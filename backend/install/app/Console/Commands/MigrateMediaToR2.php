<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

class MigrateMediaToR2 extends Command
{
    protected $signature = 'media:migrate-r2';
    protected $description = 'Update all existing Media records to use R2 storage';

    public function handle()
    {
        $this->info('🚀 Migrating Media records to R2 storage...');

        $updated = Media::whereNull('disk')
            ->orWhere('disk', '!=', 'r2')
            ->update([
                'disk' => 'r2',
                'src' => 'default/default.jpg',
            ]);

        $this->info("✅ Updated {$updated} media records to use R2 storage.");

        return Command::SUCCESS;
    }
}
