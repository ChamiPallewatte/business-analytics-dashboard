<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'service_id',
        'type',
        'vendor_name',
        'expense_date',
        'amount',
        'vat_amount',
        'total_amount',
        'payment_status',
        'payment_method',
        'currency',
        'vat_percent',
        'department',
        'project',
        'cost_center',
        'tags',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'tags' => 'array',
    ];

    public static array $types = [
        'Hosting Purchase' => 'Hosting Purchase',
        'Domain Purchase' => 'Domain Purchase',
        'Freelancer Cost' => 'Freelancer Cost',
        'Paid Advertising' => 'Paid Advertising',
        'Software Subscription' => 'Software Subscription',
        'Marketing' => 'Marketing',
        'Other' => 'Other',
    ];

    public static array $statuses = [
        'Paid' => 'Paid',
        'Pending' => 'Pending',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Audit log triggers.
     */
    protected static function booted()
    {
        static::created(function (Expense $expense) {
            ActivityLog::log('Created Expense', Expense::class, $expense->id, "Type: {$expense->type}, Amount: {$expense->amount}");
        });

        static::updated(function (Expense $expense) {
            ActivityLog::log('Updated Expense', Expense::class, $expense->id, "Type: {$expense->type}, Amount: {$expense->amount}");
        });

        static::deleted(function (Expense $expense) {
            ActivityLog::log('Deleted Expense', Expense::class, $expense->id, "Type: {$expense->type}, Amount: {$expense->amount}");
        });
    }
}
