<?php

namespace App\Models\Catch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CatchType extends Model
{
    use HasFactory;

    protected $table = 'catch.catch_type';

    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
}
