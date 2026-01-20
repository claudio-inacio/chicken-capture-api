<?php

namespace App\Repositories\Catch;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Catch\ExtraCatchRepositoryInterface;
use App\Models\Utils\ExtraCatchConfiguration;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class ExtraCatchRepository implements ExtraCatchRepositoryInterface
{
    public function getAll()
    {
        return ExtraCatchConfiguration::all();
    }

    #[ArrayShape(['data' => "mixed", 'total' => "int"])]
    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('catch.extra_catch_configuration')
            ->join('catch.catch_type', 'catch_type.id', '=', 'catchs_configuration.catch_type_id')
            ->join('main.company', 'company.id', '=', 'catchs_configuration.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('extra_catch_configuration.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'extra_catch_configuration.*',
            'catch_type.name as catch_type_name',
            'company.name as company_name'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $item){
            $item->bonus_amount = FormatHelper::decimalFourToBr($item->bonus_amount);
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return ExtraCatchConfiguration::find($id);
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        $value['bonus_amount'] = FormatHelper::brlToFourDecimal($value['bonus_amount']);
        try {
            $extraCatchVerify = ExtraCatchConfiguration::where('catch_type_id', $value['catch_type_id'])
                ->where('company_id', $value['company_id'])
                ->where('enabled', true)
                ->first();

            if ($extraCatchVerify) {
                return ResponseService::businessError('Ja existe uma configuração para esse tipo de apanha.');
            }

            $extraCatchConfig = ExtraCatchConfiguration::create($value);
            return ResponseService::success('Configuração cadastrada com sucesso!', [
                'extra_catch_configuration' => $extraCatchConfig->id
            ]);
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar configuração de apanha extra', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        $data['bonus_amount'] = FormatHelper::brlToFourDecimal($data['bonus_amount']);

        try {
            $catchConfigVerify = ExtraCatchConfiguration::where('catch_type_id', $data['catch_type_id'])
                ->where('enabled', true)
                ->where('id', '<>', $data['extra_catch_id'])
                ->first();

            if ($catchConfigVerify)
                return ResponseService::businessError('Ja existe uma configuração para esse tipo de apanha.');

            unset($data['extra_catch_id']);

            ExtraCatchConfiguration::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em alterar configuração de apanha extra', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            ExtraCatchConfiguration::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em ATIVAR/DESATIVAR configuração de apanha extra', $e->getMessage());
        }
    }
}
