<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments are account-level: a payment reduces the customer's total
     * balance, never a specific credit transaction. Relink payments from
     * debts.debt_id to customers.customer_id and drop the per-transaction
     * debts.status column (status is derived from the account balance).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable()->after('payment_id');
        });

        DB::table('payments')->update([
            'customer_id' => DB::raw(
                '(SELECT customer_id FROM debts WHERE debts.debt_id = payments.debt_id LIMIT 1)'
            ),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['debt_id']);
            $table->dropIndex(['debt_id']);
            $table->dropColumn('debt_id');

            $table->uuid('customer_id')->nullable(false)->change();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->restrictOnDelete();

            $table->index('customer_id');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('status', 30)->default('unpaid')->after('created_by');

            $table->index('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('debt_id')->nullable()->after('payment_id');
        });

        DB::table('payments')->update([
            'debt_id' => DB::raw(
                '(SELECT debt_id FROM debts WHERE debts.customer_id = payments.customer_id LIMIT 1)'
            ),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');

            $table->uuid('debt_id')->nullable(false)->change();

            $table->foreign('debt_id')
                ->references('debt_id')
                ->on('debts')
                ->restrictOnDelete();

            $table->index('debt_id');
        });
    }
};
