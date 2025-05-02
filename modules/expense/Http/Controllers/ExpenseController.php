<?php

namespace Diji\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Contact\Resources\ContactResource;
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
        $contact = PeppolDocument::find($expense_id);

        return response()->json([
            'data' => new ContactResource($contact)
        ]);
    }

    // Todo : je suis en train de faire la méthode show pour afficher une dépense
}
