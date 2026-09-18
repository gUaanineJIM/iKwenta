<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $validStatuses = ['unpaid', 'partially_paid', 'paid'];

        $legacyDebtIds = DB::table('debts')
            ->whereNotIn('status', $validStatuses)
            ->pluck('debt_id');

        if ($legacyDebtIds->isNotEmpty()) {
            DB::table('payments')->whereIn('debt_id', $legacyDebtIds)->delete();
            DB::table('debts')->whereIn('debt_id', $legacyDebtIds)->delete();
        }

        Schema::table('debts', function (Blueprint $table) use ($validStatuses) {
            $table->enum('status', $validStatuses)
                ->default('unpaid')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('unpaid')
                ->change();
        });
    }
};
