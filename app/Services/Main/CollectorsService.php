<?php

namespace App\Services\Main;

use App\Helpers\FormatHelper;
use App\Models\Main\Collectors;
use App\Models\Main\CollectorsGroup;
use App\Models\Main\Team;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class  CollectorsService
{
    /**
     * @throws Exception
     */
    public static function verifyQuantityCollectors(array $arrayRequest, $user): array
    {
        try {
            $arrayCollectors = $arrayRequest['collectors'];
            $errors = [];

            $teams = Team::where('company_id', $user->company_id)->get();
            $arrayCollectorsUsed = [];
            $arrayTeamQuantity = [];

            foreach ($teams as $team) {
                $groupCollectors = json_decode($team->collectors, true)['group_collectors'];
                foreach ($groupCollectors as $collector) {
                    $collectorId = $collector['id'];
                    $quantity = $collector['quantity_collectors'];

                    // Incrementando as quantidades de coletores usados
                    $arrayCollectorsUsed[$collectorId] = ($arrayCollectorsUsed[$collectorId] ?? 0) + $quantity;
                    $arrayTeamQuantity[$collectorId] = ($arrayTeamQuantity[$collectorId] ?? 0) + $quantity;
                }
            }

            if (!empty($arrayCollectorsUsed)) {
                $arrayCollectorsQuantity = Collectors::whereIn('collectors_group_id', array_keys($arrayCollectorsUsed))
                    ->get()
                    ->groupBy('collectors_group_id')
                    ->mapWithKeys(function ($group, $id) {
                        return [$id => $group->sum('quantity')];
                    })
                    ->toArray();

                foreach ($arrayCollectors['group_collectors'] as $key => $collector) {
                    $collectorId = $collector['id'];
                    $requestedQuantity = $collector['quantity_collectors'];

                    if ($requestedQuantity <= 0) {
                        $collectorsGroup = CollectorsGroup::find($collectorId);
                        $errors[$key] = 'A quantidade de coletores para -> ' . $collectorsGroup->function_name . ' deve ser maior que 0';
                        continue;
                    }

                    $availableQuantity = ($arrayCollectorsQuantity[$collectorId] ?? 0) - ($arrayTeamQuantity[$collectorId] ?? 0);

                    if ($requestedQuantity > $availableQuantity) {
                        $collectorsGroup = CollectorsGroup::find($collectorId);
                        $errors[$key] = 'Limite de coletores da empresa já foi atingido para o grupo de coletores -> ' . $collectorsGroup->function_name;
                    }
                }
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Falha em tentar cadastrar time',
                    'error' => $errors
                ];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha em tentar cadastrar time',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function verifyQuantityAvailable(Request $request): JsonResponse {
        try {
            $user = $request->user();
            $collectorsGroup =  CollectorsGroup::where('company_id', $user->company_id)->get();
            $teams = Team::where('company_id', $user->company_id)->get();

            $arrayTeamQuantity = [];
            $arrayCollectors = [];
            $arrayResult = [];

            if ($teams) {
                foreach ($teams as $team) {
                    $groupCollectors = json_decode($team->collectors, true)['group_collectors'];
                    foreach ($groupCollectors as $collector) {
                        $collectorId = $collector['id'];
                        $quantity = $collector['quantity_collectors'];

                        // Incrementando as quantidades de coletores usados
                        $arrayTeamQuantity[$collectorId] = ($arrayTeamQuantity[$collectorId] ?? 0) + $quantity;
                    }
                }
            }

            foreach ($collectorsGroup as $collectorGroup){
                $collectors = Collectors::where('collectors_group_id', $collectorGroup['id'])->get();
                foreach ($collectors as $collector) {
                    $quantity = $collector['quantity'];
                    $collectorGroupId = $collector['collectors_group_id'];

                    // Incrementando as quantidades de coletores cadastrados
                    $arrayCollectors[$collectorGroupId] = ($arrayCollectors[$collectorGroupId] ?? 0) + $quantity;
                }

                if (!empty($arrayTeamQuantity))
                    $arrayResult[$collectorGroup['id']] = $arrayCollectors[$collectorGroup['id']] -  $arrayTeamQuantity[$collectorGroup['id']];
                else
                    $arrayResult[$collectorGroup['id']] = $arrayCollectors[$collectorGroup['id']];
            }

            $arrayReturn = [];
            foreach ($arrayResult as $key => $item){
                $item < 0 ? $arrayReturn[$key]['quantity_available'] = 0 : $arrayReturn[$key]['quantity_available'] = $item;
                $collectorsGroup = CollectorsGroup::find($key);
                $arrayReturn[$key]['collectors_group_function_name'] = $collectorsGroup->function_name;
                $arrayReturn[$key]['collectors_group_id'] = $collectorsGroup->id;
                $arrayReturn[$key]['salary'] = FormatHelper::decimalToBr($collectorsGroup->salary);
            }

            return ResponseService::success('Sucesso em listar quantidade de coletores disponiveis', array_values($arrayReturn));
        }catch (\Exception $e) {
            return ResponseService::internalServerError('Falha em obter quantidade de coletores disponíveis.', $e->getMessage());
        }
    }
}
