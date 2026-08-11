<?php

namespace Plugins\Asaas\Models;

use Illuminate\Database\Eloquent\Model;

class AsaasInvoice extends Model
{
    protected $table = 'asaas_invoices';

    protected $fillable = [
        'payment_id',
        'customer_id',
        'external_reference',
        'payment_method',
        'amount',
        'status',
        'paid_at',
        'invoice_url',
        'payment_data',
    ];

    protected $casts = [
        'paid_at'      => 'datetime',
        'payment_data' => 'array',
        'amount'       => 'decimal:2',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
