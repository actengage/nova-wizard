<?php

namespace Actengage\Wizard\Jobs;

use Actengage\Wizard\Session;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearExpiredSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The expiration date.
     *
     * @var \Carbon\Carbon
     */
    protected $expiresAt;

    /**
     * The where filters
     *
     * @var array
     */
    protected $wheres;

    /**
     * Create a new job instance.
     *
     * @param  \Carbon\Carbon  $expiresAt
     * @return void
     */
    public function __construct(array $wheres = [], ?Carbon $expiresAt = null)
    {
        $this->wheres = $wheres;
        $this->expiresAt = $expiresAt ?? now()->sub(
            config('wizard.session.ttl', '1 day')
        );
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Session::where($this->wheres)
            ->where('created_at', '<=', $this->expiresAt)
            ->get()
            ->map
            ->delete();
    }
}