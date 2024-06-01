<?php

namespace App\Tracing\Facades;

use App\Models\Folder;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Activity {

    public static function major($new, $old = null) {
        return self::make(1, $new, $old);
    }

    public static function minor($new, $old = null) {
        return self::make(0, $new, $old);
    }

    private static function make($is_major, $new, $old = null) {
        return $new->activities()->create([
            'user_id'    => Auth::id(),
            'major'      => $is_major,
            'action'     => isset($new->traces) ? $new->traces['action'] : ($is_major == 1 ? 'edit' : 'open'),
            'changes'    => $is_major == 1 ? self::generateChanges($new, $old) : null,
            'metadata'   => self::generateMetadata($new),
            'created_at' => Carbon::now(),
        ]);
    }

    public static function generateChanges($new, $old, $json_encoded = true) {
        $changes = [];
        foreach ($new->getAttributes() as $key => $field) {
            if ($key == 'traces' && array_key_exists('custom_changes',
                    $new->traces) && count($new->traces['custom_changes']) > 0) {
                $changes['custom_changes'] = $new->traces['custom_changes'];
            }
            if ($new->traces['action'] === 'move' && $key === 'folder_id') {
                $changes['folder_name'] = [
                    'before' => Folder::find($old->{$key})->title,
                    'after' => Folder::find($new->{$key})->title,
                ];
            }
            if ($new->traces['action'] === 'teammate_removed' && $key === 'owner_id') {
                $changes['owner_name'] = [
                    'before' => User::find($old->{$key})->name,
                    'after' => User::find($new->{$key})->name,
                ];
            }
            if (!is_null($old) && !is_object($new->{$key}) && !is_array($new->{$key}) && isset($old->{$key}) && $old->{$key} != $new->{$key}) {
                $changes[$key] = [
                    'before' => $old->{$key},
                    'after'  => $new->{$key},
                ];
            }
        }

        return count($changes) == 0 ? null : ($json_encoded ? json_encode($changes) : $changes);
    }

    private static function generateMetadata($new, $json_encoded = true) {
        $metadata = [];
        if (isset($new->traces)) {
            if (count($new->traces['metadata']) > 0) {
                $metadata = $new->traces['metadata'];
            }

            if ($new->traces['action'] === 'share_add' || $new->traces['action'] === 'share_delete' || $new->traces['action'] === 'share_edit') {
                $metadata['user_name'] = User::find($metadata['user_id'])->name;
            }
        }
        return count($metadata) == 0 ? null : ($json_encoded ? json_encode($metadata) : $metadata);
    }
}