<?php

namespace App\Models\Catch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CatchsConfiguration extends Model
{
    use HasFactory;

    protected $table = 'catch.catchs_configuration';

    protected $fillable = ['id', 'catch_type_id', 'company_id', 'catch_price', 'cancellation_price', 'created_at', 'enabled',
        'updated_at'];
}
