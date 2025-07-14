<?php

namespace Diji\Contact\Http\Controllers;

use Diji\Contact\Http\Requests\StoreContactRequest;
use Diji\Contact\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class ImportController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $file = $request->file('file');

        if (!$file->isValid() || $file->getClientOriginalExtension() !== 'csv') {
            return response()->json([
                'message' => "Fichier invalide !"
            ], 422);
        }

        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        $logs = [];

        foreach ($records as $record) {
            $data = [
                "firstname" => isset($record["firstname"]) ? ucfirst(strtolower(trim($record["firstname"]))) : null,
                "lastname" => isset($record["lastname"]) ? ucfirst(strtolower(trim($record["lastname"]))) : null,
                "email" => isset($record["email"]) ? strtolower(trim($record["email"])) : null,
                "phone" => isset($record["phone"]) ? strtolower(trim($record["phone"])) : null,
                "company_name" => isset($record["company_name"]) ? ucfirst(strtolower(trim($record["company_name"]))) : null,
                "vat_number" => isset($record["vat_number"]) ? str_replace('.', '', strtolower(trim($record["vat_number"]))) : null,
                "iban" => isset($record["phone"]) ? strtolower(trim($record["phone"])) : null,
                "billing_address" => [
                    "street" => isset($record["street"]) ? ucfirst(strtolower(trim($record["street"]))) : null,
                    "street_number" => isset($record["street_number"]) ? trim($record["street_number"]) : null,
                    "city" => isset($record["city"]) ? ucfirst(strtolower(trim($record["city"]))) : null,
                    "zipcode" => isset($record["zipcode"]) ? trim($record["zipcode"]) : null,
                    "country" => $this->normalizeCountry(isset($record["country"]) ? $record["country"] : null),
                ]
            ];

            if (isset($data["billing_address"]) && empty($data["billing_address"]["street_number"])) {
                if (preg_match('/\b\d+\s?[A-Za-z]?\b/', $data["billing_address"]["street"], $matches)) {
                    $data["billing_address"]["street_number"] = $matches[0];
                    $data["billing_address"]["street"] = preg_replace('/\b\d+\s?[A-Za-z]?\b/', '', $data["billing_address"]["street"]);
                    $data["billing_address"]["street"] = trim(preg_replace('/,\s?/', '', $data["billing_address"]["street"]));
                }
            }

            $validator = Validator::make($data, (new StoreContactRequest())->rules(), (new StoreContactRequest())->messages());

            if ($validator->fails()) {
                $logs[] = [
                    "message" => "{$data['firstname']} {$data['lastname']} : Erreur : " . implode(", ", array_map(fn($errors) => implode(", ", $errors), $validator->errors()->toArray())),
                    "type" => "error",
                    "time" => Carbon::now()->format('H:i:s')
                ];
                continue;
            } else {
                if (empty($data['email'])) {
                    $contact = Contact::create($data);
                } else {
                    $contact = Contact::firstOrCreate([
                        "email" => $data['email'],
                    ], $data);
                }

                if ($contact->wasRecentlyCreated) {
                    $logs[] = [
                        "message" => "{$contact->display_name} : Contact ajouté !",
                        "type" => "success",
                        "time" => Carbon::now()->format('H:i:s')
                    ];
                } else {
                    $logs[] = [
                        "message" => "{$contact->display_name} : Le contact existe déjà !",
                        "type" => "warning",
                        "time" => Carbon::now()->format('H:i:s')
                    ];
                }
            }
        }

        return response([
            "logs" => $logs
        ], 200);
    }

    /**
     * Normalize country name to ISO code
     */
    private function normalizeCountry(?string $country): ?string
    {
        if (empty($country)) {
            return null;
        }

        $country = strtolower(trim($country));
        $countries = config('countries.all');

        return $countries[$country] ?? $country;
    }
}
