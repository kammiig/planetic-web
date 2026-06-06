<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainContact extends Model
{
    protected $fillable = [
        'domain_id', 'contact_type', 'first_name', 'last_name', 'company_name',
        'email', 'phone', 'address_line_1', 'address_line_2', 'city', 'state',
        'postcode', 'country',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
