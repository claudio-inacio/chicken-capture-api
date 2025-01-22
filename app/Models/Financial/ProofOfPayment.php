<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProofOfPayment extends Model
{
    use HasFactory;

    protected $table = 'financial.proof_of_payment';

    protected $fillable = [
        'id',
        'financial_id',
        'file_patch',
        'file_type',
        'file_name',
        'status_id',
        'credential_id',
        'observation',
        'created_at',
        'updated_at'
    ];
}
