<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CourierCommission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shippingData = [
            ['shipping_company' => 1, 'courier_id' => 328, 'courier_name' => 'Delhivery 1Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 184, 'courier_name' => 'XpressBees 1KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 376, 'courier_name' => 'Ekart Air', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 333, 'courier_name' => 'DTDC Surface 0.5KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 29, 'courier_name' => 'XpressBees 0.5 Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 102, 'courier_name' => 'DTDC Air 0.5Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 55, 'courier_name' => 'Amazon ATS', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 384, 'courier_name' => 'Amazon ATS 2KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 341, 'courier_name' => 'XpressBees 2KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 11, 'courier_name' => 'Delhivery Surface 0.5Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 362, 'courier_name' => 'DTDC Surface 2KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 3, 'courier_name' => 'Delhivery Air', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 206, 'courier_name' => 'BlueDart 0.5KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 98, 'courier_name' => 'Bluedart SP Air0.5Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 285, 'courier_name' => 'Delhivery 2Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 230, 'courier_name' => 'Ekart 5Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 363, 'courier_name' => 'DTDC Surface 5KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 31, 'courier_name' => 'XpressBees 5Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 385, 'courier_name' => 'Amazon ATS 10KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 125, 'courier_name' => 'Delhivery Surface 5KG', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 32, 'courier_name' => 'XpressBees 10Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 13, 'courier_name' => 'Delhivery Surface 10Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 129, 'courier_name' => 'Delhivery Heavy MPS', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['shipping_company' => 1, 'courier_id' => 191, 'courier_name' => 'Movin Air 5 Kg', 'type' => 'fix', 'value' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]; 

        DB::table('courier_commissions')->insert($shippingData);
    }
}
