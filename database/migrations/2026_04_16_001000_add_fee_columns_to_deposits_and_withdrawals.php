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
            $table->string('fee_type', 20)->default('flat')->after('amount');
            $table->decimal('fee_value', 18, 8)->default(0)->after('fee_type');
            $table->decimal('fee_amount', 18, 8)->default(0)->after('fee_value');
            $table->decimal('net_amount', 18, 8)->default(0)->after('fee_amount');
        });

        DB::table('deposits')->update([
            'net_amount' => DB::raw('amount'),
        ]);

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('fee_type', 20)->default('flat')->after('amount');
            $table->decimal('fee_value', 18, 8)->default(0)->after('fee_type');
            $table->decimal('fee_amount', 18, 8)->default(0)->after('fee_value');
            $table->decimal('net_amount', 18, 8)->default(0)->after('fee_amount');
        });

        DB::table('withdrawals')->update([
            'net_amount' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['fee_type', 'fee_value', 'fee_amount', 'net_amount']);
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['fee_type', 'fee_value', 'fee_amount', 'net_amount']);
        });
    }
};
