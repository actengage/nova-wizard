<?php

namespace Actengage\Wizard\Console\Commands;

use Actengage\Wizard\Jobs\ClearExpiredSessions as Job;
use Illuminate\Console\Command;

class ClearExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:wizard
        {--id= : Clear a specific session id}
        {--user= : Clear all sessions for a specific user id}
        {--user-type= : Used with the user option. Specificy a user model type for for duplicate ID\'s.}
        {--ttl= : Override the TTL defined in the config}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the expired wizard sessions.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Job::dispatch(array_filter([
            'id' => $this->option('id'),
            'user_id' => $this->option('user'),
            'user_type' => $this->option('user-type'),
        ]), $this->option('ttl') ? now()->sub($this->option('ttl')) : null);

        return 0;
    }
}