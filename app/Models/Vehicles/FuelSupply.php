<?php

namespace App\Models\Vehicles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FuelSupply extends Model
{
    use HasFactory;

    protected $table = 'vehicles.fuel_supply';

    protected $fillable = [
        'driver_area_id',
        'credential_id',
        'total_value',
        'liters_filled',
        'km_filled',
        'created_at',
        'updated_at',
    ];
}
