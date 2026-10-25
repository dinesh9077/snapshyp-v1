<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateSellerIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get users without seller_id
        $users = User::whereNull('seller_id')->where('role', 'user')->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->command->info('No users found needing seller_id update.');
            return;
        }

        // Determine starting seller_id
        $maxSellerId = User::where('role', 'user')->max('seller_id');
        $nextId = $maxSellerId ? $maxSellerId + 1 : 1001;

        $this->command->info('Starting seller_id update for ' . $users->count() . ' users...');

        $bar = $this->command->getOutput()->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $user->seller_id = $nextId;
            $user->save();
            $nextId++;
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Successfully updated users with new seller_ids.");
    }
}
