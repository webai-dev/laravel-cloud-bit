<?php

namespace App\Console\Commands\Initialize;

use App\Enums\NotificationCategories;
use App\Enums\NotificationSubcategories;
use App\Models\User;
use App\Util\NotificationPayload;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Console\Command;

class TeamNotifications extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the notifications collection for each team-user combination';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param FirestoreClient $firestore
     * @return mixed
     */
    public function handle(FirestoreClient $firestore) {
        $users = User::count();
        $bar = $this->output->createProgressBar($users);
        $count = 0;

        User::chunk(100, function ($users) use ($bar, &$count, $firestore) {
            foreach ($users as $user) {
                $teams = $user->teams;
                foreach ($teams as $team) {
                    if (!$this->isInitialized($firestore, $team, $user)) {
                        $this->init($firestore, $team, $user);
                        $count++;
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->info("$count user initial notification directories created.");
    }

    public static function isInitialized(FirestoreClient $firestore, $team, $user) {
        $userId = $user->id;
        $teamId = $team->id;

        $docRef = $firestore->document("users/$userId/teams/$teamId/notifications/init");
        $snapshot = $docRef->snapshot();
        return $snapshot->exists();
    }

    public static function init(FirestoreClient $firestore, $team, $user) {
        $userId = $user->id;
        $teamId = $team->id;

        $firestore->document("users/{$userId}/teams/{$teamId}/notifications/init")
            ->set([
                NotificationPayload::instance()
                    ->ofType('welcome')
                    ->withCategory(NotificationCategories::TEAM_ACTIONS)
                    ->withSubcategory(NotificationSubcategories::TEAM_GENERAL)
                    ->toArray(['team_id', 'user_id', 'timestamp'])
            ]);
    }
}
