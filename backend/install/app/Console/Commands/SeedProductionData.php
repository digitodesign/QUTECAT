<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedProductionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-production {--force : Force seeding even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed production database with demo data and ZARA theme';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Production Data Seeding...');
        $this->newLine();

        // Check if database has users
        $userCount = \App\Models\User::count();
        
        if ($userCount > 0 && !$this->option('force')) {
            $this->warn('⚠️  Database already contains data.');
            
            if (!$this->confirm('Do you want to seed ZARA theme only?', true)) {
                $this->info('Seeding cancelled.');
                return 0;
            }
            
            $this->info('Applying ZARA theme...');
            Artisan::call('db:seed', [
                '--class' => 'ZaraThemeSeeder',
                '--force' => true
            ]);
            $this->info('✓ ZARA theme applied');
            
            $this->clearCaches();
            return 0;
        }

        $this->info('📊 Seeding essential system data...');
        $seeders = [
            'RoleSeeder',
            'PermissionSeeder',
            'CurrencySeeder',
            'GeneraleSettingSeeder',
            'LegalPageSeeder',
            'PaymentGatewaySeeder',
            'SocialLinkSeeder',
            'ThemeColorSeeder',
            'SocialAuthSeeder',
            'VerifyManageSeeder',
            'PageSeeder',
            'MenuSeeder',
            'CountrySeeder',
            'FooterSeeder',
            'PlansTableSeeder',
            'WalletSeeder',
        ];

        foreach ($seeders as $seeder) {
            $this->info("  → $seeder");
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true
            ]);
        }

        $this->newLine();
        $this->info('🛍️  Seeding demo content (products, categories, shops)...');
        
        $demoSeeders = [
            'UserSeeder',
            'CustomerSeeder',
            'RiderSeeder',
            'ShopSeeder',
            'CategorySeeder',
            'BrandSeeder',
            'SizeSeeder',
            'ColorSeeder',
            'UnitSeeder',
            'ProductSeeder',
            'BannerSeeder',
            'CouponSeeder',
            'AddressSeeder',
            'BlogSeeder',
            'RootAdminShopSeeder',
        ];

        foreach ($demoSeeders as $seeder) {
            $this->info("  → $seeder");
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true
            ]);
        }

        $this->newLine();
        $this->info('🎨 Applying ZARA theme...');
        Artisan::call('db:seed', [
            '--class' => 'ZaraThemeSeeder',
            '--force' => true
        ]);

        $this->newLine();
        $this->clearCaches();

        $this->newLine();
        $this->info('✅ Production data seeded successfully!');
        $this->newLine();
        
        $this->displayCredentials();

        return 0;
    }

    private function clearCaches()
    {
        $this->info('🧹 Clearing caches...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        $this->info('📦 Caching for production...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        $this->info('✓ Caches optimized');
    }

    private function displayCredentials()
    {
        $this->info('═══════════════════════════════════════');
        $this->info('         Login Credentials');
        $this->info('═══════════════════════════════════════');
        $this->newLine();
        
        $this->warn('Root Admin:');
        $this->line('  Email: root@qutekart.com');
        $this->line('  Password: secret');
        $this->newLine();
        
        $this->warn('Demo Shop:');
        $this->line('  Email: shop@qutekart.com');
        $this->line('  Password: secret');
        $this->newLine();
        
        $this->warn('Demo Customer:');
        $this->line('  Email: customer@qutekart.com');
        $this->line('  Password: secret');
        $this->newLine();
        
        $appUrl = config('app.url', 'http://localhost');
        $this->info("Visit: $appUrl");
        $this->info("Admin Panel: $appUrl/admin");
        $this->info('═══════════════════════════════════════');
    }
}
