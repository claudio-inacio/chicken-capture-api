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
            ->join('authentication.credential', 'credential.id', '=', 'vehicle.motorista_credential_id')
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->join('main.units', 'units.id', '=', 'vehicle.unit_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('vehicle.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'vehicle.*',
            'person.name as motorista_credential_name',
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
            ->join('vehicles.driver_area as da', 'fuel_supply.driver_area_id', '=', 'da.id')
            ->join('vehicles.vehicle', 'da.vehicle_id', '=', 'vehicle.id')
            ->select([
                'fuel_supply.id',
                'fuel_supply.total_value',
                'fuel_supply.liters_filled',
                'fuel_supply.km_filled',
                'fuel_supply.created_at',
                'vehicle.id as vehicle_id',
                'vehicle.name as vehicle_name',
                'vehicle.plate_number',
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

            if ($vehicle) return ResponseService::businessError('Veiculo ja cadastrado no sistema!');

            $verifyDriver = Vehicle::where('motorista_credential_id', $value['motorista_credential_id'])->first();
            if ($verifyDriver){
                return ResponseService::businessError(
                    "Esse motorista ja tem um veiculo cadastrado para ele. Veiculo: $verifyDriver->name, Placa: $verifyDriver->plate_number"
                );
            }

            Vehicle::create($value);
            return ResponseService::success204();
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

            $verifyDriver = Vehicle::where('motorista_credential_id', $data['motorista_credential_id'])
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
}
