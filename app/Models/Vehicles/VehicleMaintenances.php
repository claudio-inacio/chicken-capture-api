<?php

namespace App\Models\Vehicles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class VehicleMaintenances extends Model
{
    use HasFactory;

    protected $table = 'vehicles.vehicle_maintenances';

    protected $fillable = [
        'id',
        'vehicle_id',
        'maintenance_mileage',
        'description',
        'value',
        'created_at',
        'updated_at'
    ];
}
