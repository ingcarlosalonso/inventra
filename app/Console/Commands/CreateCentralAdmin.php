<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class CreateCentralAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'central:create-admin {--name=Admin} {--email=} {--password=}';

    protected $description = 'Create a super-admin for the central admin panel';

    public function handle(): void
    {
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $name = $this->option('name');

        Admin::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => bcrypt($password)]
        );

        $this->info("Admin '{$email}' created/updated successfully.");
    }
}
