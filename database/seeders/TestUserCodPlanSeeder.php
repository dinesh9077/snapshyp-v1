<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CodSetting;

class TestUserCodPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Find all users with role 'user'
        $users = User::where('role', 'user')->get();
        
        if ($users->count() > 0) {
            // Get the default COD setting (T+4 Days)
            $defaultPlan = CodSetting::where('is_default', true)->first();
            
            if ($defaultPlan) {
                $updatedCount = 0;
                
                foreach ($users as $user) {
                    // Activate the default plan for each user
                    $user->update([
                        'cod_plan_id' => $defaultPlan->id,
                        'cod_plan_active' => true
                    ]);
                    
                    echo "User '{$user->name}' (ID: {$user->id}) has been assigned COD plan '{$defaultPlan->plan_name}'\n";
                    $updatedCount++;
                }
                
                echo "\nTotal users updated: {$updatedCount}\n";
                echo "COD Plan assigned: {$defaultPlan->plan_name} ({$defaultPlan->percentage}% charges)\n";
            } else {
                echo "No default COD plan found. Please run CodSettingSeeder first.\n";
            }
        } else {
            echo "No users with role 'user' found. Please create test users first.\n";
        }
    }
}
