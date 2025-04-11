<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreSelfInvoiceRequest;
use Diji\Billing\Http\Requests\UpdateSelfInvoiceRequest;
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
            ->when($request->month, function ($query) use ($request) {
                return $query->whereMonth('date', $request->month);
            })
            ->orderByDesc('id');

        $self_invoices = $request->has('page')
            ? $query->paginate()
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

        $pdf = PDF::loadView('billing::self-invoice', [
            ...$self_invoice->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"] ?? null
        ]);

        try {
            $instanceBrevo = new Brevo();

            $instanceBrevo->attachments([
                [
                    "filename" => "autofacture-" . str_replace("/", "-", $self_invoice->identifier) . ".pdf",
                    "output" => $pdf->output()
                ]
            ]);

            $instanceBrevo
                ->to($request->to)
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

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'Invalid or empty ID list.'], 400);
        }

        $pdfFiles = array();

        foreach ($ids as $id) {
            try {
                $invoice = SelfInvoice::findOrFail($id)->load('items');
                $fileName = 'facture-' . str_replace("/", "-", $invoice->identifier) . '.pdf';

                $pdfString = PdfService::generateInvoice($invoice);

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
