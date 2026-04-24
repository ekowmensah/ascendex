<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('symbol')->index();
            $table->enum('direction', ['UP', 'DOWN']);
            $table->decimal('amount', 18, 8);
            $table->decimal('entry_price', 18, 8);
            $table->decimal('close_price', 18, 8)->nullable();
            $table->decimal('payout_rate', 8, 4)->default(1.8);
            $table->decimal('payout_amount', 18, 8)->default(0);
            $table->enum('status', ['PENDING', 'WIN', 'LOSE'])->default('PENDING')->index();
            $table->timestamp('expiry_time')->index();
            $table->timestamp('settled_at')->nullable()->index();
            $table->timestamps();

            $table->index(['symbol', 'status', 'expiry_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
