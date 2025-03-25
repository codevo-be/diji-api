<?php

namespace Diji\Billing\Models;

use App\Models\Meta;
use App\Traits\AutoloadRelationships;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RecurringInvoice extends Model
{
    public const STATUS_DRAFT = "draft";
    public const STATUS_ACTIVE = "active";
    public const STATUS_INACTIVE = "inactive";

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE
    ];

    use HasFactory, AutoloadRelationships, QuerySearch;

    protected $fillable = [
        "status",
        "start_date",
        "frequency",
        "next_run_at",
        "issuer",
        "recipient",
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

    protected array $searchable = ['subtotal', 'total', 'recipient->name', 'recipient->vat_number'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if(!$invoice->issuer){
                $invoice->issuer = Meta::getValue('tenant_billing_details');
            }
        });

        static::updating(function($invoice){
            if ($invoice->isDirty('status') && $invoice->getOriginal('status') === 'draft') {
                $requiredFields = ['issuer', 'recipient', 'total'];

                foreach ($requiredFields as $field) {
                    if (empty($invoice->$field)) {
                        throw ValidationException::withMessages([
                            $field => "Le champ {$field} est requis pour valider la facture."
                        ]);
                    }
                }
            }
        });

        static::deleting(function ($invoice) {
            if (!empty($invoice->identifier)) {
                throw new \Exception("Invoice cannot be deleted !");
            }

            $invoice->items()->delete();
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
