<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->uuid('debt_id')->primary();

            $table->uuid('customer_id');
            $table->uuid('created_by');

            $table->string('status', 30)->default('unpaid');

            $table->timestamps();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->index('customer_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};