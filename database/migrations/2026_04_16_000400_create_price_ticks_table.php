<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_ticks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->decimal('price', 18, 8);
            $table->timestamp('tick_time')->index();
            $table->timestamps();

            $table->index(['symbol', 'tick_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_ticks');
    }
};
