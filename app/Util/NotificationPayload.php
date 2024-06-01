<?php

namespace App\Util;

class NotificationPayload
{
    private $attributes = [
        "user_id" => null,
        "team_id" => null,
        "timestamp" => null,
        "type" => null,
        "category" => null,
        "subcategory" => null,
        "payload" => null,
        "marked_as_seen" => false,
    ];

    public function __construct() {
        $this->attributes['timestamp'] = \Carbon\Carbon::now()->toDateTimeString();
    }

    public function inTeam($team_id) {
        $this->attributes['team_id'] = $team_id;
        return $this;
    }

    public function forUser($user_id) {
        $this->attributes['user_id'] = $user_id;
        return $this;
    }

    public function withCategory($category) {
        $this->attributes['category'] = $category;
        return $this;
    }

    public function withSubcategory($subcategory) {
        $this->attributes['subcategory'] = $subcategory;
        return $this;
    }

    public function withTimestamp($timestamp) {
        $this->attributes['timestamp'] = $timestamp;
        return $this;
    }

    public function withPayload($payload) {
        $this->attributes['payload'] = $payload;
        return $this;
    }

    public function ofType($type) {
        $this->attributes['type'] = $type;
        return $this;
    }

    public function __get($name) {
        return $this->attributes[$name];
    }

    public function toArray($except=[]) {
        $res = [];
        foreach ($this->attributes as $key=>$val)
            if (!in_array($key, $except))
                $res[$key] = $val;

        return $res;
    }

    public static function instance() {
        return (new static());
    }
}