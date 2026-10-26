<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Seller-wise weight slabs (user_id null = global slab) and a seller default weight for orders
// that arrive without one (e.g. Shopify products with no weight set)
class AddSellerWeightDimensions extends Migration
{
    public function up()
    {
        Schema::table('weight_dimensions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            $table->dropUnique('weight_dimensions_slab_unique');
            $table->unique(['user_id', 'slab']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('default_package_weight', 8, 3)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_package_weight');
        });

        Schema::table('weight_dimensions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slab']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
            $table->unique('slab');
        });
    }
}
