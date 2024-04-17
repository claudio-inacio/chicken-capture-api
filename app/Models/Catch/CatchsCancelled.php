<?php

namespace App\Models\Catch;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CatchsCancelled extends Model
{
    use HasFactory;

    protected $table = 'catch.catchs_cancelled';

    protected $fillable = ['id', 'date', 'credential_id', 'quantity', 'daily_catch_id', 'company_id', 'notes',
        'created_at', 'updated_at'];
}
