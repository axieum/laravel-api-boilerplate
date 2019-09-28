<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Silber\Bouncer\BouncerFacade as Bouncer;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Administrator role
        $admin = Bouncer::role()->updateOrCreate([
            'name' => 'admin',
            'title' => 'Administrator'
        ]);

        Bouncer::allow($admin)->everything();

        // Everyone
        Bouncer::allowEveryone()->to(['view'], User::class);
        Bouncer::allowEveryone()->toOwn(User::class)
            ->to(['update', 'delete', 'view.email', 'index.notification',
                'index.role', 'index.ability']);

        Bouncer::allowEveryone()->toOwn(DatabaseNotification::class)
            ->to(['read', 'mark', 'delete']);
    }
}
