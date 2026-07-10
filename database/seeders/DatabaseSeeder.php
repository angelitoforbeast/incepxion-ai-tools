<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Plans ----------
        $free = Plan::updateOrCreate(['slug' => 'free'], [
            'name'          => 'Free',
            'price'         => 0,
            'currency'      => 'PHP',
            'interval'      => 'month',
            'daily_quota'   => 20,
            'monthly_quota' => 300,
            'features'      => ['All tools (BYOK)', '20 generations/day'],
            'is_active'     => true,
            'sort_order'    => 1,
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name'          => 'Pro',
            'price'         => 299,
            'currency'      => 'PHP',
            'interval'      => 'month',
            'daily_quota'   => 200,
            'monthly_quota' => 5000,
            'features'      => ['All tools (BYOK)', '200 generations/day', 'Priority support'],
            'is_active'     => true,
            'sort_order'    => 2,
        ]);

        // ---------- Admin user ----------
        User::updateOrCreate(['email' => 'admin@incepxion-ai.com'], [
            'name'              => 'Admin',
            'password'          => Hash::make('password'), // CHANGE THIS after first login
            'role'              => 'admin',
            'status'            => 'approved',
            'plan_id'           => $free->id,
            'email_verified_at' => now(),
            'approved_at'       => now(),
        ]);

        // ---------- Tools ----------
        Tool::updateOrCreate(['slug' => 'ad-copy-generator'], [
            'name'        => 'AI Ad Copy Generator',
            'description' => 'Gumawa ng high-converting Facebook ad copy para sa Pinoy market.',
            'icon'        => '📣',
            'category'    => 'Marketing',
            'is_active'   => true,
            'sort_order'  => 1,
            'config'      => [
                'provider'      => 'openai',
                'default_model' => 'gpt-4o',
            ],
        ]);
    }
}
