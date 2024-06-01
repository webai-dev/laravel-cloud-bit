<?php

namespace App\Tracing\Traits;

use App\Tracing\Facades\Activity;
use Illuminate\Database\Eloquent\Model;

trait Traceable {

    public static function bootTraceable() {
        static::created(function (Model $item) {
            if (!isset(static::$exclude_default_events) || !in_array('created', static::$exclude_default_events)) {
                $item->trace('create');
                Activity::major($item);
                $item->cleanTraces();
            }
        });

        static::updating(function (Model $item) {
            if (!isset(static::$exclude_default_events) || !in_array('updating', static::$exclude_default_events)) {
                $item->trace('edit');
                $model = get_class($item);
                $previous_item_state = $model::find($item->id);
                Activity::major($item, $previous_item_state);
                $item->cleanTraces();
            }
        });

        static::deleting(function (Model $item) {
            if (!isset(static::$exclude_default_events) || !in_array('deleting', static::$exclude_default_events)) {
                if (!method_exists($item, 'isForceDeleting') || $item->isForceDeleting()) {
                    $item->trace('delete');
                } else {
                    $item->trace('trash');
                }
                Activity::major($item);
                $item->cleanTraces();
            }
        });
    }

    public function activities() {
        return $this->morphMany('App\Models\Activity', 'target');
    }

    public function major_activities() {
        return $this->morphMany('App\Models\Activity', 'target')->where('major', 1);
    }

    public function minor_activities() {
        return $this->morphMany('App\Models\Activity', 'target')->where('major', 0);
    }

    public function trace($action, $metadata = [], $custom_changes = []) {
        if (!isset($this->traces)) {
            $this->traces = [
                'action'         => $action,
                'metadata'       => $metadata,
                'custom_changes' => $custom_changes,
            ];
        }
        return $this;
    }

    public function cleanTraces() {
        if (isset($this->traces)) {
            unset($this->traces);
        }
        return $this;
    }

    public function triggerEvent($event) {
        return $this->fireModelEvent($event, false);
    }
}