<?php

namespace App\Http\Controllers\Api\Main;

use App\Helpers\FormatHelper;
use App\Http\Controllers\Controller;
use App\Interfaces\Main\DiaristRepositoryInterface;
use Exception;
use Illuminate\Http\Request;

class DiaristController extends Controller
{
    private DiaristRepositoryInterface $diaristRepository;

    public function __construct
    (
        DiaristRepositoryInterface $diaristRepository
    )
    {
        $this->diaristRepository = $diaristRepository;
    }

    /**
     * @throws Exception
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone_number' => 'required',
            'diarist_group_id' => 'required',
            'date' => 'required',
            'paid' => 'required'
        ]);

        if (strtolower($request->paid) == 'sim'){
            $request->validate(['proof_of_payment' => 'required']);
            $request->validate(['status_proof_of_payment' => 'required']);
        }

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['credential_id'] = $request->user()->id;
        $arrayData['phone_number'] = FormatHelper::removeSpecialCaracterTel($arrayData['phone_number']);
        $arrayData['date'] = (new \DateTime($arrayData['date']))->format('Y-m-d');
        $request->daily ? $arrayData['daily'] = FormatHelper::brlTodecimal($request->daily) : $arrayData['daily'] = 0;

        if (!$request->document) {
            $arrayData['document'] = null;
        }

        return $this->diaristRepository->create($arrayData, $request->user());
    }

    public function list(Request $request)
    {
        $whereCriterious = $request->where ?? false;
        $selectConfig = $request->selectConfig ?? false;
        if (!$selectConfig)
            return response()->json(['message' => 'Select config is required!!!'], 422);
        if (!$whereCriterious)
            return response()->json(['message' => 'Where config is required!!!'], 422);

        return response()->json($this->diaristRepository->findAll($selectConfig, $whereCriterious, $request->user()));
    }

    /**
     * @throws Exception
     */
    public function select(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'document' => 'required_without:phone_number',
            'phone_number' => 'required_without:document',
            'start_date' => 'required',
            'end_date' => 'required'
        ]);

        $arrayData = $request->all();

        if ($request->document and !$request->phone_number) {
            $arrayData['document'] = FormatHelper::formatCnpjCpf($arrayData['document']);
            $arrayData['phone_number'] = null;
        }
        if ($request->phone_number and !$request->document) {
            $arrayData['phone_number'] = FormatHelper::removeSpecialCaracterTel($arrayData['phone_number']);
            $arrayData['document'] = null;
        }

        $arrayData['start_date'] = FormatHelper::dateToUsTimeStamp($arrayData['start_date']);
        $arrayData['end_date'] = FormatHelper::dateToUsTimeStamp($arrayData['end_date']);

        return response()->json($this->diaristRepository->select($arrayData));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone_number' => 'required',
            'diarist_group_id' => 'required',
            'date' => 'required',
            'diarist_id' => 'required'
        ]);

        $arrayData = $request->all();
        $arrayData['company_id'] = $request->user()->company_id;
        $arrayData['phone_number'] = FormatHelper::removeSpecialCaracterTel($arrayData['phone_number']);
        $arrayData['date'] = (new \DateTime($arrayData['date']))->format('Y-m-d');
        $request->daily ? $arrayData['daily'] = FormatHelper::brlTodecimal($request->daily) : $arrayData['daily'] = 0;

        if (!$request->document) {
            $arrayData['document'] = null;
        }

        return $this->diaristRepository->update($request->diarist_id, $arrayData, $request->user());
    }

    public function enable(Request $request)
    {
        $request->validate([
            'diarist_id' => 'required',
            'enabled' => 'required',
        ]);

        return $this->diaristRepository->enable($request->diarist_id, $request->enabled);
    }
}
