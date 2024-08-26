<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Collectors extends Model
{
    use HasFactory;

    protected $table = 'main.collectors';

    protected $fillable = ['id', 'quantity', 'company_id', 'enabled', 'collectors_group_id', 'created_at', 'updated_at'];
}
