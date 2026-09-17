<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_items', function (Blueprint $table) {
            $table->uuid('debt_item_id')->primary();

            $table->uuid('debt_id');
            $table->uuid('product_id');

            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('debt_id')
                ->references('debt_id')
                ->on('debts')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->restrictOnDelete();

            $table->index('debt_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_items');
    }
};