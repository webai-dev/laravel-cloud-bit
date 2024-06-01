<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Enums\Roles;
use App\Models\Role;
use App\Models\Teams\Team;
use App\Models\Teams\Invitation;
use App\Models\Bits\Bit;
use App\Tracing\Traits\Traceable;

/**
 * Class User
 *
 * @package App\Models
 * @property int id
 * @property string apparatus_id
 * @property string name
 * @property string email
 * @property string photo
 * @property string phone
 * @property boolean superuser
 * @property boolean banned
 * @property Collection teams
 */
class User extends Authenticatable {

    use Notifiable, Traceable;

    public static $exclude_default_events = ['created', 'deleting'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'photo', 'phone', 'apparatus_id', 'developer', 'has_accepted_terms_on'
    ];

    protected $visible = [
        'id', 'name', 'email', 'photo', 'phone', 'is_owner', 'role_id', 'superuser', 'developer',
    ];

    public function magic_token() {
        return $this->hasOne(UserMagicToken::class)->orderBy('created_at', 'DESC');
    }

    public function invitations() {
        return Invitation::where(function ($query) {
            $query->where('contact', $this->phone)
                  ->orWhere('contact', $this->email);
        });
    }

    public function teams() {
        return $this->belongsToMany(Team::class, 'team_users')->withPivot('developer');
    }

    public function owned_teams() {
        return $this->hasMany(Team::class);
    }

    public function isInTeam($team_id) {
        return $this->teams()->where('id', $team_id)->count() > 0;
    }

    public function isGuestUser($team) {
        if ($team->user_id === $this->id) {
            return false;
        }

        $team_role = $this->roles()->where('team_id', $team->id)->first();

        return !$team_role || $team_role->label === Roles::GUEST;
    }

    protected static function getByEmail($email) {
        return self::where('email', $email)->first();
    }

    public function files() {
        return $this->hasMany(File::class);
    }

    public function folders() {
        return $this->hasMany(Folder::class);
    }

    public function bits() {
        return $this->hasMany(Bit::class);
    }

    public function shares() {
        return $this->hasMany(Share::class);
    }

    public function roles() {
        return $this->belongsToMany(Role::class, 'user_team_roles');
    }

    public function hasRoleInTeam($role, $team_id) {
        return $this->roles()
                    ->where('label', $role)
                    ->where('user_team_roles.team_id', $team_id)
                    ->count() > 0;
    }

    public function setRoleInTeam($role, $team_id) {
        $role = Role::where('label', $role)->firstOrFail();
        $this->roles()->where('team_id', $team_id)->detach();
        $this->roles()->attach($role->id, compact('team_id'));
    }

    public function scopeCanSee($query, $shareable) {
        $table = $shareable->getTable();
        $id = $shareable->id;

        return $query->whereHas('shares', function ($query) use ($shareable) {
            $query->where('shareable_id', $shareable->id)
                  ->where('shareable_type', $shareable->getType());
        })//Is directly shared with them
                     ->orWhereHas($shareable->getTable(), function ($query) use ($shareable) {
            $query->where('id', $shareable->id);
        })//Is the owner
                     ->orWhereRaw(
            "users.id IN (
                WITH RECURSIVE permissions AS (
                  SELECT id,user_id,folder_id
                  FROM
                    $table
                    WHERE id = $id
                  UNION ALL
                    SELECT folders.id,folders.user_id,folders.folder_id
                    FROM folders
                  INNER JOIN permissions ON permissions.folder_id = folders.id
                )
                SELECT shares.user_id FROM shares
                INNER JOIN permissions ON permissions.folder_id = shares.shareable_id AND shareable_type = 'folder'
            )     
        "); //Is recursively shared
    }

    public function hasAcceptedLatestTerms() {
        if (!$this->has_accepted_terms_on) {
            return false;
        } else {
            return Carbon::parse($this->has_accepted_terms_on)->greaterThan(Carbon::parse(config('auth.terms_updated_at')));
        }
    }

}
