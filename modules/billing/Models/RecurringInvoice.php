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

    use HasFactory, AutoloadRelationships, QuerySearch, Filterable;

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

    protected array $searchable = ['identifier', 'subtotal', 'total', 'recipient->name', 'recipient->vat_number'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->issuer) {
                $invoice->issuer = Meta::getValue('tenant_billing_details');
            }
        });

        static::updating(function ($invoice) {
            if ($invoice->isDirty('status') && $invoice->getOriginal('status') === 'draft') {
                $requiredFields = ['issuer', 'recipient', 'total', 'next_run_at'];

                foreach ($requiredFields as $field) {
                    if (empty($invoice->$field)) {
                        throw ValidationException::withMessages([
                            $field => "Le champ {$field} est requis pour valider la facture."
                        ]);
                    }
                }
            }

            $invoice->next_run_at = self::generateNexRunDate($invoice);
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

    public static function generateNexRunDate(RecurringInvoice $invoice): ?Carbon
    {
        $startDate = Carbon::parse($invoice->start_date);
        $now = Carbon::now();

        $nextRun = $startDate->copy();

        switch ($invoice->frequency) {
            case 'daily':
                while ($nextRun->lessThanOrEqualTo($now)) {
                    $nextRun->addDay();
                }
                break;
            case 'weekly':
                while ($nextRun->lessThanOrEqualTo($now)) {
                    $nextRun->addWeek();
                }
                break;
            case 'monthly':
                while ($nextRun->lessThanOrEqualTo($now)) {
                    $nextRun->addMonth();
                }
                break;
            case 'yearly':
                while ($nextRun->lessThanOrEqualTo($now)) {
                    $nextRun->addYear();
                }
                break;
            default:
                $nextRun = null;
                break;
        }

        return $nextRun;
    }
}
