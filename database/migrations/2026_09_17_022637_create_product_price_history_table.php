<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->uuid('price_history_id')->primary();

            $table->uuid('product_id');
            $table->decimal('price', 12, 2);

            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();

            $table->uuid('changed_by');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('changed_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->index([
                'product_id',
                'effective_from',
            ]);

            $table->index([
                'product_id',
                'effective_to',
            ]);

            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};