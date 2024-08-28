<?php

namespace App\Repositories\Main;

use App\Enum\Financial\StatusEnum;
use App\Enum\Financial\TableReferenceFinanceEnum;
use App\Enum\Financial\TypeFinanceEnum;
use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Main\DiaristRepositoryInterface;
use App\Models\Financial\FinancialAccounts;
use App\Models\Main\Diarist;
use App\Models\Main\DiaristGroup;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DiaristRepository implements DiaristRepositoryInterface
{
    public function getAll()
    {
        return Diarist::all();
    }

    public function findAll($selectConfig, array $whereCriterious, $credential) : array
    {
        $query = DB::table('main.diarist')
            ->join('main.diarist_group', 'diarist_group.id', '=', 'diarist.diarist_group_id')
            ->join('main.company', 'company.id', '=', 'diarist.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

//        if ($credential->access_group_id != AccessGroupEnum::DEVELOPER and $credential->access_group_id != AccessGroupEnum::ADMINISTRATIVE){
//            $query->where('diarist.company_id', $credential->company_id);
//        }

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);

        $query->where('diarist.enabled', true);
        $query->select([
            'diarist.*',
            'company.name as company_name',
            'diarist_group.function_name', 'diarist_group.daily'
        ]);

        $result = $query->get()->toArray();

        $arrayReturn = [];
        foreach ($result as $item){
            if (!key_exists($item->phone_number, $arrayReturn)) {
                $arrayReturn[$item->phone_number] = $item;
                $arrayReturn[$item->phone_number]->total_daily = 1;
            } else {
                $arrayReturn[$item->phone_number]->daily = $arrayReturn[$item->phone_number]->daily + $item->daily;
                $arrayReturn[$item->phone_number]->total_daily = $arrayReturn[$item->phone_number]->total_daily + 1;
            }
        }

        foreach ($arrayReturn as $item){
            if (is_numeric($item->daily))
                $item->daily = FormatHelper::decimalToBr($item->daily);
        }

        return [
            'data' => $arrayReturn,
            'total' => count($arrayReturn),
        ];
    }

    public function select(array $arrayData): array
    {
        $query = DB::table('main.diarist')
            ->join('main.diarist_group', 'diarist_group.id', '=', 'diarist.diarist_group_id')
            ->join('main.company', 'company.id', '=', 'diarist.company_id')
            ->where(function($query) use ($arrayData) {
                if ($arrayData['document'] != null) {
                    $query->orWhere('document', $arrayData['document']);
                }
                if ($arrayData['phone_number'] != null) {
                    $query->orWhere('phone_number', $arrayData['phone_number']);
                }
            });

        $total = $query->count('diarist.id');

        $query->select([
            'diarist.*',
            'company.name as company_name',
            'diarist_group.function_name as diarist_group_function_name', 'diarist_group.daily as diarist_group_daily'
        ]);

        $result = $query->get()->toArray();

        foreach ($result as $key => $item){
            if ($item->diarist_group_id != 0){
                $item->daily = $item->diarist_group_daily;
            }

            unset($item->diarist_group_daily);
            $item->daily = FormatHelper::decimalToBr($item->daily);
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }

    public function create(array $arrayData, $credential): JsonResponse
    {
        DB::beginTransaction();
        try {
            $diarist = Diarist::where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->whereDay('date', date('d', strtotime($arrayData['date'])))
                ->where(function($query) use ($arrayData) {
                    if ($arrayData['document'] != null)
                        $query->where('document', $arrayData['document']);
                    if ($arrayData['phone_number'] != null)
                        $query->where('phone_number', $arrayData['phone_number']);
                })
                ->first();

            if ($diarist) {
                $day = (new \DateTime($arrayData['date']))->format('d-m-Y');
                return ResponseService::businessError('Esse diarista ja foi cadastrada para o dia -> '. $day);
            }

            $diarist = Diarist::create($arrayData);

            if ($arrayData['daily'] == 0) {
                $diaristGroup = DiaristGroup::find($arrayData['diarist_group_id']);
                $arrayData['daily'] = $diaristGroup->daily;
            } else {
                $diaristGroup = new \stdClass();
                $diaristGroup->function_name = 'NAO_CONTEM';
            }

            $document = $diarist->document ?? 'NAO CONTEM!';
            $phoneNumber = $diarist->phone_number ?? 'NAO CONTEM!';
            FinancialAccounts::create([
                'description' => "Cadastro de diarista. Nome: {$diarist->name}, CPF: {$document}, CELULAR: {$phoneNumber}, FUNÇÃO: {$diaristGroup->function_name}. ",
                'amount' => $arrayData['daily'],
                'due_date' => (new \DateTime($arrayData['date']))->format('Y-m-d'). " 20:00:00",
                'type' => TypeFinanceEnum::TO_DISCOUNT,
                'credential_id' => $credential->id,
                'company_id' => $credential->company_id,
                'reference_id' => $diarist->id,
                'table_reference_id' => TableReferenceFinanceEnum::DIARIST,
                'status_id' => StatusEnum::TO_DISCOUNT
            ]);

            DB::commit();
            return ResponseService::success204();
        } catch (\Exception $e){
            DB::rollBack();
            return ResponseService::internalServerError('Falha em registrar diarista', $e->getMessage());
        }
    }

    public function update(int $id, array $arrayData): JsonResponse
    {
        unset($arrayData['diarist_id']);
        try {
            $diarist = Diarist::where('id', '<>', $id)
                ->where('enabled', true)
                ->where('company_id', $arrayData['company_id'])
                ->whereDay('date', date('d', strtotime($arrayData['date'])))
                ->where(function($query) use ($arrayData) {
                    if ($arrayData['document'] != null)
                        $query->where('document', $arrayData['document']);
                    if ($arrayData['phone_number'] != null)
                        $query->where('phone_number', $arrayData['phone_number']);
                })
                ->first();

            if ($diarist) {
                $day = (new \DateTime($arrayData['date']))->format('d-m-Y');
                return ResponseService::businessError('Esse diarista ja foi cadastrada para o dia -> '. $day);
            }

            Diarist::whereId($id)->update($arrayData);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em  registrar diarista', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): JsonResponse
    {
        try {
            Diarist::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar diarista', $e->getMessage());
        }
    }
}
