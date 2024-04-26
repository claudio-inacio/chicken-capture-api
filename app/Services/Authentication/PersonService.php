<?php

namespace App\Services\Authentication;


use App\Enum\Authentication\AccessGroupEnum;
use App\Helpers\FormatHelper;
use App\Models\Authentication\Person;
use App\Models\Credential;
use App\Models\Main\Company;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class PersonService
{
    public static function create(array $arrayRequest, $user): \Illuminate\Http\JsonResponse
    {
        if (!FormatHelper::cpfIsValid($arrayRequest['document']))
            return ResponseService::businessError('Por favor envie um cpf valido!');

        DB::beginTransaction();
        try {
            $arrayRequest['document'] = FormatHelper::formatCnpjCpf($arrayRequest['document']);
            $arrayRequest['access_group_id'] == AccessGroupEnum::ADMINISTRATIVE ? $isOwner = true : $isOwner = false;
            $company = Company::whereId($user->company_id)->first();

            $personVerify = Person::where('email', $arrayRequest['email'])
                ->orWhere('phone_number', $arrayRequest['phone_number'])->first();

            if ($personVerify)
                return ResponseService::businessErrorWithData('Ja existe um cadastro para esse telefone ou email!', $personVerify);

            $person = Person::create([
                'name' => $arrayRequest['name'],
                'email' => $arrayRequest['email'],
                'phone_number' => $arrayRequest['phone_number'],
                'company_group_id' => $company->company_group_id,
                'is_owner' => $isOwner,
            ]);

            Credential::create([
                'document' => $arrayRequest['document'],
                'password' => bcrypt($arrayRequest['password']),
                'person_id' => $person->id,
                'access_group_id' => $arrayRequest['access_group_id'],
                'company_id' => $arrayRequest['company_id']
            ]);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $exception){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em cadastrar usuario', $exception->getMessage());
        }
    }
}
