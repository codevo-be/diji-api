<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Services\Brevo;
use App\Services\ZipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Diji\Billing\Http\Requests\StoreEstimateRequest;
use Diji\Billing\Http\Requests\StoreInvoiceRequest;
use Diji\Billing\Http\Requests\UpdateEstimateRequest;
use Diji\Billing\Http\Requests\UpdateInvoiceRequest;
use Diji\Billing\Models\Estimate;
use Diji\Billing\Models\Invoice;
use Diji\Billing\Resources\EstimateResource;
use Diji\Billing\Resources\InvoiceResource;
use Diji\Billing\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EstimateController extends Controller
{
    public function index(Request $request)
    {
        $query = Estimate::query();

        $query
            ->filter(['contact_id', 'status', 'date'])
            ->when(isset($request->month) &&
                is_string($request->month) &&
                trim($request->month) !== '' &&
                strtolower($request->month) !== 'undefined', function ($query) use ($request) {
                return $query->whereMonth('date', $request->month);
            })
            ->orderByDesc('id');

        $estimates = $request->has('page')
            ? $query->paginate(50)
            : $query->get();

        return EstimateResource::collection($estimates)->response();
    }

    public function show(int $estimate_id): \Illuminate\Http\JsonResponse
    {
        $estimate = Estimate::findOrFail($estimate_id);

        return response()->json([
            'data' => new EstimateResource($estimate)
        ]);
    }

    public function store(StoreEstimateRequest $request)
    {
        $data = $request->validated();

        $estimate = Estimate::create($data);

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $estimate->items()->create($item);
            }
            $estimate->load('items');
        }


        return response()->json([
            'data' => new EstimateResource($estimate),
        ], 201);
    }

    public function update(UpdateEstimateRequest $request, int $estimate_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $estimate = Estimate::findOrFail($estimate_id);

        $estimate->update($data);

        return response()->json([
            'data' => new EstimateResource($estimate),
        ]);
    }

    public function destroy(int $estimate_id): \Illuminate\Http\Response
    {
        $estimate = Estimate::findOrFail($estimate_id);

        $estimate->delete();

        return response()->noContent();
    }

    public function pdf(Request $request, int $estimate_id)
    {
        $estimate = Estimate::findOrFail($estimate_id)->load('items');

        $pdfString = PdfService::generateEstimate($estimate);

        return response($pdfString, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=devis-" . str_replace("/", "-", $estimate->identifier) . ".pdf",
        ]);
    }

    public function email(Request $request, int $estimate_id)
    {
        $estimate = Estimate::findOrFail($estimate_id)->load('items');

        $logo = tenant()["settings"]['logo'];

        $pdf = PDF::loadView('billing::estimate', [
            ...$estimate->toArray(),
            "logo" => $logo
        ]);

        try {
            $instanceBrevo = new Brevo();

            $instanceBrevo->attachments([
                [
                    "filename" => "devis-" . str_replace("/", "-", $estimate->identifier) . ".pdf",
                    "output" => $pdf->output()
                ]
            ]);

            $instanceBrevo
                ->to($request->to, $estimate->recipient["name"])
                ->cc($request->cc ?? null)
                ->subject($request->subject ?? '')
                ->view("billing::email", ["estimate" => $estimate, "logo" => $logo, "body" => $request->body])
                ->send();
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 422);
        }


        return response()->noContent();
    }
}
