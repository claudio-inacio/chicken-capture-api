<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use App\Models\Catch\CatchsConfiguration;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class CatchsConfigurationRepository implements CatchsConfigurationRespositoryInterface
{
    public function getAll()
    {
        return CatchsConfiguration::all();
    }

    public function getByName(string $name)
    {
        return CatchsConfiguration::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.catchs_configuration');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catchs_configuration.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['catchs_configuration.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchsConfiguration::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['catch_price'] = FormatHelper::brlTodecimal($value['catch_price']);
        $value['cancellation_price'] = FormatHelper::brlTodecimal($value['cancellation_price']);
        try {
            CatchsConfiguration::create($value);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar configuração de apanha', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['catch_price'] = FormatHelper::brlTodecimal($data['catch_price']);
        $data['cancellation_price'] = FormatHelper::brlTodecimal($data['cancellation_price']);
        unset($data['catchs_configuration_id']);
        try {
            CatchsConfiguration::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar configuração de apanha', $e->getMessage());
        }
    }
}
