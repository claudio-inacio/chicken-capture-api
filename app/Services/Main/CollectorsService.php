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
            $arrayRequest['team_id'] ? $teamId = $arrayRequest['team_id'] : $teamId = null;
            $arrayCollectors = $arrayRequest['collectors'];
            $errors = [];

            $teams = Team::where('company_id', $user->company_id)
                ->where(function($query) use ($teamId) {
                    if ($teamId != null) {
                        $query->where('id', '<>', $teamId);
                    }
                })->get();

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

            $arrayGroupId = CollectorsGroup::all('id')->toArray();
            if (!empty($arrayCollectorsUsed)) {
                $arrayCollectorsQuantity = Collectors::whereIn('collectors_group_id', $arrayGroupId)
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
                    'message' => 'Falha em tentar selecionar coletores COD1',
                    'error' => $errors
                ];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Falha em tentar cadastrar time COD2',
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

                if (key_exists($collectorGroup['id'], $arrayResult))
                    $arrayResult[$collectorGroup['id']] = $arrayCollectors[$collectorGroup['id']] - $arrayTeamQuantity[$collectorGroup['id']];
                else {
                    if (key_exists($collectorGroup['id'], $arrayCollectors))
                        $arrayResult[$collectorGroup['id']] = $arrayCollectors[$collectorGroup['id']];
                    else $arrayResult[$collectorGroup['id']] = 0;
                }
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
            return response()->json([
                'success' => false,
                'message' => 'Falha em obter quantidade de coletores disponíveis',
                'error' => [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }

    public static function addNewsCollectors($arrayCollectors): array {
        try {
            // IDs dos group_collectors fornecidos
            $providedIds = array_column($arrayCollectors['group_collectors'], 'id');

            // Consulta para verificar se todos os IDs existem na tabela
            $collectorsGroup = CollectorsGroup::where('enabled', true)
                ->whereIn('id', $providedIds)
                ->pluck('id') // Retorna apenas os IDs encontrados
                ->toArray();

            $groupCollectorIds = array_map(function ($group) {
                return $group['id'];
            }, $arrayCollectors['group_collectors']);

            $missingIds = array_diff($groupCollectorIds, $collectorsGroup);

            // Valida se todos os IDs fornecidos estão na tabela
            $groupIsValid = empty(array_diff($providedIds, $collectorsGroup));

            if (!$groupIsValid) return [
                'success' => false,
                'message' => "Os seguintes IDs não foram encontrados na tabela groupCollectors: " . implode(', ', $missingIds),
                'error' => ""
            ];

            foreach ($arrayCollectors as $collectors) {
                foreach ($collectors as $collector) {
                    Collectors::where('collectors_group_id', $collector['id'])
                        ->where('enabled', true)
                        ->increment('quantity', $collector['quantity_collectors']);
                }
            }

            return ['success' => true];
        }catch (\Exception $e){
            return [
                'success' => false,
                'message' => 'Falha em atualizar coletores',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function removeCollectors($arrayCollectors, $teamId): array {
        try {
            // IDs dos group_collectors fornecidos
            $providedIds = array_column($arrayCollectors['group_collectors'], 'id');

            // Consulta para verificar se todos os IDs existem na tabela
            $collectorsGroup = CollectorsGroup::where('enabled', true)
                ->whereIn('id', $providedIds)
                ->pluck('id') // Retorna apenas os IDs encontrados
                ->toArray();

            $groupCollectorIds = array_map(function ($group) {
                return $group['id'];
            }, $arrayCollectors['group_collectors']);

            $missingIds = array_diff($groupCollectorIds, $collectorsGroup);

            // Valida se todos os IDs fornecidos estão na tabela
            $groupIsValid = empty(array_diff($providedIds, $collectorsGroup));

            if (!$groupIsValid) return [
                'success' => false,
                'message' => "Os seguintes IDs não foram encontrados na tabela groupCollectors: " . implode(', ', $missingIds),
                'error' => ""
            ];

            $team = Team::find($teamId);
            $collectorsTeam = json_decode($team->collectors, true);

            // Processar coletores para decrement e increment
            // reduzindo a quantidade de coletores referente ao time
            // e adcionando a nova quantia de coletores

            foreach ($arrayCollectors as $collectors) {
                foreach ($collectors as $collector) {
                    $collectorModel = Collectors::where('collectors_group_id', $collector['id'])
                        ->where('enabled', true)->first();

                    if (!$collectorModel) {
                        continue;
                    }

                    foreach ($collectorsTeam as $collectorTeam) {
                        foreach ($collectorTeam as $collectorTeamItem) {
                            if ($collectorTeamItem['id'] == $collector['id']){
                                $collectorModel->decrement('quantity', $collectorTeamItem['quantity_collectors']);

                                break;
                            }
                        }
                    }

                    $collectorModel->increment('quantity', $collector['quantity_collectors']);
                }
            }

            return ['success' => true];
        }catch (\Exception $e){
            return [
                'success' => false,
                'message' => 'Falha em atualizar coletores',
                'error' => $e->getMessage()
            ];
        }
    }
}
