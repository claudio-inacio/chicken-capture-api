<?php

namespace App\Repositories\Financial;

use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use App\Models\Catch\CatchDaily;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Units;
use App\Services\ResponseService;
use Exception;
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

    /**
     * @throws Exception
     */
    public function findAll($selectConfig, array $whereCriterious): array
    {
        $dateNow = (new \DateTime(now()))->format('Y-m-d');
        $financialAccounts = FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->where('finished_data', null)
            ->get()
            ->toarray();

        foreach ($financialAccounts as $financialAccount){
            FinancialAccounts::whereId($financialAccount['id'])->update(['status_id' => StatusEnum::DEFEATED]);
        }

        $query = DB::table('financial.financial_accounts')
            ->join('main.company', 'company.id', '=', 'financial_accounts.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'financial_accounts.credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        foreach ($whereCriterious as $criterious){
            if(str_contains($criterious['field'], 'table_reference_id')){
                if ($criterious['value'] == TableReferenceFinanceEnum::DAILY_CATCH){
                    $query->join('catch.catch_daily', 'catch_daily.id', '=', 'financial_accounts.reference_id');
                }
            }
        }

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

        $result = $query->get()->toArray();
        $arrayStatus = [];
        $arrayTotalValue = [];
        foreach ($result as $key => $item) {
            if (!key_exists(TypeFinanceEnum::TO_RECEIVE, $arrayTotalValue)){
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] = 0;
            }
            if (!key_exists(TypeFinanceEnum::TO_DISCOUNT, $arrayTotalValue)){
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] = 0;
            }

            $item->type == TypeFinanceEnum::TO_RECEIVE ?
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] += $item->amount : $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] += $item->amount;

            $item->catch_daily_date = null;
            $item->catch_daily_enabled = null;
            $item->catch_daily_units_id = null;
            $item->catch_daily_units_name = null;

            if ($item->table_reference_id == TableReferenceFinanceEnum::DAILY_CATCH) {
                $catch = CatchDaily::find($item->reference_id);

                if ($catch) {
                    $item->catch_daily_date = (new \DateTime($catch->date))->format('d/m/Y');
                    $item->catch_daily_enabled = $catch->enabled;

                    $unit = Units::find($catch->units_id);

                    if ($unit) {
                        $item->catch_daily_units_id = $unit->id;
                        $item->catch_daily_units_name = $unit->name;
                    }
                }
            }

            $arrayStatus[$item->status_id] = ($arrayStatus[$item->status_id] ?? 0) + $item->amount;
        }

        return [
            'data' => $result,
            'total' => $total,
            'value_to_receive' => "R$ ".FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_RECEIVE] ?? 0),
            'value_to_discount' => "R$ ".FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_DISCOUNT] ?? 0),
            'value_receive' => "R$ ".FormatHelper::decimalToBr($arrayStatus[StatusEnum::RECEIVE] ?? 0),
            'value_discount' => "R$ ".FormatHelper::decimalToBr($arrayStatus[StatusEnum::DISCOUNT] ?? 0),
            'value_defeated' => "R$ ".FormatHelper::decimalToBr($arrayStatus[StatusEnum::DEFEATED] ?? 0),
            'value_total_receive' => "R$ ".FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] ?? 0),
            'value_total_discount' => "R$ ".FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] ?? 0),
        ];
    }


    public function getById(int $id)
    {
        return FinancialAccounts::where('id', $id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['due_date'] = FormatHelper::dateToUsTimeStamp($value['due_date']);

        try {
            if ($value['status_id'] != StatusEnum::DISCOUNT) {
                unset($value['finished_data']);
            }
            FinancialAccounts::create($value);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em registrar conta', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['due_date'] = FormatHelper::dateToUsTimeStamp($data['due_date']);
        unset($data['financial_accounts_id']);
        try {
            if ($data['table_reference_id'] == TableReferenceFinanceEnum::DAILY_CATCH) {
                if (!empty($data['finished_data'])) {
                    CatchDaily::whereId($data['reference_id'])->update(['received' => true]);
                }
            }

            FinancialAccounts::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha em alterar conta', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            FinancialAccounts::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (Exception $e) {
            return ResponseService::internalServerError('Falha Ativar/Desativar conta', $e->getMessage());
        }
    }
}
