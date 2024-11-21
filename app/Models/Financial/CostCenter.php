<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CostCenter extends Model
{
    use HasFactory;

    protected $table = 'financial.cost_center';

    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
}
