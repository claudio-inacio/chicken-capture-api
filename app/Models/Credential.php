<?php

namespace App\Models;

use App\Models\Authentication\Person;
use App\Models\Authentication\SessionToken;
use App\Models\Main\Company;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Credential extends Authenticatable implements JWTSubject
{
    use HasApiTokens;

    protected $table = 'authentication.credential';

    protected $guarded = ['id'];

    protected $fillable = [
        'document',
        'password',
        'person_id',
        'company_id',
        'access_group_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        $company = Company::find($this->company_id);
        $sessionTokenValue = $this->company_id . $this->id . Str::random(32);

        SessionToken::create([
            "credential_id" => $this->id,
            "value" => $sessionTokenValue,
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $person = Person::find($this->person_id);

        return [
            'id' => $this->id,
            'document' => $this->document,
            'name' => $person->name,
            'phone_number' => $person->phone_number,
            'access_group_id' => $this->access_group_id,
            'company_id' => $this->company_id,
            'company_name' => $company->name,
            'session_token' => $sessionTokenValue
        ];
    }
}
