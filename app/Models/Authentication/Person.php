<?php

namespace App\Models\Authentication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Person extends Model
{
    use HasFactory;

    protected $table = 'authentication.person';

    protected $fillable = ['id', 'name', 'email', 'phone_number', 'company_group_id', 'is_owner', 'salary',
        'enabled', 'created_at', 'updated_at'];
}
