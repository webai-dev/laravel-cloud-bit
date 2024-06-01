<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Represents a link between a Share and a user's folder
 * @package App\Models
 * @property int id
 * @property int user_id
 * @property int share_id
 * @property int folder_id
 * @property Folder folder
 * @property Share share
 * @property User user
 */
class Shortcut extends Model {
    protected $fillable = ['share_id', 'folder_id', 'user_id'];
    protected $table = 'share_folders';
    public $timestamps = false;

    public function folder() {
        return $this->belongsTo(Folder::class);
    }

    public function share() {
        return $this->belongsTo(Share::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Checks whether a shortcut can be created
     * in the specified folder, or throws an exception
     *
     * @param $folder Folder|null
     */
    public static function checkTarget($folder) {
        if ($folder == null) {
            return;
        }

        // Shortcuts can only be created in non-shared folders
        $user_id = Auth::id();
        if ($folder->user_id != $user_id
            || $folder->isRecursivelySharedWith($user_id)) {
            abort(400, __('shortcuts.invalid'));
        }
    }
}
