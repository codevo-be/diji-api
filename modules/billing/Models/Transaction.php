<?php

namespace Diji\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = "pending";
    public const STATUS_RECONCILED = "reconciled";
    public const STATUS_MISSING = "missing";

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RECONCILED,
        self::STATUS_MISSING
    ];

    protected $fillable = ['status', 'date', 'model_type', 'model_id', 'structured_communication', 'creditor_name', "creditor_account", "debtor_name", "debtor_account", "amount", "response", "transaction_id"];

    protected $casts = [
        "response" => "array",
        "amount" => "float"
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($transaction) {
            if (($transaction->isDirty('model_type') || $transaction->isDirty('model_id')) && !is_null($transaction->model)) {
                $model = $transaction->model;

                $transactions_amount = $model->transactions->sum('amount');

                if(floatval($transactions_amount) >= floatval($model->total)){
                    $model->status = Invoice::STATUS_PAYED;
                    $model->save();

                    Log::channel('transaction')->info("Tenant : " . tenant()->id);
                    Log::channel('transaction')->info("Facture $model->identifier (id:$model->id) payé !");
                }
            }
        });
    }

    public function model()
    {
        return $this->morphTo();
    }
}
