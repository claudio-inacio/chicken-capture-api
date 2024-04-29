<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Helpers\ValidatorHelpers;
use App\Interfaces\Main\CompanyRepositoryInterface;
use App\Models\Main\Company;
use App\Models\Main\CompanyGroup;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function getAll()
    {
        return Company::all();
    }

    public function getByName(string $name)
    {
        return Company::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.company')
            ->join('main.company_group', 'company_group.id', '=', 'company.company_group_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('company.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'company.*',
            'company_group.name as company_group_name'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Company::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['cnpj'] = FormatHelper::formatCnpjCpf($value['cnpj']);
        //$validateCnpj = ValidatorHelpers::validateCnpj($value['cnpj']);
        $value['phone'] = FormatHelper::removeSpecialCaracterTel($value['phone']);
        $value['phone'] = FormatHelper::formatPhoneNumber($value['phone']);
//        if (!$value['phone'] || $validateCnpj)
//            return ResponseService::businessError('Cnpj ou telefone inválidos');

        try {
            $company = Company::where('name', $value['name'])
                ->orWhere('phone', $value['phone'])
                ->orWhere('cnpj', $value['cnpj'])
                ->orWhere('email', $value['email'])
                ->where('company_group_id', $value['company_group_id'])
                ->first();

            if($company) return ResponseService::businessError('Compania ja cadastrado, por favor verificar dados');

            if ($value['is_main']) {
                $company = Company::where('company.company_group_id', $value['company_group_id'])
                    ->where('company.is_main', true)
                    ->where('company.enabled', true)
                    ->join('main.company_group', 'company_group.id', '=', 'company.company_group_id')
                    ->select(['company.*', 'company_group.name as company_group_name'])
                    ->first();

                if ($company)
                    return ResponseService::businessError(
                        'Ja existe uma compania cadastrada como main para o grupo -> '. $company->company_group_name
                    );
            }

            Company::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar compania', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['cnpj'] = FormatHelper::formatCnpjCpf($data['cnpj']);
        //$validateCnpj = ValidatorHelpers::validateCnpj($data['cnpj']);
        $data['phone'] = FormatHelper::removeSpecialCaracterTel($data['phone']);
        $data['phone'] = FormatHelper::formatPhoneNumber($data['phone']);
//        if (!$data['phone'] || $validateCnpj)
//            return ResponseService::businessError('Cnpj ou telefone inválidos');

        unset($data['company_id']);
        try {
            $company = Company::where('name', $data['name'])
                ->orWhere('phone', $data['phone'])
                ->orWhere('cnpj', $data['cnpj'])
                ->orWhere('email', $data['email'])
                ->first();

            if($company) return ResponseService::businessError('Compania ja cadastrado, por favor verificar dados');

            Company::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar compania', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            $company = Company::whereId($id)->first();
            if ($enable) {
                $companyVerify = Company::where('company_group_id', $company->company_group_id)
                    ->where('enabled', true)
                    ->where('is_main', true)
                    ->where('id', '<>', $id)
                    ->get();

                $companyGroup = CompanyGroup::whereId($company->company_group_id)->first();
                if (count($companyVerify) > 0)
                    return ResponseService::businessError(
                        'Voce nao pode reativar essa compania. Ja existe uma compania cadastrada como main para o grupo -> '. $companyGroup->name
                    );
            }

            Company::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar compania', $e->getMessage());
        }
    }
}
