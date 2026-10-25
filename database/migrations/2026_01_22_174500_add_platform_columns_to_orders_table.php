<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add new generic platform columns
            $table->string('platform_source', 50)->nullable()->after('courier_logo')->comment('shopify, woocommerce, magento, manual, etc.');
            $table->boolean('is_platform_order')->default(0)->after('platform_source')->comment('1 = from external platform, 0 = manual');
            $table->string('platform_order_id')->nullable()->after('is_platform_order')->comment('External platform order ID');
            $table->string('platform_order_number')->nullable()->after('platform_order_id')->comment('External platform order number');
            $table->string('platform_order_name')->nullable()->after('platform_order_number')->comment('External platform order name/reference');
            $table->boolean('platform_order_confirmed')->default(0)->after('platform_order_name')->comment('Order confirmation status');
            $table->text('platform_tags')->nullable()->after('platform_order_confirmed')->comment('Tags from platform');
            $table->json('platform_metadata')->nullable()->after('platform_tags')->comment('Additional platform-specific data');
        });

        // Migrate existing Shopify data to new platform columns
        DB::statement("
            UPDATE orders 
            SET 
                platform_source = 'shopify',
                is_platform_order = COALESCE(is_shopify_order, 0),
                platform_order_id = shopify_order_id,
                platform_order_number = shopify_order_number,
                platform_order_name = shopify_order_name,
                platform_order_confirmed = COALESCE(confirm_shopify_order, 0),
                platform_tags = shopify_tags
            WHERE is_shopify_order = 1 OR shopify_order_id IS NOT NULL OR shopify_order_number IS NOT NULL
        ");

        // Note: We are NOT dropping the old Shopify columns yet to maintain backward compatibility
        // They will be removed in a future migration after confirming everything works
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop new platform columns
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'platform_source',
                'is_platform_order',
                'platform_order_id',
                'platform_order_number',
                'platform_order_name',
                'platform_order_confirmed',
                'platform_tags',
                'platform_metadata'
            ]);
        });

        // Old Shopify columns remain intact on rollback
    }
};
