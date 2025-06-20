<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function csv(Request $request)
    {
        $request->validate([
            "head" => "array",
            "items" => "array",
            "filename" => "required|string"
        ], [
            "head.required" => "Le header est requis",
            "head.array" => "Le header doit être un tableau",
            "items.required" => "Les items sont requis",
            "items.array" => "Les items doivent être un tableau",
            "filename.required" => "Le nom du fichier est requis",
            "filename.string" => "Le nom du fichier doit être une chaîne de caractères"
        ]);

        $data = [
            $request->head,
            ...$request->items
        ];

        foreach ($data as $row) {
            foreach ($row as $value) {
                if ($this->isMonetaryValue($value)) {
                    $value = $this->convertToFloat($value);
                }
            }
        }

        $csv = \League\Csv\Writer::createFromString('');
        $csv->setDelimiter(';');
        $csv->setOutputBOM(\League\Csv\Writer::BOM_UTF8);
        $csv->insertAll($data);

        return response($csv->toString())
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $request->filename . '"');
    }

    private function isMonetaryValue($value): bool
    {
        return str_ends_with($value, "€");
    }

    private function convertToFloat($value)
    {
        $value = preg_replace('/\s*€/', '', $value);
        $value = str_replace(',', '.', $value);
        $floatValue = (float)$value;
        return str_replace('.', ',', number_format($floatValue, 2, '.', ''));
    }
}
