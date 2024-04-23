<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class MonthlyClosingReports extends Model
{
    use HasFactory;

    protected $table = 'financial.monthly_closing_reports';

    protected $fillable = ['id', 'month', 'year', 'total_expenses', 'total_income', 'company_id', 'created_at', 'updated_at'];
}
