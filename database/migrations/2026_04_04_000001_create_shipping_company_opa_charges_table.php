<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipping_company_opa_charges')) {
            Schema::create('shipping_company_opa_charges', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipping_company_id');
                $table->string('name', 120);
                $table->string('code', 80);
                $table->decimal('per_kg', 10, 2)->default(0);
                $table->decimal('min_charge', 10, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['shipping_company_id', 'code'], 'shipping_company_opa_charge_unique');
                $table->foreign('shipping_company_id')
                    ->references('id')
                    ->on('shipping_companies')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('shipping_company_pincodes')) {
            Schema::table('shipping_company_pincodes', function (Blueprint $table) {
                if (!Schema::hasColumn('shipping_company_pincodes', 'opa_category')) {
                    $table->string('opa_category', 20)->default('none');
                }

                if (!Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id')) {
                    $table->unsignedBigInteger('opa_charge_id')->nullable();
                    $table->index(['shipping_company_id', 'opa_charge_id'], 'shipping_company_pincodes_opa_charge_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_company_pincodes')) {
            Schema::table('shipping_company_pincodes', function (Blueprint $table) {
                if (Schema::hasColumn('shipping_company_pincodes', 'opa_charge_id')) {
                    $table->dropIndex('shipping_company_pincodes_opa_charge_idx');
                    $table->dropColumn('opa_charge_id');
                }
                if (Schema::hasColumn('shipping_company_pincodes', 'opa_category')) {
                    $table->dropColumn('opa_category');
                }
            });
        }

        Schema::dropIfExists('shipping_company_opa_charges');
    }
};
