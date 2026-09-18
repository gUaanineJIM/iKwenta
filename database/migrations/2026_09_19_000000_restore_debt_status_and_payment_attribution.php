<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Credit records (debts) carry their own status again (unpaid,
     * partially_paid, paid) plus an optional manual-payment marker, and each
     * payment is attributed to the credit/customer record it settles.
     *
     * Existing account-level payments are redistributed in first-in,
     * first-out order across the customer's credit records, then each record's
     * status is recomputed from its recorded payments.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('debt_id')->nullable()->after('payment_id');
        });

        $this->attributePaymentsToCreditRecords();

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('debt_id')->nullable(false)->change();

            $table->foreign('debt_id')
                ->references('debt_id')
                ->on('debts')
                ->restrictOnDelete();

            $table->index('debt_id');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->string('status', 30)->default('unpaid')->after('created_by');
            $table->boolean('paid_manually')->default(false)->after('status');
            $table->uuid('paid_manually_by')->nullable()->after('paid_manually');
            $table->timestamp('paid_manually_at')->nullable()->after('paid_manually_by');

            $table->index('status');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->foreign('paid_manually_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });

        $this->backfillDebtStatuses();
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropForeign(['paid_manually_by']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'paid_manually',
                'paid_manually_by',
                'paid_manually_at',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['debt_id']);
            $table->dropIndex(['debt_id']);
            $table->dropColumn('debt_id');
        });
    }

    /**
     * Attribute account-level payments to credit records in FIFO order.
     *
     * For each customer, payments are replayed from the oldest to the newest
     * and applied to the oldest credit record of that customer that still has
     * unpaid capacity. This preserves the original account totals while
     * giving every payment a concrete credit-record context.
     */
    private function attributePaymentsToCreditRecords(): void
    {
        $customerIds = DB::table('customers')->pluck('customer_id');

        foreach ($customerIds as $customerId) {
            $debts = DB::table('debts')
                ->where('customer_id', $customerId)
                ->orderBy('created_at')
                ->orderBy('debt_id')
                ->get(['debt_id']);

            if ($debts->isEmpty()) {
                continue;
            }

            $capacity = [];

            foreach ($debts as $debt) {
                $capacity[$debt->debt_id] = (float) DB::table('debt_items')
                    ->where('debt_id', $debt->debt_id)
                    ->sum('subtotal');
            }

            $payments = DB::table('payments')
                ->where('customer_id', $customerId)
                ->orderBy('payment_date')
                ->orderBy('payment_id')
                ->get(['payment_id', 'amount_paid']);

            foreach ($payments as $payment) {
                $target = null;

                foreach ($capacity as $debtId => $remaining) {
                    if ($remaining > 0) {
                        $target = $debtId;
                        break;
                    }
                }

                if ($target === null) {
                    $target = $debts->first()->debt_id;
                }

                DB::table('payments')
                    ->where('payment_id', $payment->payment_id)
                    ->update(['debt_id' => $target]);

                $capacity[$target] -= (float) $payment->amount_paid;
            }
        }
    }

    /**
     * Derive each credit record's status from its attributed payments.
     */
    private function backfillDebtStatuses(): void
    {
        $debts = DB::table('debts')->get(['debt_id']);

        foreach ($debts as $debt) {
            $total = (float) DB::table('debt_items')
                ->where('debt_id', $debt->debt_id)
                ->sum('subtotal');

            $paid = (float) DB::table('payments')
                ->where('debt_id', $debt->debt_id)
                ->sum('amount_paid');

            $status = 'unpaid';

            if ($paid > 0 && $paid < $total) {
                $status = 'partially_paid';
            } elseif ($paid >= $total) {
                $status = 'paid';
            }

            DB::table('debts')
                ->where('debt_id', $debt->debt_id)
                ->update(['status' => $status]);
        }
    }
};
