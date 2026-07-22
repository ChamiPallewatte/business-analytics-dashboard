<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'currency',
        'client_id',
        'service_id',
        'invoice_date',
        'due_date',
        'amount',
        'vat_percent',
        'vat_amount',
        'total_amount',
        'payment_terms',
        'status',
        'remarks',
        'items',
        'vat_period',
        'vat_due_month',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'items' => 'array',
    ];

    public static array $terms = [
        'Monthly' => 'Monthly',
        'Quarterly' => 'Quarterly',
        'Yearly' => 'Yearly',
        'Milestone' => 'Milestone',
        'One-Time' => 'One-Time',
    ];

    public static array $statuses = [
        'Draft' => 'Draft',
        'Sent' => 'Sent',
        'Paid' => 'Paid',
        'Partially Paid' => 'Partially Paid',
        'Overdue' => 'Overdue',
        'Cancelled' => 'Cancelled',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Auto-calculate VAT, Total and auto-generate invoice number.
     */
    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $year = date('Y');
                $latest = static::whereYear('created_at', $year)->latest()->first();
                $seq = $latest ? ((int) substr($latest->invoice_number, -4)) + 1 : 1;
                $invoice->invoice_number = sprintf('INV-%s-%04d', $year, $seq);
            }
        });

        static::saving(function (Invoice $invoice) {
            $invoice->vat_amount = round($invoice->amount * ($invoice->vat_percent / 100), 2);
            $invoice->total_amount = $invoice->amount + $invoice->vat_amount;
        });
    }

    /**
     * Recalculate status and balance based on associated payments.
     */
    public function updatePaymentStatus()
    {
        $totalPaid = $this->payments()->sum('amount');
        $balance = max(0, $this->total_amount - $totalPaid);

        if ($balance <= 0) {
            $this->status = 'Paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'Partially Paid';
        } else {
            // Check if overdue
            if ($this->due_date && $this->due_date->isPast() && $this->status !== 'Cancelled') {
                $this->status = 'Overdue';
            } else {
                $this->status = 'Sent';
            }
        }

        $this->save();

        // Also update parent service if linked
        if ($this->service) {
            $this->service->paid_amount = Payment::whereHas('invoice', function ($q) {
                $q->where('service_id', $this->service_id);
            })->sum('amount');
            $this->service->save();
        }
    }
}
