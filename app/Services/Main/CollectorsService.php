<?php

namespace App\Services\Main;

use App\Models\Main\Collectors;
use App\Models\Main\CollectorsGroup;
use App\Models\Main\Team;
use Exception;

class  CollectorsService
{
    /**
     * @throws Exception
     */
    public static function verifyQuantityCollectors(array $arrayRequest, $user): array
    {
        try {
            $arrayCollectors = $arrayRequest['collectors'];
            $arrayCollectorsUsed = [];
            $errors = [];

            $verifyTeams = Team::where('company_id', $user->company_id)
                ->get()
                ->toArray();

            foreach ($verifyTeams as $team) {
                $collectorVerify = json_decode($team['collectors'], true);
                foreach ($collectorVerify['group_collectors'] as $groupCollectorUsing) {
                    if (key_exists($groupCollectorUsing['id'], $arrayCollectorsUsed))
                        $arrayCollectorsUsed[$groupCollectorUsing['id']] = $arrayCollectorsUsed[$groupCollectorUsing['id']] + $groupCollectorUsing['quantity_collectors'];
                    else
                        $arrayCollectorsUsed[$groupCollectorUsing['id']] = $groupCollectorUsing['quantity_collectors'];
                }
            }

            if (!empty($arrayCollectorsUsed)) {
                $arrayCollectorsQuantity = [];
                foreach ($arrayCollectorsUsed as $collectorGroupId => $quantityUsed) {
                    $collectors = Collectors::where('collectors_group_id', $collectorGroupId)->get()->toArray();
                    foreach ($collectors as $collector) {
                        if ($collector['collectors_group_id'] == $collectorGroupId)
                            if (key_exists($collectorGroupId, $arrayCollectorsQuantity))
                                $arrayCollectorsQuantity[$collectorGroupId] = $arrayCollectorsQuantity[$collectorGroupId] + $collector['quantity'];
                            else
                                $arrayCollectorsQuantity[$collectorGroupId] = $collector['quantity'];
                    }
                }

                foreach ($arrayCollectors['group_collectors'] as $key => $collector) {
                    foreach ($arrayCollectorsQuantity as $collectorGroupId => $collectorQuantity) {
                        if ($collector['id'] == $collectorGroupId) {
                            if ($collector['quantity_collectors'] >= $collectorQuantity) {
                                $collectorsGroup = CollectorsGroup::find($collectorGroupId);
                                $errors[$key] = 'Limite de coletores da empresa ja foi atingido para o grupo de coletores -> ' . $collectorsGroup->function_name;
                            }
                        }
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

            return [
                'success' => true,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha em tentar cadastrar time',
                'error' => $e->getMessage()
            ];
        }
    }

}
