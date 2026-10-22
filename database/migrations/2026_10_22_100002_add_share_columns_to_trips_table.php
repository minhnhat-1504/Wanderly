<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('share_slug')->nullable()->unique()->after('status');
            $table->boolean('is_shared')->default(false)->after('share_slug');
            $table->boolean('is_share_budget_visible')->default(true)->after('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['share_slug', 'is_shared', 'is_share_budget_visible']);
        });
    }
};
