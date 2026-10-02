<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email : Het e-mailadres van een bestaande gebruiker}';

    protected $description = 'Geef een bestaande geregistreerde gebruiker de adminrol voor de web-guard';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('Geen geregistreerde gebruiker gevonden met dit e-mailadres.');

            return self::FAILURE;
        }

        $user->assignRole(Role::findOrCreate('admin', 'web'));

        $this->info("Gebruiker {$user->email} heeft nu de adminrol.");

        return self::SUCCESS;
    }
}
