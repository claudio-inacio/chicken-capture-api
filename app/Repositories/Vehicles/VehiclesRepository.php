<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\FuelSupply;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class VehiclesRepository implements VehiclesRepositoryInterface
{
    public function getAll()
    {
        return Vehicle::all();
    }

    public function getByName(string $name)
    {
        return Vehicle::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.vehicle')
            ->join('main.company', 'company.id', '=', 'vehicle.company_id')
            ->join('authentication.credential', 'credential.id', '=', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->join('main.units', 'units.id', '=', 'vehicle.unit_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('vehicle.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'vehicle.*',
            'person.name as driver_credential_name',
            'company.name as company_name',
            'units.name as unit_name', 'units.code as unit_code'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }

    #[ArrayShape(['financial_expenses' => "\Illuminate\Support\Collection",
        'fuel_supplies' => "\Illuminate\Support\Collection", 'totals' => "array"])]
    public function expenses($selectConfig, array $whereCriterious) : array
    {
        $whereFactory = new WhereFactory();
        $selectFactory = new SelectFactory();
        // -------------------------------
        // CONSULTA DESPESAS FINANCEIRAS
        // -------------------------------
        $financialQuery = DB::table('financial.financial_accounts')
            ->join('vehicles.vehicle', 'vehicle.id', '=', 'financial_accounts.vehicle_id')
            ->join('authentication.credential', 'credential.id', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', 'credential.person_id')
            ->select([
                'financial_accounts.id',
                'financial_accounts.description',
                'financial_accounts.amount',
                'financial_accounts.cost_center_id',
                'financial_accounts.table_reference_id',
                'financial_accounts.created_at',
                'vehicle.id as vehicle_id',
                'vehicle.name as vehicle_name',
                'vehicle.plate_number',
                'person.name as person_name',
                'person.phone_number as person_phone_number',
                'credential.document as person_document',
            ]);

        $newCriterious = [];
        foreach ($whereCriterious as $key => $criterious){
            if(str_contains($criterious['field'], 'financial_accounts.')){
                $newCriterious[$key] = $criterious;
            }
            if(str_contains($criterious['field'], 'vehicle.')){
                $newCriterious[$key] = $criterious;
            }
        }


        $financialQuery = $whereFactory->byArray($financialQuery, $newCriterious);
        if (!empty($selectConfig)) {
            $financialQuery = $selectFactory->byArray($financialQuery, $selectConfig);
        }

        $financialExpenses = $financialQuery->get();


        // -------------------------------
        // CONSULTA COMBUSTÍVEL
        // -------------------------------
        $fuelQuery = DB::table('vehicles.fuel_supply')
            ->join('vehicles.driver_area', 'fuel_supply.driver_area_id', '=', 'driver_area.id')
            ->join('vehicles.vehicle', 'driver_area.vehicle_id', '=', 'vehicle.id')
            ->join('authentication.credential', 'credential.id', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', 'credential.person_id')
            ->select([
                'fuel_supply.id',
                'fuel_supply.total_value',
                'fuel_supply.liters_filled',
                'fuel_supply.km_filled',
                'fuel_supply.created_at',
                'vehicle.id as vehicle_id',
                'vehicle.name as vehicle_name',
                'vehicle.plate_number',
                'person.name as person_name',
                'person.phone_number as person_phone_number',
                'credential.document as person_document',
            ]);

        foreach ($whereCriterious as $key => $criterious){
            if(str_contains($criterious['field'], 'financial_accounts.')){
                unset($whereCriterious[$key]);
            }
        }

        $fuelQuery = $whereFactory->byArray($fuelQuery, $whereCriterious);
        if (!empty($selectConfig)) {
            $fuelQuery = $selectFactory->byArray($fuelQuery, $selectConfig);
        }

        $fuelSupplies = $fuelQuery->get();


        // -------------------------------
        // MONTA O RELATÓRIO
        // -------------------------------
        return [
            'financial_expenses' => $financialExpenses,
            'fuel_supplies'      => $fuelSupplies,
            'totals' => [
                'expenses' => $financialExpenses->sum('amount'),
                'fuel'      => $fuelSupplies->sum('total_value'),
                'general'   => $financialExpenses->sum('amount') + $fuelSupplies->sum('total_value')
            ]
        ];
    }


    public function getById(int $id)
    {
        return Vehicle::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $vehicle = Vehicle::where('plate_number', $value['plate_number'])
                ->where('company_id', $value['company_id'])
                ->first();

            if ($vehicle) return ResponseService::businessError('Veiculo ja cadastrado no sistema!', [
                'vehicle' => $vehicle->id
            ]);

            $verifyDriver = Vehicle::where('driver_credential_id', $value['driver_credential_id'])->first();
            if ($verifyDriver){
                return ResponseService::businessError(
                    "Esse motorista ja tem um veiculo cadastrado para ele. Veiculo: $verifyDriver->name, Placa: $verifyDriver->plate_number"
                );
            }

            $vehicle = Vehicle::create($value);
            return ResponseService::success('Veículo cadastrada com sucesso!', [
                'vehicle' => $vehicle->id
            ]);
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar Veiculo', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['vehicle_id']);
        try {
            $vehicle = Vehicle::where('plate_number', $data['plate_number'])
                ->where('company_id', $data['company_id'])
                ->where('id', '<>', $id)
                ->first();

            if ($vehicle) return ResponseService::businessError('Veiculo ja cadastrado no sistema!');

            $verifyDriver = Vehicle::where('driver_credential_id', $data['driver_credential_id'])
                ->where('id', '<>', $id)
                ->first();
            if ($verifyDriver){
                return ResponseService::businessError(
                    "Esse motorista ja tem um veiculo cadastrado para ele. Veiculo: $verifyDriver->name, Placa: $verifyDriver->plate_number"
                );
            }

            Vehicle::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar Veiculo', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Vehicle::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar Veiculo', $e->getMessage());
        }
    }

    public function expensesGraphic(
        array $whereCriterious,
        array $selectConfig,
        string $groupBy = 'day' // week | day | month | year
    ): array {
        $whereFactory  = new WhereFactory();
        $selectFactory = new SelectFactory();

        /*
        |--------------------------------------------------------------------------
        | DEFINIÇÃO DE PERÍODO (COM ALIAS PARA EVITAR AMBIGUIDADE)
        |--------------------------------------------------------------------------
        */
        switch ($groupBy) {
            case 'week':
                $dateGroupFa = DB::raw("EXTRACT(DOW FROM financial_accounts.created_at) as period");
                $dateGroupFs = DB::raw("EXTRACT(DOW FROM fuel_supply.created_at) as period");
                $periodo     = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
                break;

            case 'month':
                $dateGroupFa = DB::raw("EXTRACT(MONTH FROM financial_accounts.created_at) as period");
                $dateGroupFs = DB::raw("EXTRACT(MONTH FROM fuel_supply.created_at) as period");
                $periodo     = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                break;

            case 'year':
                $dateGroupFa = DB::raw("EXTRACT(YEAR FROM financial_accounts.created_at) as period");
                $dateGroupFs = DB::raw("EXTRACT(YEAR FROM fuel_supply.created_at) as period");
                $periodo     = [];
                break;

            default: // day
                $dateGroupFa = DB::raw("EXTRACT(DAY FROM financial_accounts.created_at) as period");
                $dateGroupFs = DB::raw("EXTRACT(DAY FROM fuel_supply.created_at) as period");
                $periodo     = range(1, 31);
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRA WHERES POR TABELA (IGUAL AO SEU PADRÃO)
        |--------------------------------------------------------------------------
        */
        $financialWhere = [];
        $fuelWhere      = [];

        foreach ($whereCriterious as $criterious) {
            if (
                str_contains($criterious['field'], 'financial.financial_accounts') ||
                str_contains($criterious['field'], 'vehicle.vehicle')
            ) {
                $financialWhere[] = $criterious;
            }

            if (
                str_contains($criterious['field'], 'vehicles.fuel_supply') ||
                str_contains($criterious['field'], 'vehicle.vehicle')
            ) {
                $fuelWhere[] = $criterious;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DESPESAS FINANCEIRAS
        |--------------------------------------------------------------------------
        */
        $financialQuery = DB::table('financial.financial_accounts')
            ->join('vehicles.vehicle', 'vehicle.id', '=', 'financial_accounts.vehicle_id')
            ->join('authentication.credential', 'credential.id', '=', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->select([
                'vehicle.id as vehicle_id',
                'vehicle.name as vehicle_name',
                'vehicle.plate_number',
                'person.name as driver_name',
                'credential.document as driver_cpf',
                'person.phone_number as driver_phone',
                $dateGroupFa,
                DB::raw('SUM(financial_accounts.amount) as total')
            ])
            ->groupBy(
                'vehicle.id',
                'vehicle.name',
                'vehicle.plate_number',
                'person.name',
                'credential.document',
                'person.phone_number',
                'period'
            );

        $financialQuery = $whereFactory->byArray($financialQuery, $financialWhere);

        if (!empty($selectConfig)) {
            $financialQuery = $selectFactory->byArray($financialQuery, $selectConfig);
        }

        /*
        |--------------------------------------------------------------------------
        | COMBUSTÍVEL
        |--------------------------------------------------------------------------
        */
        $fuelQuery = DB::table('vehicles.fuel_supply')
            ->join('vehicles.driver_area', 'fuel_supply.driver_area_id', '=', 'driver_area.id')
            ->join('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicle_id')
            ->join('authentication.credential', 'credential.id', '=', 'vehicle.driver_credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->select([
                'vehicle.id as vehicle_id',
                'vehicle.name as vehicle_name',
                'vehicle.plate_number',
                'person.name as driver_name',
                'credential.document as driver_cpf',
                'person.phone_number as driver_phone',
                $dateGroupFs,
                DB::raw('SUM(fuel_supply.total_value) as total')
            ])
            ->groupBy(
                'vehicle.id',
                'vehicle.name',
                'vehicle.plate_number',
                'person.name',
                'credential.document',
                'person.phone_number',
                'period'
            );

        $fuelQuery = $whereFactory->byArray($fuelQuery, $fuelWhere);

        if (!empty($selectConfig)) {
            $fuelQuery = $selectFactory->byArray($fuelQuery, $selectConfig);
        }

        /*
        |--------------------------------------------------------------------------
        | UNIFICA RESULTADOS
        |--------------------------------------------------------------------------
        */
        $rows = $financialQuery
            ->unionAll($fuelQuery)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MONTA RETORNO PARA O FRONT
        |--------------------------------------------------------------------------
        */
        $grouped = [];

        foreach ($rows as $row) {
            $vehicleKey = $row->vehicle_id;

            if (!isset($grouped[$vehicleKey])) {
                $grouped[$vehicleKey] = [
                    'vehicle_id'    => $row->vehicle_id,
                    'vehicle_name'  => $row->vehicle_name,
                    'plate_number'  => $row->plate_number,
                    'driver_name'   => $row->driver_name,
                    'driver_cpf'    => $row->driver_cpf,
                    'driver_phone'  => $row->driver_phone,
                    'data'          => []
                ];
            }

            if ($groupBy === 'year') {
                $year = (int) $row->period;
                $grouped[$vehicleKey]['data'][$year] =
                    ($grouped[$vehicleKey]['data'][$year] ?? 0) + (float) $row->total;
                continue;
            }

            $index = (int) $row->period - 1;
            $grouped[$vehicleKey]['data'][$index] =
                ($grouped[$vehicleKey]['data'][$index] ?? 0) + (float) $row->total;
        }

        $result = [];


        foreach ($grouped as $item) {

            if ($groupBy === 'year') {
                ksort($item['data']);

                $result[] = [
                    'vehicle_id'   => $item['vehicle_id'],
                    'vehicle_name' => $item['vehicle_name'],
                    'plate_number' => $item['plate_number'],
                    'driver_name'  => $item['driver_name'],
                    'driver_cpf'   => $item['driver_cpf'],
                    'driver_phone' => $item['driver_phone'],
                    'data'         => array_map(fn ($v) => round($v, 2), array_values($item['data'])),
                    'periodo'      => array_map('strval', array_keys($item['data']))
                ];
                continue;
            }

            $filledData = array_replace(
                array_fill(0, count($periodo), 0),
                $item['data']
            );

            $result[] = [
                'vehicle_id'   => $item['vehicle_id'],
                'vehicle_name' => $item['vehicle_name'],
                'plate_number' => $item['plate_number'],
                'driver_name'  => $item['driver_name'],
                'driver_cpf'   => $item['driver_cpf'],
                'driver_phone' => $item['driver_phone'],
                'data'         => array_map(fn ($v) => round($v, 2), $filledData),
                'periodo'      => $periodo
            ];
        }

        return $result;
    }

}
