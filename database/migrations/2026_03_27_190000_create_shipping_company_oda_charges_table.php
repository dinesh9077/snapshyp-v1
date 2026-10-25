<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipping_company_oda_charges')) {
            Schema::create('shipping_company_oda_charges', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipping_company_id');
                $table->string('name', 120);
                $table->string('code', 80);
                $table->decimal('per_kg', 10, 2)->default(0);
                $table->decimal('min_charge', 10, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['shipping_company_id', 'code'], 'shipping_company_oda_charge_unique');
                $table->foreign('shipping_company_id')
                    ->references('id')
                    ->on('shipping_companies')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('shipping_company_pincodes') && !Schema::hasColumn('shipping_company_pincodes', 'oda_charge_id')) {
            Schema::table('shipping_company_pincodes', function (Blueprint $table) {
                $table->unsignedBigInteger('oda_charge_id')->nullable()->after('oda_category');
                $table->index(['shipping_company_id', 'oda_charge_id'], 'shipping_company_pincodes_oda_charge_idx');
            });
        }

        if (!Schema::hasTable('shipping_company_oda_charges') || !Schema::hasTable('shipping_company_pincodes')) {
            return;
        }

        $companyIds = DB::table('shipping_company_pincodes')
            ->whereNotNull('shipping_company_id')
            ->distinct()
            ->pluck('shipping_company_id');

        foreach ($companyIds as $companyId) {
            $defaultOda = DB::table('overhead_charges')
                ->select('oda_per_kg', 'oda_min')
                ->where('shipping_company_id', $companyId)
                ->first();

            $perKg = (float) ($defaultOda->oda_per_kg ?? 0);
            $minCharge = (float) ($defaultOda->oda_min ?? 0);

            $legacyCategories = DB::table('shipping_company_pincodes')
                ->where('shipping_company_id', $companyId)
                ->whereNotNull('oda_category')
                ->where('oda_category', '<>', '')
                ->whereRaw("LOWER(oda_category) <> 'none'")
                ->distinct()
                ->pluck('oda_category');

            $sortOrder = 1;
            foreach ($legacyCategories as $legacyCategory) {
                $legacyCategory = strtolower(trim((string) $legacyCategory));
                if ($legacyCategory === '' || $legacyCategory === 'none') {
                    continue;
                }

                $name = strtoupper(str_replace('_', ' ', $legacyCategory));
                $code = Str::limit(str_replace('-', '_', Str::slug($legacyCategory, '_')), 80, '');
                if ($code === '') {
                    $code = 'oda_' . $sortOrder;
                }

                $existingCharge = DB::table('shipping_company_oda_charges')
                    ->where('shipping_company_id', $companyId)
                    ->where('code', $code)
                    ->first();

                if ($existingCharge) {
                    $odaChargeId = (int) $existingCharge->id;
                } else {
                    $odaChargeId = DB::table('shipping_company_oda_charges')->insertGetId([
                        'shipping_company_id' => $companyId,
                        'name' => $name,
                        'code' => $code,
                        'per_kg' => $perKg,
                        'min_charge' => $minCharge,
                        'sort_order' => $sortOrder,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('shipping_company_pincodes')
                    ->where('shipping_company_id', $companyId)
                    ->whereRaw('LOWER(oda_category) = ?', [$legacyCategory])
                    ->update([
                        'oda_charge_id' => $odaChargeId,
                        'updated_at' => now(),
                    ]);

                $sortOrder++;
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_company_pincodes') && Schema::hasColumn('shipping_company_pincodes', 'oda_charge_id')) {
            Schema::table('shipping_company_pincodes', function (Blueprint $table) {
                $table->dropIndex('shipping_company_pincodes_oda_charge_idx');
                $table->dropColumn('oda_charge_id');
            });
        }

        Schema::dropIfExists('shipping_company_oda_charges');
    }
};
