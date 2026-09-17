<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('payment_id')->primary();

            $table->uuid('debt_id');

            $table->decimal('amount_paid', 12, 2);

            $table->timestamp('payment_date');

            $table->uuid('received_by');

            $table->timestamps();

            $table->foreign('debt_id')
                ->references('debt_id')
                ->on('debts')
                ->restrictOnDelete();

            $table->foreign('received_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->index('debt_id');
            $table->index('received_by');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};