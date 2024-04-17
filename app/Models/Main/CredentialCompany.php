<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class CredentialCompany extends Model
{
    use HasFactory;

    protected $table = 'main.credential_company';

    protected $fillable = ['credential_id', 'company_id', 'created_at', 'updated_at'];
}
