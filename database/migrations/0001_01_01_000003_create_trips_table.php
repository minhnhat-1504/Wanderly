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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('destination');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 12, 2)->default(0);
            $table->string('cover')->nullable();
            $table->enum('privacy', ['private', 'group', 'public'])->default('private');
            $table->enum('status', ['planning', 'ongoing', 'completed'])->default('planning');
            $table->timestamps();
            
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
