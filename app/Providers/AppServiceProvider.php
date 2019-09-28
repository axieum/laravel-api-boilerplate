<?php

namespace App\Providers;

use App\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\ServiceProvider;
use Silber\Bouncer\BouncerFacade as Bouncer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Bouncer::ownedVia(User::class, 'id');
        Bouncer::ownedVia(DatabaseNotification::class, 'notifiable_id');
    }
}
