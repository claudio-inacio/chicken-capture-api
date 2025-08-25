<?php

namespace App\Jobs\Command;

use App\Models\Credential;
use App\Services\Command\Job\SendGenericMessageJobService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendGenericMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $message;
    public Credential $credential;
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $message,
        Credential $credential,
    )
    {
        $this->message = $message;
        $this->credential = $credential;
    }

    /**
     * @throws Exception
     */
    public function handle(): bool
    {
        return SendGenericMessageJobService::run($this->message, $this->credential);
    }

    public function tags(): array
    {
        return ['alert-admins'];
    }
}
