<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('other_charge_masters', 'shipping_company_id')) {
            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->unsignedBigInteger('shipping_company_id')->nullable()->after('id');
                $table->index('shipping_company_id', 'ocm_shipping_company_id_index');
            });

            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->foreign('shipping_company_id', 'ocm_shipping_company_fk')
                    ->references('id')
                    ->on('shipping_companies')
                    ->nullOnDelete();
            });
        }

        try {
            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->dropUnique('other_charge_masters_code_unique');
            });
        } catch (\Throwable $th) {
        }

        try {
            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->unique(['shipping_company_id', 'code'], 'ocm_company_code_unique');
            });
        } catch (\Throwable $th) {
        }
    }

    public function down(): void
    {
        try {
            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->dropUnique('ocm_company_code_unique');
            });
        } catch (\Throwable $th) {
        }

        if (Schema::hasColumn('other_charge_masters', 'shipping_company_id')) {
            try {
                Schema::table('other_charge_masters', function (Blueprint $table) {
                    $table->dropForeign('ocm_shipping_company_fk');
                });
            } catch (\Throwable $th) {
            }

            try {
                Schema::table('other_charge_masters', function (Blueprint $table) {
                    $table->dropIndex('ocm_shipping_company_id_index');
                });
            } catch (\Throwable $th) {
            }

            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->dropColumn('shipping_company_id');
            });
        }

        try {
            Schema::table('other_charge_masters', function (Blueprint $table) {
                $table->unique('code', 'other_charge_masters_code_unique');
            });
        } catch (\Throwable $th) {
        }
    }
};

