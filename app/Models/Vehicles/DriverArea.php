<?php

namespace App\Models\Vehicles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DriverArea extends Model
{
    use HasFactory;

    protected $table = 'vehicles.driver_area';

    protected $fillable = ['id', 'credential_id', 'vehicle_id', 'liters_of_fuel', 'maintenance_expenses',
        'enabled', 'total_supply_value', 'daily_start_km', 'daily_start_time', 'daily_end_km', 'daily_end_date',
        'company_id', 'created_at', 'updated_at'];
}
