<?php

namespace Diji\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Expense\Resources\ExpenseResource;
use Diji\Peppol\Models\PeppolDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
        $expense = PeppolDocument::find($expense_id);

        return response()->json([
            'data' => new ExpenseResource($expense)
        ]);
    }
}
