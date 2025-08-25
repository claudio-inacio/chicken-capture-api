<?php

namespace App\Jobs\Command;

use App\Services\Command\Job\SendMessageToDriverJobService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JetBrains\PhpStorm\Pure;

class SendMessageToDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public object $driver;
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        object $driver,
    )
    {
        $this->driver = $driver;
    }

    /**
     * @throws Exception
     */
    #[Pure] public function handle(): bool
    {
        return SendMessageToDriverJobService::run($this->driver);
    }

    public function tags(): array
    {
        return ['message-driver'];
    }
}
