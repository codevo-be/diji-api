<?php

namespace Diji\Billing\Models;

use App\Models\Meta;
use App\Traits\AutoloadRelationships;
use App\Traits\Filterable;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Estimate extends Model
{
    public const STATUS_DRAFT = "draft";
    public const STATUS_PENDING = "pending";
    public const STATUS_ACCEPETED = "accepted";
    public const STATUS_REJECTED = "rejected";
    public const STATUS_EXPIRED = "expired";

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_ACCEPETED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED
    ];

    use HasFactory, AutoloadRelationships, QuerySearch, Filterable;

    protected $fillable = [
        "status",
        "issuer",
        "recipient",
        "date",
        "due_date",
        "subtotal",
        "taxes",
        "total",
        "contact_id"
    ];

    protected $casts = [
        'subtotal' => 'float',
        'taxes' => 'json',
        'total' => 'float',
        'issuer' => 'json',
        'recipient' => 'json'
    ];

    protected array $searchable = ['identifier', 'subtotal', 'total', 'date', 'recipient->name', 'recipient->vat_number'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->due_date) {
                $invoice->due_date = now()->addDays(30);
            }

            if (!$invoice->date) {
                $invoice->date = now();
            }

            if(!$invoice->issuer){
                $invoice->issuer = Meta::getValue('tenant_billing_details');
            }
        });

        static::updating(function($estimate){
            if ($estimate->isDirty('status') && $estimate->getOriginal('status') === 'draft') {
                $requiredFields = ['issuer', 'recipient', 'total'];

                foreach ($requiredFields as $field) {
                    if (empty($estimate->$field)) {
                        throw ValidationException::withMessages([
                            $field => "Le champ {$field} est requis pour valider la facture."
                        ]);
                    }
                }

                if (empty($estimate->identifier_number)) {
                    $year = now()->year;

                    $lastOffer = self::whereYear('date', $year)
                        ->whereNotNull('identifier_number')
                        ->orderBy('identifier_number', 'desc')
                        ->first();

                    $start = Meta::getValue('tenant_billing_details')['estimate_start_number'] ?? 1;

                    $nextNumber = $lastOffer
                        ? max($start, $lastOffer->identifier_number + 1)
                        : $start;

                    $estimate->identifier_number = $nextNumber;
                    $estimate->identifier = sprintf('%d/%03d', $year, $nextNumber);
                }
            }

            if ($estimate->isDirty('date')) {
                $estimate->due_date = Carbon::parse($estimate->date)->addDays(30);
            }
        });

        static::deleting(function ($estimate) {
            $estimate->items()->delete();
        });
    }

    public function items()
    {
        return $this->morphMany(BillingItem::class, 'model')->orderBy("position");
    }

    public function contact()
    {
        return $this->belongsTo(\Diji\Contact\Models\Contact::class, 'contact_id');
    }
}
