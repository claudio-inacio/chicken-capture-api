<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FinancialAccounts extends Model
{
    use HasFactory;

    protected $table = 'financial.financial_accounts';

    protected $fillable = ['id', 'description', 'amount', 'due_date', 'finished_data', 'type', 'credential_id',
        'company_id', 'created_at', 'updated_at'];
}
