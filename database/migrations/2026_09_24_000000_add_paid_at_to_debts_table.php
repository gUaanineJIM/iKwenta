<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paid credit records are archived for a 15-day retention window that is
     * measured from the exact date/time the debt was fully settled. This
     * migration adds the `paid_at` marker and backfills it for debts that are
     * already paid using the date/time of their final payment.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('paid_manually_at');
            $table->index('paid_at');
        });

        DB::statement(
            'UPDATE debts
             SET paid_at = (
                 SELECT MAX(payments.payment_date)
                 FROM payments
                 WHERE payments.debt_id = debts.debt_id
             )
             WHERE status = ? AND paid_at IS NULL',
            ['paid']
        );

        DB::statement(
            'UPDATE debts
             SET paid_at = updated_at
             WHERE status = ? AND paid_at IS NULL',
            ['paid']
        );
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropColumn('paid_at');
        });
    }
};
