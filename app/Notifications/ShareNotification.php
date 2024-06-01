<?php

namespace App\Notifications;

use App\Enums\NotificationCategories;
use App\Enums\NotificationSubcategories;
use App\Enums\NotificationTypes;
use App\Util\NotificationPayload;
use App\Channels\NotificationChannel;
use App\Models\Bits\Bit;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;

class ShareNotification extends YBitNotification
{
    private $share;

    public function __construct(Share $share) {
        parent::__construct();
        $this->category = NotificationCategories::TEAM_ACTIONS;
        $this->subcategory = NotificationSubcategories::TEAM_SHARE;
        $this->share = $share;
    }

    public function via($notifiable) {
        return [NotificationChannel::class];
    }

    public function toNotification($notifiable) {
        $share = $this->share;

        return NotificationPayload::instance()
            ->forUser($notifiable->id)
            ->inTeam($share->team->id)
            ->ofType($this->getType())
            ->withTimestamp($share->created_at->toDatetimeString())
            ->withCategory($this->category)
            ->withSubcategory($this->subcategory)
            ->withPayload($this->getActionParams());
    }

    protected function getType() {
        $entity = $this->share->shareable;

        if ($entity instanceof Bit)
            return NotificationTypes::SHARE_BIT;

        if ($entity instanceof File)
            return NotificationTypes::SHARE_FILE;

        if ($entity instanceof Folder)
            return NotificationTypes::SHARE_FOLDER;

        $class = get_class($entity);
        throw new \Exception("Invalid entity type $class provided.");
    }

    private function getActionParams() {
        $entity = $this->share->shareable;
        $params = [
            "shareable" => [
                "id" => $entity->id,
                "title" => $entity->title,
            ],
            "creator" => [
                "id" => $this->share->created_by_id,
                "email" => $this->share->creator->email,
                "name"  => $this->share->creator->name,
            ]
        ];

        if ($this->getType() === NotificationTypes::SHARE_BIT)
            return array_merge($params, ["type" => $this->share->shareable_type]);

        return $params;
    }
}