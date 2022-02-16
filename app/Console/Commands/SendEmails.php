<?php

namespace App\Console\Commands;

use App\Models\Office;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a marketing email to a user';

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
        $office = Office::factory()->create();
        Notification::send(User::find($this->argument('user')), new OfficePendingApproval($office));

        return 0;
    }
}
