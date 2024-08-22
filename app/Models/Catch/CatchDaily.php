<?php

namespace App\Models\Catch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CatchDaily extends Model
{
    use HasFactory;

    protected $table = 'catch.catch_daily';

    protected $fillable = ['id', 'date', 'quantity', 'code', 'batch', 'credential_id', 'units_id',
        'integrated_id', 'team_id', 'catch_type_id', 'company_id', 'enabled', 'received', 'created_at', 'updated_at'];
}
