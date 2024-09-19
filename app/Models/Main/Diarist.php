<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Diarist extends Model
{
    use HasFactory;

    protected $table = 'main.diarist';

    protected $fillable = ['id', 'name', 'document', 'phone_number', 'diarist_group_id',
        'company_id', 'enabled', 'daily', 'date', 'team_id', 'created_at', 'updated_at'];
}
