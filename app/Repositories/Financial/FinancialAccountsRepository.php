<?php

namespace App\Repositories\Financial;

use App\Enum\Authentication\AccessGroupEnum;
use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Financial\FinancialAccountsRepositoryInterface;
use App\Models\Catch\CatchDaily;
use App\Models\Credential;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Units;
use App\Services\ResponseService;
use App\Services\Upload\UploadBase64Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
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
    public function findAll($selectConfig, array $whereCriterious, Credential $credential): array
    {
        $dateNow = (new \DateTime(now()))->format('Y-m-d');
        $financialAccounts = FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->where('finished_data', null)
            ->get()
            ->toArray();

        foreach ($financialAccounts as $financialAccount) {
            FinancialAccounts::whereId($financialAccount['id'])->update(['status_id' => StatusEnum::DEFEATED]);
        }

        // Query base com joins
        $baseQuery = DB::table('financial.financial_accounts')
            ->join('main.company', 'company.id', '=', 'financial_accounts.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'financial_accounts.credential_id')
            ->leftJoin('main.team', 'team.id', '=', 'financial_accounts.team_id')
            ->join('financial.cost_center', 'cost_center.id', '=', 'financial_accounts.cost_center_id')
            ->leftJoin('vehicles.vehicle', 'vehicle.id', '=', 'financial_accounts.vehicle_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id');

        // joins dinâmicos (ex.: catch_daily) que dependem do whereCriterious
        foreach ($whereCriterious as $criterious) {
            if (str_contains($criterious['field'], 'table_reference_id')) {
                if ($criterious['value'] == TableReferenceFinanceEnum::DAILY_CATCH) {
                    $baseQuery->join('catch.catch_daily', 'catch_daily.id', '=', 'financial_accounts.reference_id');
                }
            }
        }

        // clone para usar como base das duas queries (uma para data/total, outra para totais agregados)
        $query = clone $baseQuery;
        $queryTotals = clone $baseQuery;

        $whereFactory = new WhereFactory();

        // Aplica todos os filtros originais na query principal
        $query = $whereFactory->byArray($query, $whereCriterious);

        // Para a query de totais: remove apenas filtros sobre financial_accounts.status_id
        $whereCriteriousWithoutStatus = array_filter($whereCriterious, function ($c) {
            return $c['field'] !== 'financial_accounts.status_id';
        });
        $queryTotals = $whereFactory->byArray($queryTotals, $whereCriteriousWithoutStatus);

        // Aplica restrição de credential (se necessário) em ambas
        if (
            $credential->access_group_id != AccessGroupEnum::DEVELOPER &&
            $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE
        ) {
            $query->where('financial_accounts.credential_id', $credential->id);
            $queryTotals->where('financial_accounts.credential_id', $credential->id);
        }

        // total count (aplicado na query filtrada)
        $total = $query->count('financial_accounts.id');

        // Aplica select/pagination apenas na query de listagem
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

        // ---------- AGREGAÇÕES: calcular totais IGNORANDO filtro de status_id ----------
        // 1) Totais por status (value_receive, value_discount, value_defeated, etc)
        $statusSums = $queryTotals
            ->select('financial_accounts.status_id', DB::raw('SUM(financial_accounts.amount) AS total_amount'))
            ->groupBy('financial_accounts.status_id')
            ->get()
            ->keyBy('status_id'); // facilita lookup por status_id

        // 2) Totais por type (value_total_receive, value_total_discount)
        $typeSums = $queryTotals
            ->select('financial_accounts.type', DB::raw('SUM(financial_accounts.amount) AS total_amount'))
            ->groupBy('financial_accounts.type')
            ->get()
            ->keyBy('type');

        $arrayTotalValue = [
            TypeFinanceEnum::TO_RECEIVE => (float)($typeSums[TypeFinanceEnum::TO_RECEIVE]->total_amount ?? 0),
            TypeFinanceEnum::TO_DISCOUNT => (float)($typeSums[TypeFinanceEnum::TO_DISCOUNT]->total_amount ?? 0),
        ];

        // extrai os status específicos
        $value_receive = (float)($statusSums[StatusEnum::RECEIVE]->total_amount ?? 0);
        $value_discount = (float)($statusSums[StatusEnum::DISCOUNT]->total_amount ?? 0);
        $value_defeated = (float)($statusSums[StatusEnum::DEFEATED]->total_amount ?? 0);
        $value_to_receive = (float)($statusSums[StatusEnum::TO_RECEIVE]->total_amount ?? 0);
        $value_to_discount = (float)($statusSums[StatusEnum::TO_DISCOUNT]->total_amount ?? 0);

        // ---------- PROCESSA O RESULTADO (apenas formata campos e calcula status do resultado filtrado se quiser) ----------
        // Se você quer também mostrar os status **aplicados ao resultado filtrado** (por ex.: totals exibidos na UI baseados no filtro),
        // você pode manter a lógica que somava $arrayStatus a partir de $result — mas conforme seu pedido, vamos usar os valores agregados
        // vindos de $queryTotals para os valores de resumo (portanto são invariantes ao filtro status_id).
        foreach ($result as $item) {
            $item->description_data = json_decode($item->description_data);

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

            $item->amount = FormatHelper::decimalToBr($item->amount);

            if ($item->enabled == false and $item->status_id != StatusEnum::DEFEATED){
                $item->status_id = StatusEnum::CANCELED;
            }
        }
        $valueToReceive = FormatHelper::decimalToBr($value_to_receive);
        $valueToDiscount = FormatHelper::decimalToBr($value_to_discount);
        $valueReceive = FormatHelper::decimalToBr($value_receive);
        $valueDiscount = FormatHelper::decimalToBr($value_discount);
        $valueDefeated = FormatHelper::decimalToBr($value_defeated);

        $type = TypeFinanceEnum::TO_RECEIVE;

        foreach ($whereCriterious as $criterious) {
            if (
                array_key_exists('field', $criterious) &&
                $criterious['field'] === 'financial_accounts.type' &&
                array_key_exists('value', $criterious)
            ) {
                $type = $criterious['value'];
                break;
            }
        }

        $defeatedToReceive = 0;
        $defeatedToDiscount = 0;

        if ($type == TypeFinanceEnum::TO_DISCOUNT){
            $defeatedToDiscount = $value_defeated;
        } else {
            $defeatedToReceive = $value_defeated;
        }

        return [
            'data' => $result,
            'total' => $total,
            'value_to_receive' => "R$ " . $valueToReceive,
            'value_to_discount' => "R$ " . $valueToDiscount,
            'value_receive' => "R$ " . $valueReceive,
            'value_discount' => "R$ " . $valueDiscount,
            'value_defeated' => "R$ " . $valueDefeated,
            'value_total_receive' => "R$ " . FormatHelper::decimalToBr($value_to_receive + $value_receive + $defeatedToReceive),
            'value_total_discount' => "R$ " . FormatHelper::decimalToBr($value_to_discount + $value_discount + $defeatedToDiscount),
        ];
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['data' => "mixed", 'total' => "int", 'value_to_receive' => "string", 'value_to_discount' => "string",
        'value_receive' => "string", 'value_discount' => "string", 'value_defeated' => "string",
        'value_total_receive' => "string", 'value_total_discount' => "string"])]
    public function findAllByDate($selectConfig, array $whereCriterious, $startDate, $endDate): array
    {
        $dateNow = (new \DateTime(now()))->format('Y-m-d');

        $financialAccounts = FinancialAccounts::where('status_id', '<>', StatusEnum::DEFEATED)
            ->where('due_date', '<', $dateNow)
            ->where('finished_data', null)
            ->get()
            ->toArray();

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

        $query->whereBetween('financial_accounts.created_at', [$startDate, $endDate]);

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
        $dataGrouped = [];

        foreach ($result as $item) {
            $item->description_data = json_decode($item->description_data);

            if (!key_exists(TypeFinanceEnum::TO_RECEIVE, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] = 0;
            }
            if (!key_exists(TypeFinanceEnum::TO_DISCOUNT, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] = 0;
            }

            if ($item->type == TypeFinanceEnum::TO_RECEIVE) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] += $item->amount;
            } else {
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] += $item->amount;
            }

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

            // Agrupar por data do created_at
            $dateKey = (new \DateTime($item->created_at))->format('d/m/Y');
            $item->amount = FormatHelper::decimalToBr($item->amount);

            if (!isset($dataGrouped[$dateKey])) {
                $dataGrouped[$dateKey] = [];
            }

            $dataGrouped[$dateKey][] = $item;
        }

// ✅ Preencher datas ausentes no intervalo com arrays vazios
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        while ($start->lte($end)) {
            $key = $start->format('d/m/Y');
            if (!isset($dataGrouped[$key])) {
                $dataGrouped[$key] = [];
            }
            $start->addDay();
        }

// ✅ Retorno final
        return [
            'data' => $dataGrouped,
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
            ->toArray();

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
        $dataGrouped = [];

        foreach ($result as $item) {
            $item->description_data = json_decode($item->description_data);

            if (!key_exists(TypeFinanceEnum::TO_RECEIVE, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] = 0;
            }
            if (!key_exists(TypeFinanceEnum::TO_DISCOUNT, $arrayTotalValue)) {
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] = 0;
            }

            if ($item->type == TypeFinanceEnum::TO_RECEIVE) {
                $arrayTotalValue[TypeFinanceEnum::TO_RECEIVE] += $item->amount;
            } else {
                $arrayTotalValue[TypeFinanceEnum::TO_DISCOUNT] += $item->amount;
            }

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

            // Agrupar por data do created_at
            $dateKey = (new \DateTime($item->created_at))->format('d/m/Y');
            $item->amount = FormatHelper::decimalToBr($item->amount);

            if (!isset($dataGrouped[$dateKey])) {
                $dataGrouped[$dateKey] = [];
            }

            $dataGrouped[$dateKey][] = $item;
        }

        return [
            'data' => $dataGrouped,
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
        $arrayData['due_date'] = FormatHelper::setDueDate($arrayData['due_date'] ?? null);
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
    public function update(int $id, array $data, array $paymentData): JsonResponse
    {
        $data['due_date'] = FormatHelper::setDueDate($data['due_date'] ?? null);
        $data['amount'] = FormatHelper::moneyToUS($data['amount']);
        unset($data['financial_accounts_id']);
        try {
            DB::beginTransaction();
            if ($data['table_reference_id'] == TableReferenceFinanceEnum::DAILY_CATCH) {
                if (!empty($data['finished_data'])) {
                    CatchDaily::whereId($data['reference_id'])->update(['received' => true]);
                }
            }

            if ($data['status_id'] != StatusEnum::TO_RECEIVE and $data['status_id'] != StatusEnum::TO_DISCOUNT) {
                if (!empty($data['finished_data']))
                    $data['finished_data'] = FormatHelper::dateToUsTimeStamp($data['finished_data']);
            }

            $financialAccount = FinancialAccounts::find($id);
            $financialAccount->update($data);

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
