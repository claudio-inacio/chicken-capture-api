<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Team extends Model
{
    use HasFactory;

    protected $table = 'main.team';

    protected $fillable = ['id', 'name', 'leader_credential_id', 'driver_credential_id', 'default_unit_id',
        'company_id', 'collectors', 'contracting_company_id', 'enabled', 'created_at', 'updated_at'];
}
