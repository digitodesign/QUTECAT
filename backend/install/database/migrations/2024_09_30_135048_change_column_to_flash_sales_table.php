<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Handle PostgreSQL type conversion with USING clause
        if (config('database.default') === 'pgsql') {
            // Drop the old default first
            DB::statement('ALTER TABLE flash_sales ALTER COLUMN status DROP DEFAULT');
            // Change the column type
            DB::statement('ALTER TABLE flash_sales ALTER COLUMN status TYPE boolean USING status::boolean');
            // Set the new default
            DB::statement('ALTER TABLE flash_sales ALTER COLUMN status SET DEFAULT true');
            DB::statement('ALTER TABLE flash_sales ALTER COLUMN status SET NOT NULL');
        } else {
            Schema::table('flash_sales', function (Blueprint $table) {
                $table->boolean('status')->default(1)->change();
            });
        }

        Schema::table('flash_sales', function (Blueprint $table) {
            $table->float('min_discount')->nullable()->default(0)->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
            $table->dropColumn('min_discount');
        });
    }
};
