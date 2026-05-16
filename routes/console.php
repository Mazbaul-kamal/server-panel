<?php

use App\Models\User;
use App\Services\Hosting\HostingCatalog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('panel:admin {email} {--name=} {--password=}', function (string $email): int {
    $password = (string) ($this->option('password') ?: Str::password(24));

    $user = User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $this->option('name') ?: 'Administrator',
            'password' => $password,
            'is_admin' => true,
        ],
    );

    $this->info("Admin user ready: {$user->email}");

    if (! $this->option('password')) {
        $this->warn("Generated password: {$password}");
    }

    return 0;
})->purpose('Create or update an administrator who can access the Filament panel.');

Artisan::command('panel:sync-catalog', function (HostingCatalog $catalog): int {
    $catalog->sync();

    $this->info('Hosting module catalog synced.');

    return 0;
})->purpose('Sync CyberPanel-style module and service catalog records.');
