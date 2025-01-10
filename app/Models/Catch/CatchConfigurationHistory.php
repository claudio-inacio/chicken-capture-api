<?php

namespace App\Models\Catch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CatchConfigurationHistory extends Model
{
    use HasFactory;

    protected $table = 'catch.catchs_configuration_history';

    protected $fillable = ['id', 'catch_type_id', 'company_id', 'catch_price', 'cancellation_price', 'catch_configuration_id',
        'created_at', 'updated_at'];
}
