<?php

namespace App\Models\Vehicles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicles.vehicle';

    protected $fillable = ['id', 'name', 'plate_number', 'unit_id', 'company_id', 'enabled', 'mileage',
        'created_at', 'updated_at'];
}
