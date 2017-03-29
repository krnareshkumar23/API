<?php

namespace App\Console\Commands;

use App\Models\User;
use App\ReceiptValidator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSubscriptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all users who have subscriptions that haven\'t been checked recently.';

    /**
     * Create a new command instance.
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
        $validator = new ReceiptValidator();
        $checkBefore = env('RECEIPT_DEBUG') ? Carbon::now()->subMinute() : Carbon::now()->subHours(6);

        User
            ::whereNotNull('dealer_rank_id')
            ->where('receipt_checked_at', '<', $checkBefore)
            ->chunk(50, function ($users) use ($validator) {
                foreach ($users as $user) {
                    $this->checkUser($validator, $user);
                }
            });
    }

    /**
     * Check if a user still has an active subscription
     *
     * @param ReceiptValidator $validator
     * @param User $user
     */
    public function checkUser(ReceiptValidator $validator, User $user)
    {
        $this->info('Checking user ' . $user->id);
        $rank = $validator->validateSubscription($user->receipt, $user->receipt_type);

        if ($rank) {
            $user->dealerRank()->associate($rank);
            $user->receipt_checked_at = Carbon::now();
        } else {
            $user->dealerRank()->dissociate();
            $user->receipt = null;
            $user->receipt_type = null;
            $user->receipt_checked_at = null;
        }

        $user->save();
    }
}
