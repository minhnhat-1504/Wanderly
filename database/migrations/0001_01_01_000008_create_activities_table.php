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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->enum('time_slot', ['morning', 'midday', 'afternoon', 'evening']);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('title');
            $table->decimal('cost', 12, 2)->default(0.00);
            $table->enum('category', [
                'sightseeing',
                'dining',
                'cafe',
                'shopping',
                'entertainment',
                'other',
            ]);
            $table->string('location');
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('type', ['activity', 'room', 'booking'])->default('activity');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['trip_id', 'day', 'time_slot', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
