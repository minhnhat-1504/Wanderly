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
        if (! Schema::hasTable('providers')) {
            Schema::create('providers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('business_name');
                $table->enum('service_type', [
                    'accommodation',
                    'transport',
                    'tour',
                    'restaurant',
                    'photo',
                    'guide',
                ]);
                $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
                $table->text('bio')->nullable();
                $table->decimal('rating', 2, 1)->default(5.0);
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
