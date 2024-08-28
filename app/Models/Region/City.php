<?php

namespace App\Models\Region;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class City extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'region.city';

    protected $fillable = ['id', 'code', 'name', 'uf'];
}
