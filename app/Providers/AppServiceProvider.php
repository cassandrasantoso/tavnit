<?php

namespace App\Providers;

use App\Models\PartnerApplication;
use App\Models\RefillSpot;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Short names in the translations table instead of class names,
        // so moving a model class doesn't break saved rows.
        Relation::morphMap([
            'refill_spot' => RefillSpot::class,
            'partner_application' => PartnerApplication::class,
        ]);
    }
}
