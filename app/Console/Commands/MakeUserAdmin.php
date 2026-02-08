<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : The email of the user to make admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to administrator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ User with email '{$email}' not found.");

            return Command::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("ℹ️  User '{$user->name}' is already an administrator.");

            return Command::SUCCESS;
        }

        $user->update(['is_admin' => true]);

        $this->info("✅ User '{$user->name}' ({$email}) is now an administrator!");

        return Command::SUCCESS;
    }
}
