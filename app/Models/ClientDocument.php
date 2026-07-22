<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'document_type',
        'file_path',
        'file_name',
        'notes',
    ];

    public static array $types = [
        'Trade License' => 'Trade License',
        'Emirates ID' => 'Emirates ID',
        'Contract' => 'Contract',
        'Quotation' => 'Quotation',
        'Agreement' => 'Agreement',
        'Other' => 'Other',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
