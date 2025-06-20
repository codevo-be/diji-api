<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreInvoiceRequest;
use Diji\Billing\Http\Requests\UpdateInvoiceRequest;
use Diji\Billing\Jobs\ProcessBatchInvoiceEmail;
use Diji\Billing\Models\Invoice;
use Diji\Billing\Resources\InvoiceResource;
use Diji\Billing\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::query();

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

        $invoices = $request->has('page')
            ? $query->paginate(50)
            : $query->get();

        return InvoiceResource::collection($invoices)->response();
    }

    public function show(int $invoice_id): \Illuminate\Http\JsonResponse
    {
        $invoice = Invoice::findOrFail($invoice_id);

        return response()->json([
            'data' => new InvoiceResource($invoice)
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = Invoice::create($data);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $invoice->items()->create($item);
            }
            $invoice->load('items');
        }


        return response()->json([
            'data' => new InvoiceResource($invoice),
        ], 201);
    }

    public function update(UpdateInvoiceRequest $request, int $invoice_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $invoice = Invoice::findOrFail($invoice_id);

        $invoice->update($data);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function destroy(int $invoice_id): \Illuminate\Http\Response
    {
        $invoice = Invoice::findOrFail($invoice_id);

        $invoice->delete();

        return response()->noContent();
    }

    public function batchDestroy(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        $invoices = Invoice::whereIn('id', $request->invoice_ids)->get();

        foreach ($invoices as $invoice) {
            try{
                $invoice->delete();
            }catch (\Exception $e){
                continue;
            }
        }

        return response()->noContent();
    }

    public function batchUpdate(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'integer|exists:invoices,id',
            'data' => 'required|array'
        ]);

        $failedInvoices = [];

        $invoices = Invoice::whereIn('id', $request->invoice_ids)->get();

        foreach ($invoices as $invoice) {
            $invoice->fill($request->data);

            try {
                $invoice->save();
            } catch (ValidationException $e) {
                $failedInvoices[$invoice->id] = $e->errors();
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
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        $ids = $request->input('ids');
        $email = $request->input('email');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID list.'], 400);
        }

        try {
            $badStatusFiles = Invoice::whereIn('id', $ids)
                ->where('status', 'draft')
                ->get('id')
                ->mapWithKeys(function ($item) {
                    return [
                        $item->id => "Impossible de print le document avec le statut courant."
                    ];
                })
                ->toArray();

            $goodStatusFiles = array_diff($ids, array_keys($badStatusFiles));

            ProcessBatchInvoiceEmail::dispatch($goodStatusFiles, $email);

            return response()->json([
                'sent' => $goodStatusFiles,
                'errors' => $badStatusFiles,
                'message' => 'Traitement lancé, vous recevrez un email avec les factures valides.'
            ]);
        } catch (\Exception $e) {
            Log::info("Zip");
            Log::info($e->getMessage());
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }
    }

    public function pdf(Request $request, int $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id)->load('items');

        $pdfString = PdfService::generateInvoice($invoice);

        return response($pdfString, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=facture-" . str_replace("/", "-", $invoice->identifier) . ".pdf",
        ]);
    }

    public function email(Request $request, int $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id)->load('items');

        $qrcode = \Diji\Billing\Helpers\Invoice::generateQrCode($invoice->issuer["name"], $invoice->issuer["iban"], $invoice->total, $invoice->structured_communication);
        $logo = Meta::getValue('tenant_billing_details')['logo'];

        $pdf = PDF::loadView('billing::invoice', [
            ...$invoice->toArray(),
            "logo" => $logo,
            "qrcode" => $qrcode
        ]);

        try {
            $instanceBrevo = new Brevo();

            $instanceBrevo->attachments([
                [
                    "filename" => "facture-" . str_replace("/", "-", $invoice->identifier) . ".pdf",
                    "output" => $pdf->output()
                ]
            ]);

            $instanceBrevo
                ->to($request->to, $invoice->recipient["name"])
                ->cc($request->cc ?? null)
                ->subject($request->subject ?? '')
                ->view("billing::email-invoice", ["invoice" => $invoice, "logo" => $logo,  "qrcode" => $qrcode,  "body" => $request->body])
                ->send();
        }catch (\Exception $e){
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }


        return response()->noContent();
    }
}
