<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Vehicles\VehiclesRepositoryInterface;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

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
