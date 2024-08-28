<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Units extends Model
{
    use HasFactory;

    protected $table = 'main.units';

    protected $fillable = ['id', 'name', 'location', 'code', 'company_id', 'contracting_company_id', 'enabled', 'created_at',
        'updated_at'];
}
