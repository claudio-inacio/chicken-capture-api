<?php

namespace App\Models\Utils;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ExtraCatchConfiguration extends Model
{
    use HasFactory;

    protected $table = 'catch.extra_catch_configuration';

    protected $fillable = [
        'company_id',
        'loading_target',
        'bonus_amount',
        'catch_type_id',
        'enabled',
        'created_at',
        'updated_at'
    ];
}
