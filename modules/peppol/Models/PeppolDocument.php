<?php

namespace Diji\Peppol\Models;

use Illuminate\Database\Eloquent\Model;

class PeppolDocument extends Model
{
    protected $table = 'peppol_documents';

    protected $fillable = [
        'document_identifier',
        'document_type',
        'sender',
        'recipient',
        'sender_address',
        'recipient_address',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'taxes',
        'total',
        'structured_communication',
        'lines',
        'raw_xml',
    ];

    protected $casts = [
        'sender' => 'array',
        'recipient' => 'array',
        'sender_address' => 'array',
        'recipient_address' => 'array',
        'taxes' => 'array',
        'lines' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'float',
        'total' => 'float',
    ];
}
