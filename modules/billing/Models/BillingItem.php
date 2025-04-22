<?php

namespace Diji\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingItem extends Model
{
    public const MODEL_TYPES = [
        "estimate" => Estimate::class,
        "invoice" => Invoice::class,
        "credit_note" => CreditNote::class,
        "self_invoice" => SelfInvoice::class,
        "recurring_invoice" => RecurringInvoice::class
    ];

    protected $fillable = ["type", "position", "model_type", "model_id", "name", "quantity", "vat", "cost", "retail"];

    protected $casts = [
        "quantity" => "float",
        "cost" => "json",
        "retail" => "json"
    ];

    public static function findParent(Request $request)
    {
        foreach (self::MODEL_TYPES as $param => $model) {
            if ($request->route($param)) {
                return $model::findOrFail($request->route($param));
            }
        }

        throw new \InvalidArgumentException("Invalid parent type.");
    }

    protected static function booted()
    {
        parent::boot();

        static::creating(function (BillingItem $item) {
            if(!$item->position){
                $item->position = BillingItem::where('model_type', $item->model_type)
                        ->where('model_id', $item->model_id)
                        ->count() + 1;
            }

            if($item->type !== "product"){
                $item->retail = null;
                $item->cost = null;
                $item->vat = null;
                $item->quantity = 0;
            }

            if($item->type === "product" && $item->retail) {
                $retail_tax = (floatval($item->retail["subtotal"]) * $item->vat) / 100;
                $item->retail = [
                    "subtotal" => floatval($item->retail["subtotal"]),
                    "tax" => $retail_tax,
                    "total" => floatval($item->retail["subtotal"]) + $retail_tax
                ];
            }

            if($item->type === "product" && $item->cost) {
                $cost_tax = (floatval($item->cost["subtotal"]) * $item->vat) / 100;
                $item->cost = [
                    "subtotal" => floatval($item->cost["subtotal"]),
                    "tax" => $cost_tax,
                    "total" => floatval($item->cost["subtotal"]) + $cost_tax
                ];
            }
        });

        static::updating(function (BillingItem $item) {
            $oldOrder = $item->getOriginal('position');
            $newOrder = $item->position;

            if($item->type !== "product"){
                $item->retail = null;
                $item->cost = null;
                $item->vat = null;
                $item->quantity = 0;
            }

            if($item->type === "product" && $item->retail) {
                $retail_tax = (floatval($item->retail["subtotal"]) * $item->vat) / 100;
                $item->retail = [
                    "subtotal" => floatval($item->retail["subtotal"]),
                    "tax" => $retail_tax,
                    "total" => floatval($item->retail["subtotal"]) + $retail_tax
                ];
            }

            if($item->type === "product" && $item->cost) {
                $cost_tax = (floatval($item->cost["subtotal"]) * $item->vat) / 100;
                $item->cost = [
                    "subtotal" => floatval($item->cost["subtotal"]),
                    "tax" => $cost_tax,
                    "total" => floatval($item->cost["subtotal"]) + $cost_tax
                ];
            }

            if ($oldOrder === $newOrder) {
                return;
            }

            if ($oldOrder < $newOrder) {
                BillingItem::where('model_type', $item->model_type)
                    ->where('model_id', $item->model_id)
                    ->whereBetween('position', [$oldOrder + 1, $newOrder])
                    ->decrement('position');
            } else {
                BillingItem::where('model_type', $item->model_type)
                    ->where('model_id', $item->model_id)
                    ->whereBetween('position', [$newOrder, $oldOrder - 1])
                    ->increment('position');
            }
        });

        static::saved(function ($model) {
            static::recalculateModelTotals($model);
        });

        static::deleted(function ($model) {
            static::recalculateModelTotals($model);
        });
    }

    public function model()
    {
        return $this->morphTo();
    }

    private static function recalculateModelTotals($model): void
    {
        if(!$model->retail | $model->type !== "product"){
            return;
        }

        $parentModel = $model->model;

        if(!$parentModel){
            throw new \Error("Une erreur est survenue avec le devis !");
        }

        $items = $parentModel->items;

        $amount = $items->reduce(function ($carry, $item) {
            if(!$item->retail){
                return $carry;
            }

            $subtotal = floatval($item->retail["subtotal"]) * $item->quantity;
            $tax = $subtotal * (floatval($item->vat) / 100);

            $result = [
                "subtotal" => $subtotal,
                "tax" => $tax,
                "total" => $subtotal + $tax
            ];

            if (!isset($carry['taxes'][$item->vat])) {
                $carry['taxes'][$item->vat] = 0;
            }

            $carry['taxes'][$item->vat] += ($result['tax'] ?? 0);

            return [
                "subtotal" => ($carry['subtotal'] ?? 0) + ($result['subtotal'] ?? 0),
                "taxes" => $carry['taxes'],
                "total" => ($carry['total'] ?? 0) + ($result['total'] ?? 0)
            ];
        }, ['subtotal' => 0, 'taxes' => [], 'total' => 0]);

        $parentModel->update($amount);
    }
}
