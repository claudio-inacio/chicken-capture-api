<?php

namespace App\Repositories\Financial;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class FinancialAccountsRepository implements FinancialAccountsRepositoryInterface
{
    public function getAll()
    {
        return FinancialAccounts::all();
    }

    public function getByName(string $name)
    {
        return FinancialAccounts::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('financial.financial_accounts')
            ->join('main.company', 'company.id', '=', 'financial_accounts.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'financial_accounts.credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('financial_accounts.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'financial_accounts.*',
            'credential.document as credential_document',
            'person.name as credential_name',
            'company.name as company_name'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return FinancialAccounts::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['due_date'] = FormatHelper::dateToUsTimeStamp($value['due_date']);

        try {
            FinancialAccounts::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar conta', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['due_date'] = FormatHelper::dateToUsTimeStamp($data['due_date']);
        unset($data['financial_accounts_id']);
        try {
            FinancialAccounts::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar conta', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            FinancialAccounts::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar conta', $e->getMessage());
        }
    }
}
