<?php

namespace App\Providers;

use App\Events\ActivityCostChanged;
use App\Events\RoomBudgetToggled;
use App\Listeners\RecomputeTripBudget;
use App\Models\Trip;
use App\Models\User;
use App\Policies\ProfilePolicy;
use App\Policies\TripPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Trip::class, TripPolicy::class);
        Gate::policy(User::class, ProfilePolicy::class);

        Event::listen(ActivityCostChanged::class, RecomputeTripBudget::class);
        Event::listen(RoomBudgetToggled::class, RecomputeTripBudget::class);
    }
}
