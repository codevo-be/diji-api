<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Billing\Http\Requests\StoreRecurringInvoiceRequest;
use Diji\Billing\Http\Requests\UpdateRecurringInvoiceRequest;
use Diji\Billing\Resources\RecurringInvoiceResource;
use Diji\Billing\Models\RecurringInvoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringInvoice::query();

        $query
            ->filter(['contact_id', 'status', 'date'])
            ->orderByDesc('id');

        $invoices = $request->has('page')
            ? $query->paginate()
            : $query->get();

        return RecurringInvoiceResource::collection($invoices)->response();
    }

    public function show(int $recurring_invoice_id): \Illuminate\Http\JsonResponse
    {
        $invoice = RecurringInvoice::findOrFail($recurring_invoice_id);

        return response()->json([
            'data' => new RecurringInvoiceResource($invoice)
        ]);
    }

    public function store(StoreRecurringInvoiceRequest $request)
    {
        $data = $request->validated();

        $recurring_invoice = RecurringInvoice::create($data);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $recurring_invoice->items()->create($item);
            }
            $recurring_invoice->load('items');
        }


        return response()->json([
            'data' => new RecurringInvoiceResource($recurring_invoice),
        ], 201);
    }

    public function update(UpdateRecurringInvoiceRequest $request, int $recurring_invoice_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $recurring_invoice = RecurringInvoice::findOrFail($recurring_invoice_id);

        $recurring_invoice->update($data);

        return response()->json([
            'data' => new RecurringInvoiceResource($recurring_invoice),
        ]);
    }

    public function destroy(int $recurring_invoice_id): \Illuminate\Http\Response
    {
        $recurring_invoice = RecurringInvoice::findOrFail($recurring_invoice_id);

        $recurring_invoice->delete();

        return response()->noContent();
    }
}
