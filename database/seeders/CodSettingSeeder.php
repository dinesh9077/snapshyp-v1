<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CodSetting;

class CodSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'plan_name' => 'T+1 Day',
                'days' => 1,
                'percentage' => 1.00,
                'gst_percentage' => 18.00,
                'description' => 'The applicable transaction charges is 1.00% of the COD amount (Exclusive of GST)',
                'is_active' => true,
                'is_default' => false
            ],
            [
                'plan_name' => 'T+2 Days',
                'days' => 2,
                'percentage' => 0.70,
                'gst_percentage' => 18.00,
                'description' => 'The applicable transaction charges is 0.70% of the COD amount (Exclusive of GST)',
                'is_active' => true,
                'is_default' => false
            ],
            [
                'plan_name' => 'T+3 Days',
                'days' => 3,
                'percentage' => 0.50,
                'gst_percentage' => 18.00,
                'description' => 'The applicable transaction charges is 0.50% of the COD amount (Exclusive of GST)',
                'is_active' => true,
                'is_default' => false
            ],
            [
                'plan_name' => 'T+4 Days (Default)',
                'days' => 4,
                'percentage' => 0,
                'gst_percentage' => 18.00,
                'description' => 'The applicable transaction charges is 0.00% of the COD amount (Exclusive of GST)',
                'is_active' => true,
                'is_default' => true
            ]
        ];

        foreach ($settings as $setting) {
            CodSetting::updateOrCreate(
                ['days' => $setting['days']],
                $setting
            );
        }
    }
}
