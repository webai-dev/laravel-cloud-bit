<?php

namespace App\Notifications;

use App\Channels\NotificationChannel;
use App\Enums\NotificationCategories;
use App\Enums\NotificationSubcategories;
use App\Enums\NotificationTypes;
use App\Models\Teams\Invitation;
use App\Util\NotificationPayload;

class InvitationAcceptedNotification extends YBitNotification {
    private $invitation;

    public function __construct(Invitation $invitation) {
        parent::__construct();
        $this->category = NotificationCategories::TEAM_ACTIONS;
        $this->subcategory = NotificationSubcategories::TEAM_GENERAL;
        $this->invitation = $invitation;
    }

    public function via($notifiable) {
        return [NotificationChannel::class];
    }

    public function toNotification($notifiable) {
        $invitation = $this->invitation;

        return NotificationPayload::instance()
            ->forUser($notifiable->id)
            ->inTeam($invitation->team_id)
            ->ofType($this->getType())
            ->withTimestamp($invitation->created_at->toDatetimeString())
            ->withCategory($this->category)
            ->withSubcategory($this->subcategory);
    }

    protected function getType() {
        return NotificationTypes::WELCOME;
    }
}