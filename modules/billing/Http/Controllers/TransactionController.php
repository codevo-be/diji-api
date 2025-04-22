<?php

namespace Diji\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Diji\Billing\Http\Requests\UpdateTransactionRequest;
use Diji\Billing\Models\Transaction;
use Diji\Billing\Resources\TransactionResource;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query();

        $query
            ->when(isset($request->month) &&
                is_string($request->month) &&
                trim($request->month) !== '' &&
                strtolower($request->month) !== 'undefined', function ($query) use($request){
                return $query->whereMonth('date', $request->month);
            })->when(
                isset($request->expenseOnly) && $request->expenseOnly,
                function ($query) {
                    return $query->where('amount', '<', 0);
                }
            )->orderBy('date', 'desc');

        $transactions = $request->has('page')
            ? $query->paginate(50)
            : $query->get();

        return TransactionResource::collection($transactions)->response();
    }

    public function show(int $transaction_id)
    {
        $transaction = Transaction::findOrFail($transaction_id);

        return response()->json([
            'data' => new TransactionResource($transaction)
        ]);
    }

    public function update(UpdateTransactionRequest $request, int $transaction_id): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $transaction = Transaction::findOrFail($transaction_id);

        $transaction->update($data);

        return response()->json([
            'data' => new TransactionResource($transaction),
        ]);
    }
}
