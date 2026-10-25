<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cod_vouchers', function (Blueprint $table) {
            $table->decimal('cod_charges', 10, 2)->default(0)->after('amount');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('cod_charges');
            $table->decimal('total_charges', 10, 2)->default(0)->after('gst_amount');
            $table->decimal('net_amount', 10, 2)->default(0)->after('total_charges');
            $table->unsignedBigInteger('cod_plan_id')->nullable()->after('net_amount');
            $table->decimal('plan_percentage', 5, 2)->nullable()->after('cod_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::table('cod_vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'cod_charges',
                'gst_amount', 
                'total_charges',
                'net_amount',
                'cod_plan_id',
                'plan_percentage'
            ]);
        });
    }
};
