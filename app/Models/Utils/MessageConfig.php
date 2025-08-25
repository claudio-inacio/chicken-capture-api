<?php

namespace App\Models\Utils;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class MessageConfig extends Model
{
    use HasFactory;

    protected $table = 'messaging.message_config';

    protected $fillable = [
        'auth',
        'message_service_id',
        'external_id',
        'client_id',
        'created_at',
        'updated_at'
    ];
}
