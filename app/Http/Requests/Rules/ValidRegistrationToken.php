<?php

namespace App\Http\Requests\Rules;

use App\Models\RegistrationLink;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRegistrationToken implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $link = RegistrationLink::on('mysql')->where('token', $value)->first();

        if (!$link) {
            $fail('Le lien d’inscription est invalide.');
            return;
        }

        if ($link->used_at !== null) {
            $fail('Ce lien a déjà été utilisé.');
            return;
        }

        if ($link->expires_at->isPast()) {
            $fail('Ce lien a expiré.');
            return;
        }
    }
}
