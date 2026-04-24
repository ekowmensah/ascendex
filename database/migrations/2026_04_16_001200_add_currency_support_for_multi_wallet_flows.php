<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('currency', 10)->default('USDT')->after('user_id');
        });

        DB::table('wallets')->update(['currency' => 'USDT']);

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('wallets_user_id_unique');
            $table->unique(['user_id', 'currency']);
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->string('currency', 10)->default('USDT')->after('user_id');
        });

        DB::table('deposits')->update(['currency' => 'USDT']);

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('currency', 10)->default('USDT')->after('user_id');
        });

        DB::table('withdrawals')->update(['currency' => 'USDT']);
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'currency']);
            $table->unique('user_id');
            $table->dropColumn('currency');
        });
    }
};
