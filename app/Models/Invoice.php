<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
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

    protected static function booted()
    {
        static::bootBelongsToCompany();

        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $year = date('Y');
                $latest = static::withoutGlobalScopes()->latest('id')->first();
                $seq = $latest ? ($latest->id + 1) : 1;
                $companyPrefix = $invoice->company_id ? sprintf('C%02d-', $invoice->company_id) : '';
                $invoice->invoice_number = sprintf('INV-%s%s-%04d', $companyPrefix, $year, $seq);
            }
        });

        static::saving(function (Invoice $invoice) {
            $invoice->vat_amount = round($invoice->amount * ($invoice->vat_percent / 100), 2);
            $invoice->total_amount = $invoice->amount + $invoice->vat_amount;
        });
    }

    public function updatePaymentStatus()
    {
        $totalPaid = $this->payments()->sum('amount');
        if ($totalPaid >= $this->total_amount) {
            $this->status = 'Paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'Partially Paid';
        }
        $this->save();
    }
}
