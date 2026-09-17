<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('product_id')->primary();

            $table->string('product_name', 150);
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);

            $table->uuid('created_by');

            $table->timestamps();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->index('product_name');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};