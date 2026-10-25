<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CodTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $termsContent = "1. COD charges are applicable as per the selected plan
2. Remittance will be processed according to the T+ days configuration
3. GST will be charged additionally on the COD percentage
4. Plans can be changed at any time from the user dashboard
5. Deactivation of plans will stop COD services immediately
6. All COD transactions are subject to verification
7. Disputes must be raised within 7 days of transaction
8. Company reserves the right to modify terms and conditions
9. Users must maintain sufficient wallet balance for any additional charges
10. COD services are subject to availability in the delivery location";

        // Check if setting exists
        $setting = DB::table('settings')->where('name', 'cod_term')->first();
        
        if ($setting) {
            // Update existing setting
            DB::table('settings')
                ->where('name', 'cod_term')
                ->update([
                    'value' => $termsContent,
                    'updated_at' => now()
                ]);
        } else {
            // Create new setting
            DB::table('settings')->insert([
                'name' => 'cod_term',
                'value' => $termsContent,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
