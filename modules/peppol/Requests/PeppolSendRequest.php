<?php

namespace Diji\Peppol\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeppolSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Document
            'document.bill_name' => 'required|string|max:255',
            'document.issue_date' => 'required|date',
            'document.due_date' => 'required|date|after_or_equal:document.issue_date',
            'document.currency' => 'required|string|size:3',
            'document.buyer_reference' => 'required|string|max:70',
            'document.structured_communication' => 'nullable|string|max:70',

            // Sender
            'sender.name' => 'required|string|max:150',
            'sender.vat_number' => 'required|string|max:20',
            'sender.iban' => 'required|string|max:34',
            'sender.address.line1' => 'required|string|max:150',
            'sender.address.city' => 'required|string|max:100',
            'sender.address.zip_code' => 'required|string|max:20',
            'sender.address.country' => 'required|string|size:2',

            // Receiver
            'receiver.name' => 'required|string|max:150',
            'receiver.peppol_identifier' => 'required|string',
            'receiver.vat_number' => 'required|string|max:20',
            'receiver.contact.name' => 'nullable|string|max:150',
            'receiver.contact.phone' => 'nullable|string|max:50',
            'receiver.contact.email' => 'nullable|email|max:150',
            'receiver.address.line1' => 'required|string|max:150',
            'receiver.address.city' => 'required|string|max:100',
            'receiver.address.zip_code' => 'required|string|max:20',
            'receiver.address.country' => 'required|string|size:2',

            // Delivery
            'delivery.date' => 'required|date',

            // Payment
            'payment.payment_delay' => 'required|integer|min:1|max:365',

            // Totals
            'totals.total_taxable_amount' => 'required|numeric|min:0',
            'totals.total_amount' => 'required|numeric|min:0',

            // Taxes
            'taxes' => 'required|array|min:1',
            'taxes.*.vat_code' => 'required|string|max:10',
            'taxes.*.tax_percentage' => 'required|numeric|min:0',
            'taxes.*.taxable_amount' => 'required|numeric|min:0',
            'taxes.*.tax_amount' => 'required|numeric|min:0',

            // Invoice lines
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.taxable_amount' => 'required|numeric|min:0',
            'lines.*.vat_code' => 'required|string|max:10',
            'lines.*.tax_percentage' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            // Document
            'document.bill_name.required' => 'Le nom de la facture est requis.',
            'document.issue_date.required' => 'La date d’émission est requise.',
            'document.due_date.required' => 'La date d’échéance est requise.',
            'document.due_date.after_or_equal' => 'La date d’échéance doit être postérieure ou égale à la date d’émission.',
            'document.currency.required' => 'La devise est requise.',
            'document.currency.size' => 'La devise doit être un code ISO 4217 de 3 lettres (ex: EUR).',
            'document.buyer_reference.required' => 'Le champ "buyer_reference" est requis.',
            'document.structured_communication.max' => 'La communication structurée ne peut pas dépasser 70 caractères.',

            // Sender
            'sender.name.required' => 'Le nom de l’émetteur est requis.',
            'sender.vat_number.required' => 'Le numéro de TVA de l’émetteur est requis.',
            'sender.iban.required' => 'L’IBAN de l’émetteur est requis.',
            'sender.address.line1.required' => 'L’adresse de l’émetteur est requise.',
            'sender.address.city.required' => 'La ville de l’émetteur est requise.',
            'sender.address.zip_code.required' => 'Le code postal de l’émetteur est requis.',
            'sender.address.country.required' => 'Le pays de l’émetteur est requis.',
            'sender.address.country.size' => 'Le pays de l’émetteur doit être un code ISO 2 lettres (ex: BE).',

            // Receiver
            'receiver.name.required' => 'Le nom du destinataire est requis.',
            'receiver.peppol_identifier.required' => 'L’identifiant Peppol du destinataire est requis.',
            'receiver.vat_number.required' => 'Le numéro de TVA du destinataire est requis.',
            'receiver.contact.email.email' => 'L’adresse email du destinataire doit être valide.',
            'receiver.address.line1.required' => 'L’adresse du destinataire est requise.',
            'receiver.address.city.required' => 'La ville du destinataire est requise.',
            'receiver.address.zip_code.required' => 'Le code postal du destinataire est requis.',
            'receiver.address.country.required' => 'Le pays du destinataire est requis.',
            'receiver.address.country.size' => 'Le pays du destinataire doit être un code ISO 2 lettres (ex: BE).',

            // Delivery
            'delivery.date.required' => 'La date de livraison est requise.',

            // Payment
            'payment.payment_delay.required' => 'Le délai de paiement est requis.',
            'payment.payment_delay.integer' => 'Le délai de paiement doit être un entier.',
            'payment.payment_delay.min' => 'Le délai de paiement doit être d’au moins 1 jour.',
            'payment.payment_delay.max' => 'Le délai de paiement ne peut pas dépasser 365 jours.',

            // Totals
            'totals.total_taxable_amount.required' => 'Le montant total hors TVA est requis.',
            'totals.total_taxable_amount.numeric' => 'Le montant hors TVA doit être un nombre.',
            'totals.total_amount.required' => 'Le montant total TTC est requis.',
            'totals.total_amount.numeric' => 'Le montant TTC doit être un nombre.',

            // Taxes
            'taxes.required' => 'Au moins une ligne de taxe est requise.',
            'taxes.array' => 'Le champ taxes doit être un tableau.',
            'taxes.*.vat_code.required' => 'Le code TVA est requis pour chaque taxe.',
            'taxes.*.tax_percentage.required' => 'Le pourcentage TVA est requis pour chaque taxe.',
            'taxes.*.taxable_amount.required' => 'Le montant hors TVA est requis pour chaque taxe.',
            'taxes.*.tax_amount.required' => 'Le montant TVA est requis pour chaque taxe.',

            // Lines
            'lines.required' => 'Au moins une ligne de facture est requise.',
            'lines.array' => 'Le champ lines doit être un tableau.',
            'lines.*.description.required' => 'La description est requise pour chaque ligne.',
            'lines.*.quantity.required' => 'La quantité est requise pour chaque ligne.',
            'lines.*.quantity.numeric' => 'La quantité doit être un nombre.',
            'lines.*.unit_price.required' => 'Le prix unitaire est requis pour chaque ligne.',
            'lines.*.unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'lines.*.taxable_amount.required' => 'Le montant hors TVA est requis pour chaque ligne.',
            'lines.*.taxable_amount.numeric' => 'Le montant hors TVA doit être un nombre.',
            'lines.*.vat_code.required' => 'Le code TVA est requis pour chaque ligne.',
            'lines.*.tax_percentage.required' => 'Le pourcentage TVA est requis pour chaque ligne.',
        ];
    }
}
