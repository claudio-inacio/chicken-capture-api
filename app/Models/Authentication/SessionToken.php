<?php

namespace App\Models\Authentication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionToken extends Model
{
    use HasFactory;

    protected $table = 'authentication.session_token';

    protected $fillable = [
        'credential_id',
        'value',
        'created_at',
        'updated_at'
    ];
}
