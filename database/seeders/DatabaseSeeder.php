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
            'daily_quota'   => 1000,
            'monthly_quota' => 30000,
            'features'      => ['All tools (BYOK)', '1000 generations/day'],
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

        // ---------- Admin user (firstOrCreate: never resets an existing password) ----------
        User::firstOrCreate(['email' => 'admin@incepxion-ai.com'], [
            'name'              => 'Admin',
            'password'          => Hash::make('password'), // CHANGE THIS after first login
            'role'              => 'admin',
            'status'            => 'approved',
            'plan_id'           => $free->id,
            'email_verified_at' => now(),
            'approved_at'       => now(),
        ]);

        // ---------- Tools ----------
        $tools = [
            [
                'slug' => 'ad-copy-generator', 'name' => 'AI Ad Copy Generator',
                'description' => 'Gumawa ng high-converting Facebook ad copy para sa Pinoy market.',
                'icon' => '📣', 'category' => 'Marketing', 'sort_order' => 1,
                'config' => ['provider' => 'openai', 'default_model' => 'gpt-4o'],
            ],
            [
                'slug' => 'rts-processor', 'name' => 'RTS Processor',
                'description' => 'I-upload ang courier file (J&T, atbp.) para mabilis i-proseso ang Return-to-Sender orders.',
                'icon' => '📦', 'category' => 'Logistics', 'sort_order' => 2,
                'config' => ['status' => 'coming_soon'],
            ],
            [
                'slug' => 'profit-computation', 'name' => 'Profit Computation',
                'description' => 'Kalkulahin ang kita per order/product — kasama ang shipping, fees, at COGS.',
                'icon' => '💰', 'category' => 'Finance', 'sort_order' => 3,
                'config' => ['status' => 'coming_soon'],
            ],
        ];

        foreach ($tools as $t) {
            // firstOrCreate so re-seeding never wipes admin-edited config (prompts, template).
            Tool::firstOrCreate(['slug' => $t['slug']], array_merge($t, ['is_active' => true]));
        }
    }
}
