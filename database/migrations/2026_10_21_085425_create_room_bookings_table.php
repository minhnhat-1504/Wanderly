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
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('hotel_name');
            $table->string('room_type')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->decimal('price', 12, 2)->default(0.00);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_budget_included')->default(true);
            $table->timestamps();

            $table->index(['trip_id', 'check_in']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
