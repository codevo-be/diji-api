<?php

namespace App\Console\Commands;

use App\Models\RegistrationLink;
use App\Services\Brevo;
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
    protected $description = 'Génère un lien d’inscription, l’enregistre en base et l’envoie par email';

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
        $settings = json_decode(env('BREVO_SETTINGS', '{}'), true);

        $this->info("Lien d’inscription généré pour $email");
        $this->line($url);

        try {
            $mailService = new Brevo($settings);

            $mailService
                ->to($email)
                ->subject("Votre lien d’inscription à Diji")
                ->content(nl2br("Bonjour,\n\nVoici votre lien d’inscription :\n{$url}\n\nCe lien est valable pendant 7 jours."))
                ->send();

            $this->info("Email envoyé à $email");
        } catch (\Exception $e) {
            $this->error("Échec de l’envoi de l’email : " . $e->getMessage());
        }

        return 0;
    }
}
