<?php

namespace App\Repositories\Financial;

use App\Factory\SelectFactory;
use App\Factory\WhereFactory;
use App\Helpers\FormatHelper;
use App\Interfaces\Financial\MonthlyClosingReportsRepositoryInterface;
use App\Models\Financial\MonthlyClosingReports;
use Illuminate\Support\Facades\DB;

class MonthlyClosingReportsRepository implements MonthlyClosingReportsRepositoryInterface
{
    public function getAll()
    {
        return MonthlyClosingReports::all();
    }

    public function getByName(string $name)
    {
        return MonthlyClosingReports::where('name', $name)->get();
    }

    public function findAll($selectConfig, array $whereCriterious) : array
    {
        $query = DB::table('financial.monthly_closing_reports')
            ->join('main.company', 'company.id', '=', 'monthly_closing_reports.company_id');

        $whereFactory = new WhereFactory();
        $query = $whereFactory->byArray($query, $whereCriterious);

        $total = $query->count('monthly_closing_reports.id');

        $selectFactory = new SelectFactory();
        $query = $selectFactory->byArray($query, $selectConfig);
        $query->select(['monthly_closing_reports.*', 'company.name as company_name']);

        $result = $query->get();
        foreach ($result as $item){
            $item->total_expenses = FormatHelper::decimalToBr($item->total_expenses);
            $item->total_income = FormatHelper::decimalToBr($item->total_income);
        }

        return [
            'data' => $result->toArray(),
            'total' => $total,
        ];
    }


    public function getById(int $id)
    {
        return MonthlyClosingReports::where('id',$id)->get();
    }

    public function create(array $value)
    {
        return MonthlyClosingReports::create($value);
    }

    public function update(int $id, array $data)
    {
        return MonthlyClosingReports::whereId($id)->update($data);
    }
}
