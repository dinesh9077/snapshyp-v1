<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Several accounts of the same courier (e.g. two Shadowfax logins):
 * - shipping_companies.provider: which courier integration the account uses
 * - shipping_companies.is_default: the account a seller gets when none is assigned
 * - users.assigned_shipping_company_id: the account assigned to a seller (null = default)
 */
class AddCourierAccountsToShippingCompanies extends Migration
{
    public function up()
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->string('provider', 50)->default('shadowfax')->after('name');
            $table->boolean('is_default')->default(false)->after('provider');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_shipping_company_id')->nullable()->after('rate_card_id');
        });

        // Existing rows: Shadowfax by name, anything else is a removed integration
        DB::table('shipping_companies')->where('name', 'not like', '%shadowfax%')->update(['provider' => 'other']);

        // One default per provider: the oldest active account
        foreach (DB::table('shipping_companies')->distinct()->pluck('provider') as $provider) {
            $defaultId = DB::table('shipping_companies')->where('provider', $provider)->where('status', 1)->orderBy('id')->value('id');
            if ($defaultId) {
                DB::table('shipping_companies')->where('id', $defaultId)->update(['is_default' => true]);
            }
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('assigned_shipping_company_id');
        });

        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->dropColumn(['provider', 'is_default']);
        });
    }
}
