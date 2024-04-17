<?php

namespace App\Models\Vehicles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Vehicles extends Model
{
    use HasFactory;

    protected $table = 'vehicles.vehicles';

    protected $fillable = ['vehicle_id', 'vehicle_name', 'plate_number', 'unit_id', 'company_id', 'created_at',
        'updated_at'];
}
