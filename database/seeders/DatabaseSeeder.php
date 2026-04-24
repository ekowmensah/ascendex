<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Support\WalletCurrency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $trader = User::factory()->create([
            'name' => 'Trader User',
            'email' => 'trader@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        foreach (WalletCurrency::all() as $currency) {
            Wallet::query()->updateOrCreate([
                'user_id' => $admin->id,
                'currency' => $currency,
            ], [
                'balance' => $currency === WalletCurrency::DEFAULT ? 1000 : 0,
                'bonus' => 0,
                'locked_balance' => 0,
            ]);

            Wallet::query()->updateOrCreate([
                'user_id' => $trader->id,
                'currency' => $currency,
            ], [
                'balance' => $currency === WalletCurrency::DEFAULT ? 500 : 0,
                'bonus' => 0,
                'locked_balance' => 0,
            ]);
        }

        AdminSetting::query()->updateOrCreate(['key' => 'payout_percent'], ['value' => '80']);
        AdminSetting::query()->updateOrCreate(['key' => 'payout_rate'], ['value' => '1.8']);
        AdminSetting::query()->updateOrCreate(['key' => 'ghs_per_usdt'], ['value' => '15.00000000']);
    }
}
