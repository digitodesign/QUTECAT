<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses just getting started with online sales',
                'price' => 29.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'yearly_price' => 290.00, // 2 months free
                'products_limit' => 100,
                'orders_per_month' => 500,
                'storage_limit_mb' => 1024, // 1GB
                'team_members_limit' => 2,
                'features' => json_encode([
                    'Mobile app access',
                    'Basic analytics',
                    'Email support',
                    'Standard themes',
                    'SSL certificate',
                ]),
                'custom_domain' => false,
                'remove_branding' => false,
                'priority_support' => false,
                'api_access' => false,
                'advanced_analytics' => false,
                'multi_currency' => false,
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'For growing businesses that need more power and flexibility',
                'price' => 99.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'yearly_price' => 990.00, // 2 months free
                'products_limit' => 1000,
                'orders_per_month' => 5000,
                'storage_limit_mb' => 10240, // 10GB
                'team_members_limit' => 5,
                'features' => json_encode([
                    'Everything in Starter',
                    'Custom domain',
                    'Remove QuteCart branding',
                    'Advanced analytics',
                    'Multi-currency support',
                    'Priority email support',
                    'Premium themes',
                    'Abandoned cart recovery',
                ]),
                'custom_domain' => true,
                'remove_branding' => true,
                'priority_support' => false,
                'api_access' => false,
                'advanced_analytics' => true,
                'multi_currency' => true,
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => true, // Most popular
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For established businesses requiring unlimited scale and premium support',
                'price' => 299.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'yearly_price' => 2990.00, // 2 months free
                'products_limit' => null, // unlimited
                'orders_per_month' => null, // unlimited
                'storage_limit_mb' => 102400, // 100GB
                'team_members_limit' => 20,
                'features' => json_encode([
                    'Everything in Growth',
                    'Unlimited products',
                    'Unlimited orders',
                    'API access',
                    '24/7 priority support',
                    'Dedicated account manager',
                    'Custom integrations',
                    'Advanced reporting',
                    'White-label solution',
                    'SLA guarantee',
                ]),
                'custom_domain' => true,
                'remove_branding' => true,
                'priority_support' => true,
                'api_access' => true,
                'advanced_analytics' => true,
                'multi_currency' => true,
                'trial_days' => 30, // Longer trial for enterprise
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('plans')->insert($plans);

        $this->command->info('✅ Created 3 subscription plans: Starter ($29), Growth ($99), Enterprise ($299)');
    }
}
