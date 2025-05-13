<?php

namespace Diji\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceEmailLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'recipient_email',
        'sent_at',
        'extended_date',
        'success',
        'error_message'
    ];
}
