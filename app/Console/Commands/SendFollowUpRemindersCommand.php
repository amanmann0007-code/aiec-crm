<?php

namespace App\Console\Commands;

use App\Jobs\SendFollowUpReminders;
use Illuminate\Console\Command;

class SendFollowUpRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followups:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pending follow-up reminders';

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
     * @return int
     */
    public function handle()
    {
        SendFollowUpReminders::dispatchSync();
        $this->info('Follow-up reminders sent.');
        return 0;
    }
}
