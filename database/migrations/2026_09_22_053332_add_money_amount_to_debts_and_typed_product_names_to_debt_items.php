<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (! Schema::hasColumn('debts', 'money_amount')) {
                $table->decimal('money_amount', 12, 2)->default(0)->after('paid_manually_at');
            }
        });

        Schema::table('debt_items', function (Blueprint $table) {
            if (! Schema::hasColumn('debt_items', 'product_name')) {
                $table->string('product_name', 150)->nullable()->after('product_id');
            }

            $foreignKeys = collect(Schema::getForeignKeys('debt_items'));
            if ($foreignKeys->contains('name', 'debt_items_product_id_foreign')) {
                $table->dropForeign(['product_id']);
            }

            $table->uuid('product_id')->nullable()->change();

            if (! $foreignKeys->contains('name', 'debt_items_product_id_foreign')) {
                $table->foreign('product_id')
                    ->references('product_id')
                    ->on('products')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debt_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_name');
            $table->uuid('product_id')->nullable(false)->change();
            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->restrictOnDelete();
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('money_amount');
        });
    }
};
