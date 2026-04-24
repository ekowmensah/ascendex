<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('local_currency', 10)->default('GHS')->after('amount');
            $table->decimal('local_amount', 18, 2)->default(0)->after('local_currency');
            $table->decimal('conversion_rate', 18, 8)->default(1)->after('local_amount');
        });

        DB::table('deposits')->update([
            'local_currency' => 'GHS',
            'local_amount' => DB::raw('amount'),
            'conversion_rate' => '1.00000000',
        ]);

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('local_currency', 10)->default('GHS')->after('amount');
            $table->decimal('local_amount', 18, 2)->default(0)->after('local_currency');
            $table->decimal('conversion_rate', 18, 8)->default(1)->after('local_amount');
        });

        DB::table('withdrawals')->update([
            'local_currency' => 'GHS',
            'local_amount' => DB::raw('amount'),
            'conversion_rate' => '1.00000000',
        ]);
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['local_currency', 'local_amount', 'conversion_rate']);
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['local_currency', 'local_amount', 'conversion_rate']);
        });
    }
};
