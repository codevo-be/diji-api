<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Helpers\PeppolPayloadDTOBuilder;
use Diji\Billing\Http\Requests\StoreCreditNoteRequest;
use Diji\Billing\Http\Requests\UpdateCreditNoteRequest;
use Diji\Billing\Jobs\ProcessBatchCreditNotesEmail;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Resources\CreditNoteResource;

use Diji\Peppol\Helpers\PeppolBuilder;
use Diji\Peppol\Services\PeppolService;
use Diji\Billing\Services\PdfService;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditNote::query();

        $query
            ->filter(['contact_id', 'status', 'date'])
            ->when(isset($request->month) &&
                is_string($request->month) &&
                trim($request->month) !== '' &&
                strtolower($request->month) !== 'undefined', function ($query) use ($request) {
                return $query->whereMonth('date', $request->month);
            })
            ->when(isset($request->date_from) &&
                isset($request->date_to), function ($query) use ($request) {
                return $query->whereBetween('date', [
                    $request->date_from,
                    $request->date_to
                ]);
            })
            ->orderByDesc('id');

        $credit_notes = $request->has('page')
            ? $query->paginate(50)
            : $query->get();

        return CreditNoteResource::collection($credit_notes)->response();
    }

    public function show(int $credit_note_id): \Illuminate\Http\JsonResponse
    {
        $credit_note_id = CreditNote::findOrFail($credit_note_id);

        return response()->json([
            'data' => new CreditNoteResource($credit_note_id)
        ]);
    }

    public function store(StoreCreditNoteRequest $request)
    {
        $data = $request->validated();

        $credit_note = CreditNote::create($data);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $credit_note->items()->create($item);
            }
            $credit_note->load('items');
        }


        return response()->json([
            'data' => new CreditNoteResource($credit_note),
        ], 201);
    }

    public function update(UpdateCreditNoteRequest $request, int $credit_note_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $credit_note = CreditNote::findOrFail($credit_note_id);

        $credit_note->update($data);

        return response()->json([
            'data' => new CreditNoteResource($credit_note),
        ]);
    }

    public function destroy(int $credit_note_id): \Illuminate\Http\Response
    {
        $credit_note = CreditNote::findOrFail($credit_note_id);

        $credit_note->delete();

        return response()->noContent();
    }

    public function pdf(Request $request, int $credit_note_id)
    {
        $credit_note = CreditNote::findOrFail($credit_note_id)->load('items');

        $pdfString = PdfService::generateCreditNote($credit_note);

        return response($pdfString, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=note-de-credit-" . str_replace("/", "-", $credit_note->identifier) . ".pdf",
        ]);
    }

    public function email(Request $request, int $credit_note_id)
    {
        $credit_note = CreditNote::findOrFail($credit_note_id)->load('items');

        $pdfString = PdfService::generateCreditNote($credit_note);

        try {
            $instanceBrevo = new Brevo();

            $instanceBrevo->attachments([
                [
                    "filename" => "note-de-crédit-" . str_replace("/", "-", $credit_note->identifier) . ".pdf",
                    "output" => $pdfString
                ]
            ]);

            $instanceBrevo
                ->to($request->to, $credit_note->recipient["name"])
                ->cc($request->cc ?? null)
                ->subject($request->subject ?? '')
                ->view("billing::email", ["body" => $request->body])
                ->send();
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ]);
        }


        return response()->noContent();
    }

    public function batchDestroy(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            'credit_note_ids' => 'required|array',
            'credit_note_ids.*' => 'integer|exists:credit_notes,id',
        ]);

        $credit_notes = CreditNote::whereIn('id', $request->credit_note_ids)->get();

        foreach ($credit_notes as $credit_note) {
            try {
                $credit_note->delete();
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->noContent();
    }

    public function batchUpdate(Request $request)
    {
        $request->validate([
            'credit_note_ids' => 'required|array',
            'credit_note_ids.*' => 'integer|exists:credit_notes,id',
            'data' => 'required|array'
        ]);

        $failedInvoices = [];

        $credit_notes = CreditNote::whereIn('id', $request->credit_note_ids)->get();

        foreach ($credit_notes as $credit_note) {
            $credit_note->fill($request->data);

            try {
                $credit_note->save();
            } catch (ValidationException $e) {
                $failedInvoices[$credit_note->id] = $e->errors();
                continue;
            }
        }

        if (!empty($failedInvoices)) {
            return response()->json([
                'message' => 'Some invoices failed to update.',
                'errors' => $failedInvoices
            ], 422);
        }

        return response()->noContent();
    }

    /**
     * Envoie une note de crédit au réseau Peppol via Digiteal.
     * Si la facture d’origine est liée, utilise son identifiant comme référence.
     * Plusieurs tentatives sont effectuées avec différents payloads si nécessaire.
     */
    public function sendToPeppol(int $credit_note_id)
    {
        $creditNote = CreditNote::findOrFail($credit_note_id)->load('items', 'contact');
        $invoiceIdentifier = $creditNote->invoice?->identifier ?? 'Invoice';
        $payloads = PeppolPayloadDTOBuilder::fromCreditNote(new CreditNoteResource($creditNote), $invoiceIdentifier);

        foreach ($payloads as $index => $payload) {
            $xml = (new PeppolBuilder())
                ->withPayload($payload)
                ->build();

            $filename = "peppol_credit_note_try_{$index}.xml";

            $result = (new PeppolService())->sendInvoice($xml, $filename);

            $internalResponse = json_decode($result['response'] ?? '', true);

            if (isset($internalResponse['status']) && $internalResponse['status'] === 'OK') {
                return response()->json([
                    'message' => 'Note de crédit Peppol envoyée avec succès.',
                    'digiteal_response' => $result,
                    'filename' => $filename,
                ]);
            }

            if (!empty($internalResponse['status']) && $internalResponse['status'] !== 'RECIPIENT_NOT_IN_PEPPOL') {
                return response()->json([
                    'error' => true,
                    'message' => $internalResponse['message'] ?? 'Erreur inconnue lors de l’envoi de la note de crédit.',
                    'digiteal_response' => $result,
                ], 400);
            }
        }

        return response()->json([
            'error' => true,
            'message' => 'Aucun identifiant Peppol n’a permis d’envoyer la note de crédit.',
            'digiteal_response' => $result ?? null,
        ], 400);
    }

    public function batchPdf(Request $request)
    {
        $ids = $request->input('ids');
        $email = $request->input('email');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID list.'], 400);
        }

        try {
            $badStatusFiles = CreditNote::whereIn('id', $ids)
                ->where('status', 'draft')
                ->get(['id'])
                ->mapWithKeys(function ($item) {
                    return [$item->id => "Impossible de print le document avec le statut courant."];
                })
                ->toArray();

            $goodStatusFiles = array_diff($ids, array_keys($badStatusFiles));

            ProcessBatchCreditNotesEmail::dispatch($goodStatusFiles, $email);

            return response()->json([
                'sent' => $goodStatusFiles,
                'errors' => $badStatusFiles,
                'message' => 'Traitement lancé, vous recevrez un email avec les notes de crédits valides.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }
    }
}
