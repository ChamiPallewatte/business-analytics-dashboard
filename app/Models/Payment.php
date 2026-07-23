<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'service_id',
        'invoice_id',
        'amount',
        'balance_amount',
        'status',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public static array $methods = [
        'Cash' => 'Cash',
        'Bank Transfer' => 'Bank Transfer',
        'Cheque' => 'Cheque',
        'PDC' => 'PDC',
        'Card' => 'Card',
    ];

    public static array $statuses = [
        '100% Paid' => '100% Paid',
        '50% Paid' => '50% Paid',
        'Partial' => 'Partial',
        'Pending' => 'Pending',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted()
    {
        static::bootBelongsToCompany();

        static::created(function (Payment $payment) {
            ActivityLog::log('Created Payment', Payment::class, $payment->id, "Amount: {$payment->amount}, Mode: {$payment->payment_method}");
        });

        static::deleted(function (Payment $payment) {
            ActivityLog::log('Deleted Payment', Payment::class, $payment->id, "Amount: {$payment->amount}");
        });

        $updateParentRelations = function (Payment $payment) {
            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            }

            $service = $payment->service;
            if ($service) {
                $service->paid_amount = $service->payments()->sum('amount');
                $service->save();
            }
        };

        static::saved($updateParentRelations);
        static::deleted($updateParentRelations);
    }
}
