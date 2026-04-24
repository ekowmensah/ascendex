<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize existing duplicates so the unique index can be created safely.
        $duplicates = DB::table('transactions')
            ->select('type', 'reference')
            ->whereNotNull('reference')
            ->where('reference', '!=', '')
            ->groupBy('type', 'reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('transactions')
                ->where('type', $duplicate->type)
                ->where('reference', $duplicate->reference)
                ->orderBy('id')
                ->get(['id', 'reference']);

            foreach ($rows->skip(1) as $row) {
                $newReference = substr($row->reference.'-'.$row->id, 0, 255);

                DB::table('transactions')
                    ->where('id', $row->id)
                    ->update(['reference' => $newReference]);
            }
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->unique(['type', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropUnique(['type', 'reference']);
        });
    }
};
