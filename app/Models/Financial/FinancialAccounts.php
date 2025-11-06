<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FinancialAccounts extends Model
{
    use HasFactory;

    protected $table = 'financial.financial_accounts';

    protected $fillable = ['id', 'description', 'amount', 'due_date', 'finished_data', 'type', 'credential_id',
        'status_id', 'company_id', 'reference_id', 'table_reference_id', 'description_data', 'team_id', 'cost_center_id',
        'vehicle_id', 'driver_credential_id', 'created_at', 'updated_at'];
}
