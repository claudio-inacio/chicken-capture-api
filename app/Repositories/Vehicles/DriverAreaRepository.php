<?php

namespace App\Repositories\Vehicles;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Vehicles\DriverAreaRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Models\Vehicles\DriverArea;
use App\Models\Vehicles\Vehicle;
use App\Services\ResponseService;
use App\Services\Vehicles\DriverAreaService;
use Illuminate\Support\Facades\DB;

class DriverAreaRepository implements DriverAreaRepositoryInterface
{
    public function getAll()
    {
        return DriverArea::all();
    }

    public function getByName(string $name)
    {
        return DriverArea::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('vehicles.driver_area')
            ->join('authentication.credential', 'credential.id', '=', 'driver_area.credential_id')
            ->join('vehicles.vehicle', 'vehicle.id', '=', 'driver_area.vehicles')
            ->join('main.company', 'company.id', '=', 'driver_area.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('driver_area.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'driver_area.*',
            'company.name as company_name',
            'vehicle.name', 'vehicle.plate_number'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return DriverArea::where('id',$id)->get();
    }

    public function create(array $value)
    {
       return DriverArea::create($value);
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
       return DriverArea::whereId($id)->update($data);
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            DriverArea::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar area do motorista', $e->getMessage());
        }
    }
}
