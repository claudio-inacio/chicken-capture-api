<?php

namespace App\Repositories\Authentication;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Interfaces\Authentication\PersonRespositoryInterface;
use App\Models\Authentication\Person;
use Illuminate\Support\Facades\DB;

class PersonRepository implements PersonRespositoryInterface
{
    public function getAll()
    {
        return Person::all();
    }

    public function getByName(string $name)
    {
        return Person::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('authentication.person');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('person.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['person.*']);

        $result = $query->get();

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return Person::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return Person::create($value);
    }

    public function update(int $id, array $data)
    {
        return Person::whereId($id)->update($data);
    }
}
