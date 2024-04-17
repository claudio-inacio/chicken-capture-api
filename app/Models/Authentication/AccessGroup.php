<?php

namespace App\Models\Authentication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class AccessGroup extends Model
{
    use HasFactory;

    protected $table = 'authentication.access_group';

    protected $fillable = ['id', 'name'];
}
