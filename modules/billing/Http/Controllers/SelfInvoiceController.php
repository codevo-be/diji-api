<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreSelfInvoiceRequest;
use Diji\Billing\Http\Requests\UpdateSelfInvoiceRequest;
use Diji\Billing\Jobs\ProcessBatchSelfInvoiceEmail;
use Diji\Billing\Models\SelfInvoice;
use Diji\Billing\Resources\SelfInvoiceResource;
use Diji\Billing\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SelfInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SelfInvoice::query();

        $query
            ->filter(['contact_id', 'status', 'date'])
            ->when(isset($request->month) &&
                is_string($request->month) &&
                trim($request->month) !== '' &&
                strtolower($request->month) !== 'undefined', function ($query) use($request){
                return $query->whereMonth('date', $request->month);
            })
            ->when(isset($request->date_from) &&
                isset($request->date_to), function ($query) use($request){
                return $query->whereBetween('date', [
                    $request->date_from,
                    $request->date_to
                ]);
            })
            ->orderByDesc('id');

        $self_invoices = $request->has('page')
            ? $query->paginate(50)
            : $query->get();

        return SelfInvoiceResource::collection($self_invoices)->response();
    }

    public function show(int $self_invoice_id): \Illuminate\Http\JsonResponse
    {
        $self_invoice = SelfInvoice::findOrFail($self_invoice_id);

        return response()->json([
            'data' => new SelfInvoiceResource($self_invoice)
        ]);
    }

    public function store(StoreSelfInvoiceRequest $request)
    {
        $data = $request->validated();

        $self_invoice = SelfInvoice::create($data);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $self_invoice->items()->create($item);
            }
            $self_invoice->load('items');
        }


        return response()->json([
            'data' => new SelfInvoiceResource($self_invoice),
        ], 201);
    }

    public function update(UpdateSelfInvoiceRequest $request, int $self_invoice_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $self_invoice = SelfInvoice::findOrFail($self_invoice_id);

        $self_invoice->update($data);

        return response()->json([
            'data' => new SelfInvoiceResource($self_invoice),
        ]);
    }

    public function destroy(int $self_invoice_id): \Illuminate\Http\Response
    {
        $self_invoice = SelfInvoice::findOrFail($self_invoice_id);

        $self_invoice->delete();

        return response()->noContent();
    }

    public function pdf(Request $request, int $self_invoice_id)
    {
        $self_invoice = SelfInvoice::findOrFail($self_invoice_id)->load('items');

        $pdfString = PdfService::generateSelfInvoice($self_invoice);

        return response($pdfString, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=autofacturation-" . str_replace("/", "-", $self_invoice->identifier) . ".pdf",
        ]);
    }

    public function email(Request $request, int $self_invoice_id)
    {
        $self_invoice = SelfInvoice::findOrFail($self_invoice_id)->load('items');

        $pdfString = PdfService::generateSelfInvoice($self_invoice);

        try {
            $instanceBrevo = new Brevo();

            $instanceBrevo->attachments([
                [
                    "filename" => "autofacture-" . str_replace("/", "-", $self_invoice->identifier) . ".pdf",
                    "output" => $pdfString
                ]
            ]);

            $instanceBrevo
                ->to($request->to, $self_invoice->recipient["name"])
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
            'self_invoice_ids' => 'required|array',
            'self_invoice_ids.*' => 'integer|exists:self_invoices,id',
        ]);

        $self_invoices = SelfInvoice::whereIn('id', $request->self_invoice_ids)->get();

        foreach ($self_invoices as $self_invoice) {
            try {
                $self_invoice->delete();
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->noContent();
    }

    public function batchPdf(Request $request)
    {
        $ids = $request->input('ids');
        $email = $request->input('email');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID list.'], 400);
        }

        try {
            $badStatusFiles = SelfInvoice::whereIn('id', $ids)
                ->where('status', 'draft')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [
                        $item->id => "Impossible de print le document avec le statut courant."
                    ];
                })
                ->toArray();

            $goodStatusFiles = array_diff($ids, array_keys($badStatusFiles));

            ProcessBatchSelfInvoiceEmail::dispatch($goodStatusFiles, $email);

            return response()->json([
                'sent' => $goodStatusFiles,
                'errors' => $badStatusFiles,
                'message' => 'Traitement lancé, vous recevrez un email avec les factures valides.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }
    }

    public function batchUpdate(Request $request)
    {
        $request->validate([
            'self_invoice_ids' => 'required|array',
            'self_invoice_ids.*' => 'integer|exists:self_invoices,id',
            'data' => 'required|array'
        ]);

        $failedInvoices = [];

        $self_invoices = SelfInvoice::whereIn('id', $request->self_invoice_ids)->get();

        foreach ($self_invoices as $self_invoice) {
            $self_invoice->fill($request->data);

            try {
                $self_invoice->save();
            } catch (ValidationException $e) {
                $failedInvoices[$self_invoice->id] = $e->errors();
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
}
