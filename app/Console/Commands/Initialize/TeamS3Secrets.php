<?php

namespace App\Console\Commands\Initialize;

use App\Models\Teams\Team;
use Illuminate\Console\Command;

class TeamS3Secrets extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:s3_secrets {team : The subdomain of the team to encrypt the secrets for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypts the S3 credentials of the specified team';

    public function handle() {
        $subdomain = $this->argument('team');
        /** @var Team $team */
        $team = Team::query()
            ->where('subdomain', $subdomain)
            ->first();

        if ($team == null){
            $this->error("Team with subdomain '$subdomain' not found");
            return;
        }

        if ($team->aws_key == null || $team->aws_secret == null){
            $this->error("Team credentials missing");
            return;
        }

        $team->aws_key = encrypt($team->aws_key);
        $team->aws_secret = encrypt($team->aws_secret);
        $team->save();

        $this->info("Finished encrypting team secrets");
    }
}
