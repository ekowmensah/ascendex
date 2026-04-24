<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->string('mobile_provider', 20)->nullable()->after('payment_method');
            $table->string('sender_name')->nullable()->after('mobile_provider');
            $table->string('sender_phone', 30)->nullable()->after('sender_name');
            $table->string('transaction_reference', 100)->nullable()->after('sender_phone');
        });

        Schema::table('withdrawals', function (Blueprint $table): void {
            $table->string('mobile_provider', 20)->nullable()->after('destination');
            $table->string('account_name')->nullable()->after('mobile_provider');
            $table->string('account_number', 50)->nullable()->after('account_name');
            $table->string('account_phone', 30)->nullable()->after('account_number');
        });

        $parseKeyValueNote = static function (?string $note): array {
            $parsed = [
                'sender_name' => null,
                'sender_phone' => null,
                'tx_ref' => null,
                'account_name' => null,
                'account_phone' => null,
                'extra_note' => [],
            ];

            foreach (array_filter(array_map('trim', explode('|', (string) $note))) as $part) {
                if (! str_contains($part, '=')) {
                    $parsed['extra_note'][] = $part;

                    continue;
                }

                [$key, $value] = array_map('trim', array_pad(explode('=', $part, 2), 2, ''));

                if (array_key_exists($key, $parsed) && $key !== 'extra_note') {
                    $parsed[$key] = $value !== '' ? $value : null;

                    continue;
                }

                $parsed['extra_note'][] = $part;
            }

            return $parsed;
        };

        DB::table('deposits')
            ->select(['id', 'payment_method', 'note'])
            ->orderBy('id')
            ->chunkById(100, function ($deposits) use ($parseKeyValueNote): void {
                foreach ($deposits as $deposit) {
                    $paymentMethod = strtolower(trim((string) $deposit->payment_method));
                    $provider = null;

                    if ($paymentMethod !== '' && str_contains($paymentMethod, ':')) {
                        $provider = explode(':', $paymentMethod, 2)[1] ?: null;
                    } elseif ($paymentMethod !== '') {
                        $provider = $paymentMethod;
                    }

                    $parsed = $parseKeyValueNote($deposit->note);

                    DB::table('deposits')
                        ->where('id', $deposit->id)
                        ->update([
                            'mobile_provider' => $provider,
                            'sender_name' => $parsed['sender_name'],
                            'sender_phone' => $parsed['sender_phone'],
                            'transaction_reference' => $parsed['tx_ref'],
                            'note' => count($parsed['extra_note']) ? implode(' | ', $parsed['extra_note']) : null,
                        ]);
                }
            });

        DB::table('withdrawals')
            ->select(['id', 'destination', 'note'])
            ->orderBy('id')
            ->chunkById(100, function ($withdrawals) use ($parseKeyValueNote): void {
                foreach ($withdrawals as $withdrawal) {
                    $destinationParts = explode(':', (string) $withdrawal->destination);
                    $provider = $destinationParts[1] ?? null;
                    $accountNumber = $destinationParts[2] ?? null;
                    $parsed = $parseKeyValueNote($withdrawal->note);

                    DB::table('withdrawals')
                        ->where('id', $withdrawal->id)
                        ->update([
                            'mobile_provider' => $provider,
                            'account_name' => $parsed['account_name'],
                            'account_number' => $accountNumber,
                            'account_phone' => $parsed['account_phone'],
                            'note' => count($parsed['extra_note']) ? implode(' | ', $parsed['extra_note']) : null,
                        ]);
                }
            });

        Schema::table('deposits', function (Blueprint $table): void {
            $table->index('mobile_provider');
            $table->index('transaction_reference');
        });

        Schema::table('withdrawals', function (Blueprint $table): void {
            $table->index('mobile_provider');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table): void {
            $table->dropIndex(['mobile_provider']);
            $table->dropColumn(['mobile_provider', 'account_name', 'account_number', 'account_phone']);
        });

        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropIndex(['mobile_provider']);
            $table->dropIndex(['transaction_reference']);
            $table->dropColumn(['mobile_provider', 'sender_name', 'sender_phone', 'transaction_reference']);
        });
    }
};
