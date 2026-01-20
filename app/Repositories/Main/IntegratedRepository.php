<?php

namespace App\Repositories\Main;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Main\IntegratedRepositoryInterface;
use App\Models\Main\Integrated;
use App\Models\Region\City;
use App\Services\ResponseService;
use Illuminate\Support\Facades\DB;

class IntegratedRepository implements IntegratedRepositoryInterface
{
    public function getAll()
    {
        return Integrated::all();
    }

    public function getByName(string $name)
    {
        return Integrated::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('main.integrated')
            ->join('region.city', 'city.id', '=', 'integrated.city_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('integrated.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select([
            'integrated.*',
            'city.name as city_name', 'city.uf as city_uf', 'city.code as city_code'
        ]);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Integrated::where('id',$id)->get();
    }

    public function create(array $value): \Illuminate\Http\JsonResponse
    {
        try {
            $integrated = Integrated::where('name', $value['name'])->first();
            if ($integrated) return ResponseService::businessError('Ja existe uma integraçao com esse nome!', [
                'integrated' => $integrated->id
            ]);

            $city = City::find($value['city_id']);
            if (!$city) return ResponseService::businessError('Cidade não encontrado no banco de dados!');

            $integrated = Integrated::create($value);
            return ResponseService::success('Integraçao cadastrada com sucesso!', [
                'integrated' => $integrated->id
            ]);
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em registrar integraçao', $e->getMessage());
        }
    }

    public function update(int $id, array $data): \Illuminate\Http\JsonResponse
    {
        unset($data['integrated_id']);
        try {
            $integrated = Integrated::where('name', $data['name'])
                ->where('id', '<>', $id)->first();

            if ($integrated) return ResponseService::businessError('Ja existe uma integraçao com esse nome!');

            $city = City::find($data['city_id']);
            if (!$city) return ResponseService::businessError('Cidade não encontrado no banco de dados!');

            Integrated::whereId($id)->update($data);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha em alterar integraçao', $e->getMessage());
        }
    }

    public function enable(int $id, bool $enable): \Illuminate\Http\JsonResponse
    {
        try {
            Integrated::whereId($id)->update(['enabled' => $enable]);
            return ResponseService::success204();
        } catch (\Exception $e){
            return ResponseService::internalServerError('Falha Ativar/Desativar integraçao', $e->getMessage());
        }
    }
}
