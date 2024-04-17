<?php

namespace App\Repositories\Financial;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\ContractingCompany\FinancialAccountsRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
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
        $query = DB::table('financial.financial_accounts');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('financial_accounts.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['financial_accounts.*']);

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

    public function create(array $value)
    {
        return FinancialAccounts::create($value);
    }

    public function update(int $id, array $data)
    {
        return FinancialAccounts::whereId($id)->update($data);
    }
}
