<?php

namespace Diji\Billing\Models;

use App\Models\Meta;
use App\Traits\AutoloadRelationships;
use App\Traits\QuerySearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreditNote extends Model
{
    public const STATUS_DRAFT = "draft";
    public const STATUS_PENDING = "pending";
    public const STATUS_REFUND = "refund";

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_REFUND
    ];

    use HasFactory, AutoloadRelationships, QuerySearch;

    protected $fillable = [
        "invoice_id",
        "issuer",
        "recipient",
        "date",
        "status",
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

    protected array $searchable = ['date', 'subtotal', 'total', 'contact_name', 'email', 'date']; // TODO search on recipient

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($credit_note) {
            if (!$credit_note->date) {
                $credit_note->date = now();
            }

            if(!$credit_note->issuer){
                $credit_note->issuer = Meta::getValue('credit_note_default_issuer'); // TODO UPDATE FORM Billing_...
            }
        });

        static::updating(function($credit_note){
            if ($credit_note->isDirty('status') && $credit_note->getOriginal('status') === 'draft') {
                $requiredFields = ['issuer', 'recipient', 'total'];

                foreach ($requiredFields as $field) {
                    if (empty($credit_note->$field)) {
                        throw ValidationException::withMessages([
                            $field => "Le champ {$field} est requis pour valider la facture."
                        ]);
                    }
                }

                if (empty($credit_note->identifier_number)) {
                    $year = now()->year;

                    $lastOffer = self::whereYear('date', $year)
                        ->whereNotNull('identifier_number')
                        ->orderBy('identifier_number', 'desc')
                        ->first();

                    $nextNumber = $lastOffer ? $lastOffer->identifier_number + 1 : 1;

                    $credit_note->identifier_number = $nextNumber;
                    $credit_note->identifier = sprintf('%d/%03d', $year, $nextNumber);
                }
            }
        });

        static::deleting(function ($credit_note) {
            if (!empty($credit_note->identifier)) {
                throw new \Exception("Invoice cannot be deleted !");
            }

            $credit_note->items()->delete();
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

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'model');
    }
}
