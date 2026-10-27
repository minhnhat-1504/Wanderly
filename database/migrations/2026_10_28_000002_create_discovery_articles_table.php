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
        Schema::create('discovery_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('discovery_regions')->cascadeOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('nature')->index(); // culture, cuisine, festival, nature
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('best_season')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('location_name')->nullable();
            $table->json('highlights')->nullable();
            $table->json('specialties')->nullable();
            $table->string('estimated_duration')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovery_articles');
    }
};
