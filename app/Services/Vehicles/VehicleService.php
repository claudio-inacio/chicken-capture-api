<?php

namespace App\Services\Vehicles;

use App\Models\Vehicles\Vehicle;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public static function saveMileageVehicle(array $arrayRequest): array
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::whereId($arrayRequest['vehicle_id'])->first();

            if (!$vehicle)
                return [
                    'success' => false,
                    'message' => 'Falha em encontrar veiculo',
                    'error' => 'id do veiculo nao encontrado id -> '.$arrayRequest['vehicle_id']
                ];

            Vehicle::whereId($vehicle->id)->update([
                'mileage' => $arrayRequest['daily_end_km'],
            ]);

            DB::commit();
            return [
                'success' => true
            ];
        } catch (\Exception $exception){
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Falha em cadastrar finanças',
                'error' => $exception->getMessage()
            ];
        }
    }
}
