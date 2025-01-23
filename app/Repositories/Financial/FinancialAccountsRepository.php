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
use App\Models\Financial\ProofOfPayment;
use App\Models\Main\Units;
use App\Services\ResponseService;
use App\Services\Upload\UploadBase64Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

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
    #[ArrayShape(['data' => "mixed", 'total' => "int", 'value_to_receive' => "string", 'value_to_discount' => "string",
        'value_receive' => "string", 'value_discount' => "string", 'value_defeated' => "string",
        'value_total_receive' => "string", 'value_total_discount' => "string"])]
    public function findAll($selectConfig, array $whereCriterious): array
    {
        $dateNow = (new \DateTime(now()))->format('Y-m-d');
        $financialAccounts = FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->where('finished_data', null)
            ->get()
            ->toarray();

        foreach ($financialAccounts as $financialAccount) {
            FinancialAccounts::whereId($financialAccount['id'])->update(['status_id' => StatusEnum::DEFEATED]);
        }

        $query = DB::table('financial.financial_accounts')
            ->join('main.company', 'company.id', '=', 'financial_accounts.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'financial_accounts.credential_id')
            ->leftJoin('main.team', 'team.id', '=', 'financial_accounts.team_id')
            ->join('financial.cost_center', 'cost_center.id', '=', 'financial_accounts.cost_center_id')
            ->leftJoin('vehicles.vehicle', 'vehicle.id', '=', 'financial_accounts.vehicle_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        foreach ($whereCriterious as $criterious) {
            if (str_contains($criterious['field'], 'table_reference_id')) {
                if ($criterious['value'] == TableReferenceFinanceEnum::DAILY_CATCH) {
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
            'company.name as company_name',
            'team.name as team_name',
            'vehicle.name as vehicle_name',
            'vehicle.plate_number',
            'cost_center.name as cost_center_name',
        ]);

        $result = $query->get()->toArray();
        $arrayStatus = [];
        $arrayTotalValue = [];
        foreach ($result as $key => $item) {
            $item->description_data = json_decode($item->description_data);

            if (!key_exists(TypeFinanceEnum::TO_RECEIVE, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] = 0;
            }
            if (!key_exists(TypeFinanceEnum::TO_DISCOUNT, $arrayTotalValue)) {
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
                    $item->code = $catch->code;

                    $unit = Units::find($catch->units_id);

                    if ($unit) {
                        $item->catch_daily_units_id = $unit->id;
                        $item->catch_daily_units_name = $unit->name;
                    }
                }
            }

            $arrayStatus[$item->status_id] = ($arrayStatus[$item->status_id] ?? 0) + $item->amount;
            $item->amount = FormatHelper::decimalToBr($item->amount);
        }

        return [
            'data' => $result,
            'total' => $total,
            'value_to_receive' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_RECEIVE] ?? 0),
            'value_to_discount' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_DISCOUNT] ?? 0),
            'value_receive' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::RECEIVE] ?? 0),
            'value_discount' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::DISCOUNT] ?? 0),
            'value_defeated' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::DEFEATED] ?? 0),
            'value_total_receive' => "R$ " . FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] ?? 0),
            'value_total_discount' => "R$ " . FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] ?? 0),
        ];
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['data' => "mixed", 'total' => "int", 'value_to_receive' => "string", 'value_to_discount' => "string",
        'value_receive' => "string", 'value_discount' => "string", 'value_defeated' => "string",
        'value_total_receive' => "string", 'value_total_discount' => "string"])]
    public function findAllDownload($selectConfig, array $whereCriterious): array
    {
        $dateNow = (new \DateTime(now()))->format('Y-m-d');
        $financialAccounts = FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->where('finished_data', null)
            ->get()
            ->toarray();

        foreach ($financialAccounts as $financialAccount) {
            FinancialAccounts::whereId($financialAccount['id'])->update(['status_id' => StatusEnum::DEFEATED]);
        }

        $query = DB::table('financial.financial_accounts')
            ->join('main.company', 'company.id', '=', 'financial_accounts.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'financial_accounts.credential_id')
            ->leftJoin('main.team', 'team.id', '=', 'financial_accounts.team_id')
            ->join('financial.cost_center', 'cost_center.id', '=', 'financial_accounts.cost_center_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        foreach ($whereCriterious as $criterious) {
            if (str_contains($criterious['field'], 'table_reference_id')) {
                if ($criterious['value'] == TableReferenceFinanceEnum::DAILY_CATCH) {
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
            'financial_accounts.description',
            'financial_accounts.amount',
            'financial_accounts.due_date',
            'financial_accounts.finished_data',
            'financial_accounts.type',
            'financial_accounts.status_id',
            'financial_accounts.reference_id',
            'financial_accounts.table_reference_id',
            'credential.document as credential_document',
            'person.name as credential_name',
            'company.name as company_name',
            'team.name as team_name',
            'cost_center.name as cost_center_name',
        ]);

        $result = $query->get()->toArray();
        $arrayStatus = [];
        $arrayTotalValue = [];
        foreach ($result as $key => $item) {
            if (!key_exists(TypeFinanceEnum::TO_RECEIVE, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] = 0;
            }
            if (!key_exists(TypeFinanceEnum::TO_DISCOUNT, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] = 0;
            }

            $item->type == TypeFinanceEnum::TO_RECEIVE ?
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] += $item->amount : $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] += $item->amount;

            if ($item->type == TypeFinanceEnum::TO_RECEIVE) $item->type = 'RECEITA';
            if ($item->type == TypeFinanceEnum::TO_DISCOUNT) $item->type = 'DESPESA';

            $item->catch_daily_date = null;
            $item->catch_daily_enabled = null;
            $item->catch_daily_units_id = null;
            $item->catch_daily_units_name = null;

            if ($item->table_reference_id == TableReferenceFinanceEnum::DAILY_CATCH) {
                $catch = CatchDaily::find($item->reference_id);

                if ($catch) {
                    $item->catch_daily_date = (new \DateTime($catch->date))->format('d/m/Y');
                    $item->catch_daily_enabled = $catch->enabled;
                    $item->code = $catch->code;

                    $unit = Units::find($catch->units_id);

                    if ($unit) {
                        $item->catch_daily_units_id = $unit->id;
                        $item->catch_daily_units_name = $unit->name;
                    }
                }
            }

            if ($item->table_reference_id == TableReferenceFinanceEnum::DAILY_CATCH) $item->table_reference_id = "APANHA DIARIA";
            if ($item->table_reference_id == TableReferenceFinanceEnum::MAINTENANCE) $item->table_reference_id = "MANUNTENCAO";
            if ($item->table_reference_id == TableReferenceFinanceEnum::FUEL) $item->table_reference_id = "COMBUSTIVEL";
            if ($item->table_reference_id == TableReferenceFinanceEnum::DIARIST) $item->table_reference_id = "DIARIA";

            $arrayStatus[$item->status_id] = ($arrayStatus[$item->status_id] ?? 0) + $item->amount;

            if ($item->status_id == StatusEnum::TO_RECEIVE) $item->status_id = "A RECEBER";
            if ($item->status_id == StatusEnum::TO_DISCOUNT) $item->status_id = "A PAGAR";
            if ($item->status_id == StatusEnum::RECEIVE) $item->status_id = "RECEBIDO";
            if ($item->status_id == StatusEnum::DISCOUNT) $item->status_id = "DESCONTADO";
            if ($item->status_id == StatusEnum::DEFEATED) $item->status_id = "CANCELADO";

            $item->amount = "R$ ".FormatHelper::decimalToBr($item->amount);
            $item->due_date = FormatHelper::dateToBr($item->due_date);
            $item->finished_data ? $item->finished_data = FormatHelper::dateToBr($item->finished_data) : $item->finished_data = null;
        }

        return [
            'data' => $result,
            'total' => $total,
            'value_to_receive' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_RECEIVE] ?? 0),
            'value_to_discount' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::TO_DISCOUNT] ?? 0),
            'value_receive' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::RECEIVE] ?? 0),
            'value_discount' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::DISCOUNT] ?? 0),
            'value_defeated' => "R$ " . FormatHelper::decimalToBr($arrayStatus[StatusEnum::DEFEATED] ?? 0),
            'value_total_receive' => "R$ " . FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] ?? 0),
            'value_total_discount' => "R$ " . FormatHelper::decimalToBr($arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] ?? 0),
        ];
    }


    public function getById(int $id)
    {
        return FinancialAccounts::where('id', $id)->get();
    }

    /**
     * @throws Exception
     */
    public function create(array $arrayData, array $paymentData): \Illuminate\Http\JsonResponse
    {
        $arrayData['due_date'] = FormatHelper::dateToUsTimeStamp($arrayData['due_date']);
        $arrayData['amount'] = FormatHelper::moneyToUS($arrayData['amount']);

        if (!empty($data['finished_data']))
            $arrayData['finished_data'] = FormatHelper::dateToUsTimeStamp($arrayData['finished_data']);

        try {
            DB::beginTransaction();
            if ($arrayData['status_id'] != StatusEnum::DISCOUNT and $arrayData['status_id'] != StatusEnum::RECEIVE) {
                unset($arrayData['finished_data']);
            }

            $financialAccount = FinancialAccounts::create($arrayData);

            if ($paymentData['proof_of_payment']) {
                $upload = UploadBase64Service::uploadProofPayment($paymentData, $arrayData['credential_id'], $financialAccount);
                if (!$upload['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($upload['message'], $upload['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar conta', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, array $paymentData): \Illuminate\Http\JsonResponse
    {
        $data['due_date'] = FormatHelper::dateToUsTimeStamp($data['due_date']);
        $data['amount'] = FormatHelper::moneyToUS($data['amount']);
        unset($data['financial_accounts_id']);
        try {
            DB::beginTransaction();
            if ($data['table_reference_id'] == TableReferenceFinanceEnum::DAILY_CATCH) {
                if (!empty($data['finished_data'])) {
                    CatchDaily::whereId($data['reference_id'])->update(['received' => true]);
                }
            }

            if (!empty($data['finished_data']))
                $data['finished_data'] = FormatHelper::dateToUsTimeStamp($data['finished_data']);

            $financialAccount = FinancialAccounts::find($id)->update($data);

            if ($paymentData['proof_of_payment']) {
                $upload = UploadBase64Service::uploadProofPayment($paymentData, $data['credential_id'], $financialAccount);
                if (!$upload['success']) {
                    DB::rollBack();
                    return ResponseService::businessError($upload['message'], $upload['error']);
                }
            }

            DB::commit();
            return ResponseService::success204();
        } catch (Exception $e) {
            DB::rollBack();
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

    #[ArrayShape(['despesas' => "array", 'receitas' => "array", 'canceladas' => "array", 'total_receita' => "mixed",
        'total_despesa' => "mixed", 'total_canceladas' => "mixed", 'lucro' => "mixed",
        'total_registros_calculados' => "int"])]
    public function generalReport(array $selectConfig, array $whereCriterious): array
    {
        $dateNow = Carbon::now()->format('Y-m-d');
        FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->whereNull('finished_data')
            ->update(['status_id' => StatusEnum::DEFEATED]);

        $query = DB::table('financial.financial_accounts')
            ->join('financial.cost_center', 'cost_center.id', '=', 'financial_accounts.cost_center_id');

        $total = $query->count('financial_accounts.id');

        $query->select(
            'cost_center.name as cost_center_name',
            'financial_accounts.status_id',
            DB::raw('SUM(financial_accounts.amount) as total_amount')
        )
            ->groupBy('cost_center.name', 'financial_accounts.status_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $result = $query->get();

// Inicializar centros de custo com valores padrão
        $defaultStatuses = [
            'a_receber' => ['status' => 'a receber', 'valor' => 0],
            'recebido' => ['status' => 'a receber', 'valor' => 0],
            'pago' => ['status' => 'pago', 'valor' => 0],
            'a_pagar' => ['status' => 'a pagar', 'valor' => 0],
            'cancelado' => ['status' => 'cancelado', 'valor' => 0],
        ];

        $response = [];
        $totalReceita = 0;
        $totalDespesa = 0;
        $totalCancelados = 0;

// Processar resultados
        foreach ($result as $item) {
            $costCenterName = $item->cost_center_name;
            $statusKey = match ($item->status_id) {
                StatusEnum::TO_RECEIVE => 'a_receber',
                StatusEnum::RECEIVE => 'recebido',
                StatusEnum::TO_DISCOUNT => 'a_pagar',
                StatusEnum::DISCOUNT => 'pago',
                StatusEnum::DEFEATED => 'cancelado',
                default => 'desconhecido',
            };

            if (!isset($response[$costCenterName])) {
                $response[$costCenterName] = [];
            }

            $response[$costCenterName][$statusKey] = [
                'nome' => $costCenterName,
                'status' => $statusKey,
                'valor' => number_format($item->total_amount, 2, ',', '.'),
            ];

            // Somatórios
            if (in_array($statusKey, ['a_pagar', 'pago'])) {
                $totalDespesa += $item->total_amount;
            } elseif (in_array($statusKey, ['a_receber', 'recebido'])) {
                $totalReceita += $item->total_amount;
            } elseif ($statusKey === 'cancelado') {
                $totalCancelados += $item->total_amount;
            }
        }

// Garantir objetos com valores zerados para centros de custo sem dados
        foreach ($response as $costCenterName => &$statuses) {
            foreach ($defaultStatuses as $key => $default) {
                if (!isset($statuses[$key])) {
                    $statuses[$key] = [
                        'nome' => $costCenterName,
                        'status' => $default['status'],
                        'valor' => '0',
                    ];
                }
            }
        }

        $lucro = $totalReceita - $totalDespesa;

// Adiciona os totais gerais no retorno
        $response['totais'] = [
            'total_receita' => number_format($totalReceita, 2, ',', '.'),
            'total_despesa' => number_format($totalDespesa, 2, ',', '.'),
            'total_canceladas' => number_format($totalCancelados, 2, ',', '.'),
            'lucro' => number_format($lucro, 2, ',', '.'),
            'total_registros_calculados' => $total,
        ];

        return $response;
    }
}
