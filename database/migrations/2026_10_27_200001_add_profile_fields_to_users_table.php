<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('cover_image')->nullable()->after('avatar');
            $table->string('location', 120)->nullable()->after('cover_image');
            $table->string('phone', 30)->nullable()->after('location');
            $table->string('website')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('website');
            $table->boolean('is_profile_public')->default(true)->after('bio');
            $table->boolean('show_budget_publicly')->default(false)->after('is_profile_public');
            $table->string('locale', 5)->default('en')->after('show_budget_publicly');
            $table->string('currency', 3)->default('VND')->after('locale');
            $table->boolean('notify_in_app')->default(true)->after('currency');
            $table->boolean('notify_email')->default(true)->after('notify_in_app');
        });

        // Backfill unique username for existing users
        $users = DB::table('users')->select('id', 'name', 'username')->get();
        $usedUsernames = [];

        foreach ($users as $user) {
            if (! empty($user->username)) {
                $usedUsernames[$user->username] = true;

                continue;
            }

            $baseUsername = Str::slug($user->name, '_');
            $baseUsername = preg_replace('/[^a-z0-9_]/', '', strtolower($baseUsername));

            if (empty($baseUsername) || strlen($baseUsername) < 3) {
                $baseUsername = 'user_'.$user->id;
            } elseif (strlen($baseUsername) > 20) {
                $baseUsername = substr($baseUsername, 0, 20);
            }

            $candidate = $baseUsername;
            $counter = 1;

            while (isset($usedUsernames[$candidate]) || DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = substr($baseUsername, 0, 24).'_'.$counter;
                $counter++;
            }

            $usedUsernames[$candidate] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'cover_image',
                'location',
                'phone',
                'website',
                'bio',
                'is_profile_public',
                'show_budget_publicly',
                'locale',
                'currency',
                'notify_in_app',
                'notify_email',
            ]);
        });
    }
};
