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
        Schema::table('orders', function (Blueprint $table) {
            // Index for order list query (customer + status + payment + created_at)
            $table->index(['customer_id', 'status', 'payment_status', 'created_at'], 'idx_orders_list');
            // Index for order search
            $table->index('order_no', 'idx_orders_no');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Index for order items queries
            $table->index('order_shop_id', 'idx_order_items_shop');
            $table->index('product_variant_id', 'idx_order_items_variant');
            // Index for search by name
            $table->index('name', 'idx_order_items_name');
        });

        Schema::table('order_shops', function (Blueprint $table) {
            // Index for order shops by order
            $table->index('order_id', 'idx_order_shops_order');
            $table->index('shop_id', 'idx_order_shops_shop');
            $table->index('status', 'idx_order_shops_status');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            // Index for cart queries
            $table->index('cart_id', 'idx_cart_items_cart');
            $table->index('vendor_listing_id', 'idx_cart_items_listing');
        });

        Schema::table('carts', function (Blueprint $table) {
            // Index for cart lookup
            $table->index('customer_id', 'idx_carts_customer');
            $table->index('session_id', 'idx_carts_session');
        });

        Schema::table('media', function (Blueprint $table) {
            // Index for media queries
            $table->index(['owner_type', 'owner_id'], 'idx_media_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_list');
            $table->dropIndex('idx_orders_no');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_shop');
            $table->dropIndex('idx_order_items_variant');
            $table->dropIndex('idx_order_items_name');
        });

        Schema::table('order_shops', function (Blueprint $table) {
            $table->dropIndex('idx_order_shops_order');
            $table->dropIndex('idx_order_shops_shop');
            $table->dropIndex('idx_order_shops_status');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart');
            $table->dropIndex('idx_cart_items_listing');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_customer');
            $table->dropIndex('idx_carts_session');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('idx_media_owner');
        });
    }
};
