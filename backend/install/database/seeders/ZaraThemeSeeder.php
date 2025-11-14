<?php

namespace Database\Seeders;

use App\Models\ThemeColor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ZaraThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * ZARA-Style Theme: Minimalist black and white with subtle gray variants
     */
    public function run(): void
    {
        $this->command->info('🎨 Seeding ZARA-style theme colors...');

        // Delete existing theme colors
        ThemeColor::query()->delete();
        $this->command->info('   ✓ Cleared existing theme colors');

        // Create ZARA-style black theme with subtle gray variants
        ThemeColor::create([
            'primary' => '#000000',           // Pure black (ZARA signature)
            'secondary' => '#F5F5F5',         // Light gray
            'variant_50' => '#FAFAFA',        // Almost white
            'variant_100' => '#F5F5F5',       // Light gray
            'variant_200' => '#E5E5E5',       // Subtle gray
            'variant_300' => '#D4D4D4',       // Medium-light gray
            'variant_400' => '#A3A3A3',       // Medium gray
            'variant_500' => '#000000',       // Base black (primary)
            'variant_600' => '#1A1A1A',       // Soft black
            'variant_700' => '#333333',       // Dark gray
            'variant_800' => '#4D4D4D',       // Medium-dark gray
            'variant_900' => '#666666',       // Gray
            'variant_950' => '#808080',       // Light gray
            'is_default' => true,
        ]);

        $this->command->info('   ✓ Created ZARA-style color palette');

        // Update general settings with ZARA colors
        $generalSettings = \App\Models\GeneraleSetting::first();
        if ($generalSettings) {
            $generalSettings->update([
                'primary_color' => '#000000',
                'secondary_color' => '#F5F5F5',
            ]);
            $this->command->info('   ✓ Updated general settings');
        }

        // Update CSS files
        $this->updateStyleCss();
        $this->updateLoginCss();

        $this->command->info('🎉 ZARA-style theme colors seeded successfully!');
        $this->command->line('');
        $this->command->line('   Primary Color: #000000 (Black)');
        $this->command->line('   Secondary Color: #F5F5F5 (Light Gray)');
        $this->command->line('');
        $this->command->info('✨ Your platform now has a minimalist, ZARA-style aesthetic!');
    }

    /**
     * Update style.css with ZARA colors
     */
    private function updateStyleCss(): void
    {
        $file = public_path('assets/css/style.css');

        if (!file_exists($file)) {
            $this->command->warn('   ⚠ style.css not found, skipping');
            return;
        }

        try {
            $str = file_get_contents($file);

            // Update CSS variables
            $str = preg_replace('/\s*--theme-color:\s*(#[a-zA-Z0-9]{6});/', '  --theme-color: #000000;', $str);
            $str = preg_replace('/\s*--theme-hover-bg:\s*(#[a-zA-Z0-9]{6});/', '  --theme-hover-bg: #F5F5F5;', $str);

            file_put_contents($file, $str);
            $this->command->info('   ✓ Updated style.css');
        } catch (\Throwable $e) {
            Log::error('Failed to update style.css: ' . $e->getMessage());
            $this->command->error('   ✗ Failed to update style.css');
        }
    }

    /**
     * Update login.css with ZARA colors
     */
    private function updateLoginCss(): void
    {
        $file = public_path('assets/css/login.css');

        if (!file_exists($file)) {
            $this->command->warn('   ⚠ login.css not found, skipping');
            return;
        }

        try {
            $str = file_get_contents($file);

            // Update CSS variables
            $str = preg_replace('/\s*--theme_color:\s*(#[a-zA-Z0-9]{6});/', '  --theme_color: #000000;', $str);

            file_put_contents($file, $str);
            $this->command->info('   ✓ Updated login.css');
        } catch (\Throwable $e) {
            Log::error('Failed to update login.css: ' . $e->getMessage());
            $this->command->error('   ✗ Failed to update login.css');
        }
    }
}
