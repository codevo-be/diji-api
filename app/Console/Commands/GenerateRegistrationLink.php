<?php

namespace App\Console\Commands;

use App\Models\RegistrationLink;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateRegistrationLink extends Command
{
    /**
     * The name and signature of the command.
     */
    protected $signature = 'registration:generate-link {email}';

    /**
     * The console command description.
     */
    protected $description = 'Génère un lien d’inscription et l’enregistre en base';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $token = Str::uuid()->toString();

        $link = RegistrationLink::create([
            'token' => $token,
            'email' => $email,
            'expires_at' => now()->addDays(7),
        ]);

        $url = env('FRONTEND_URLS') . '/register?token=' . $link->token;

        $this->info("Lien d’inscription généré pour $email");
        $this->line($url);

        return 0;
    }
}
