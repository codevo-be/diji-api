<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreCreditNoteRequest;
use Diji\Billing\Http\Requests\UpdateCreditNoteRequest;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Resources\CreditNoteResource;
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
                strtolower($request->month) !== 'undefined', function ($query) use($request){
                return $query->whereMonth('date', $request->month);
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
                ->to($request->to)
                ->cc($request->cc ?? null)
                ->subject($request->subject ?? '')
                ->view("billing::email", ["body" => $request->body])
                ->send();
        }catch (\Exception $e){
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
            try{
                $credit_note->delete();
            }catch (\Exception $e){
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

    public function batchPdf(Request $request)
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID list.'], 400);
        }

        $pdfFiles = array();

        foreach ($ids as $id) {
            try {
                $credit_notes = CreditNote::findOrFail($id)->load('items');
                $fileName = 'facture-' . str_replace("/", "-", $credit_notes->identifier) . '.pdf';

                $pdfString = PdfService::generateCreditNote($credit_notes);

                $pdfFiles[$fileName] = $pdfString;

            } catch (\Exception $e) {
                return response()->json([
                    "message" => $e->getMessage()
                ], 422);
            }
        }

        try {
            $zipPath = ZipService::createTempZip($pdfFiles);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }
    }
}
