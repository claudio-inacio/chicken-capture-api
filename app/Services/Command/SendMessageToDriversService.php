<?php

namespace App\Services\Command\Proposal;

use App\Enum\Authentication\AccessGroupEnum;
use App\Jobs\Command\SendMessageToDriverJob;
use App\Models\Credential;
use App\Services\Main\LogService;
use Illuminate\Support\Facades\DB;

class SendMessageToDriversService
{
    public static function sendMessage(): bool
    {
        $drivers = Credential::query()
            ->select(
                'credential.id',
                'credential.document',
                'person.name',
                'person.phone_number'
            )
            ->join('authentication.person', 'person.id', '=', 'credential.person_id')
            ->where('credential.access_group_id', AccessGroupEnum::DRIVER)
            ->where('person.enabled', true)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vehicles.driver_area as da')
                    ->whereRaw('da.credential_id = credential.id')
                    ->whereDate('da.daily_start_time', '=', DB::raw('CURRENT_DATE'));
            })
            ->get();

        LogService::save("UpdateProposalCommandService::INIT_UPDATE", [
            "result" => $drivers
        ]);

        foreach ($drivers as $driver) {
            SendMessageToDriverJob::dispatch($driver)->onQueue('message-drivers');
        }

        return true;
    }
}
