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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('flash_sale_item_id')->nullable()->after('product_variant_id')->constrained()->nullOnDelete();
            // The variant's normal price when a flash sale price was charged; null otherwise.
            $table->unsignedBigInteger('regular_unit_price')->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('flash_sale_item_id');
            $table->dropColumn('regular_unit_price');
        });
    }
};
