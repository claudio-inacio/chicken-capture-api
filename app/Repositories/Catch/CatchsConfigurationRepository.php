<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Catch\CatchsConfigurationRespositoryInterface;
use App\Models\Catch\CatchConfigurationHistory;
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
        $query = DB::table('catch.catchs_configuration')
            ->join('catch.catch_type', 'catch_type.id', '=', 'catchs_configuration.catch_type_id')
            ->join('main.company', 'company.id', '=', 'catchs_configuration.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('catchs_configuration.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'catchs_configuration.*',
            'catch_type.name as catch_type_name',
            'company.name as company_name'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $item){
            $item->catch_price = FormatHelper::decimalFourToBr($item->catch_price);
            $item->cancellation_price = FormatHelper::decimalFourToBr($item->cancellation_price);
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return CatchsConfiguration::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['catch_price'] = FormatHelper::brlToFourDecimal($value['catch_price']);
        $value['cancellation_price'] = FormatHelper::brlToFourDecimal($value['cancellation_price']);
        try {
            $catchConfigVerify = CatchsConfiguration::where('catch_type_id', $value['catch_type_id'])
                ->where('enabled', true)
                ->first();

            if ($catchConfigVerify)
                return ResponseService::businessError('Ja existe uma configuração para esse tipo de apanha.');

            DB::beginTransaction();
            $catchConfig = CatchsConfiguration::create($value);
            unset($value['enabled']);

            $value['catch_configuration_id'] = $catchConfig->id;
            CatchConfigurationHistory::create($value);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar configuração de apanha', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['catch_price'] = FormatHelper::brlToFourDecimal($data['catch_price']);
        $data['cancellation_price'] = FormatHelper::brlToFourDecimal($data['cancellation_price']);

        try {
            $catchConfigVerify = CatchsConfiguration::where('catch_type_id', $data['catch_type_id'])
                ->where('enabled', true)
                ->where('id', '<>', $data['catchs_configuration_id'])
                ->first();

            if ($catchConfigVerify)
                return ResponseService::businessError('Ja existe uma configuração para esse tipo de apanha.');

            unset($data['catchs_configuration_id']);

            DB::beginTransaction();
            CatchsConfiguration::whereId($id)->update($data);

            unset($data['enabled']);
            $data['catch_configuration_id'] = $id;
            CatchConfigurationHistory::create($data);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em alterar configuração de apanha', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            CatchsConfiguration::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em ATIVAR/DESATIVAR configuração de apanha', $e->getMessage());
        }
    }
}
