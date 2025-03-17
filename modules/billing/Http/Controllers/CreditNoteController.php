<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreCreditNoteRequest;
use Diji\Billing\Http\Requests\UpdateCreditNoteRequest;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Resources\CreditNoteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = CreditNote::query();

        if($request->filled('contact_id')){
            $query->where("contact_id", $request->contact_id);
        }

        if($request->filled('status')){
            $query->where("status", $request->status);
        }

        return CreditNoteResource::collection($query->get())->response();
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

        $pdf = PDF::loadView('billing::credit-note', [
            ...$credit_note->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"]
        ]);

        return $pdf->stream("note-de-credit-$credit_note->identifier_number.pdf");
    }

    public function email(Request $request, int $credit_note_id)
    {
        $credit_note = CreditNote::findOrFail($credit_note_id)->load('items');

        $pdf = PDF::loadView('billing::credit-note', [
            ...$credit_note->toArray(),
            "logo" => Meta::getValue('tenant_billing_details')["logo"]
        ]);

        try {
            Mail::send('billing::email', ["body" => $request->body], function ($message) use($request, $pdf) {
                $tenant = tenant();
                $message->from([env('MAIL_FROM_ADDRESS'), $tenant->name]);
                $message->to($request->to);

                if($request->subject){
                    $message->subject($request->subject);
                }

                if($request->cc){
                    $message->cc($request->cc);
                }

                $message->attachData($pdf->output(), "aa.pdf", [
                    "mime" => 'application/pdf'
                ]);
            });
        }catch (\Exception $e){
            return response()->json([
                "message" => $e->getMessage()
            ]);
        }


        return response()->noContent();
    }
}
