<?php

namespace Diji\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Expense\Resources\ExpenseResource;
use Diji\Peppol\Models\PeppolDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Log::info("Expense controller : Index");
        $query = PeppolDocument::query();

        $documents = $request->has('page')
            ? $query->paginate()
            : $query->get();

        return response()->json([
            'data' => $documents,
        ]);
    }

    public function show(int $expense_id): JsonResponse
    {
        Log::info("Expense controller : Show");

        $expense = PeppolDocument::find($expense_id);

        Log::info("Expense", [
            'expense' => $expense,
        ]);

        return response()->json([
            'data' => new ExpenseResource($expense)
        ]);
    }
}
