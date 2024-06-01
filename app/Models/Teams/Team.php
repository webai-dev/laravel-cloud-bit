<?php

namespace App\Models\Teams;

use App\Models\Bits\BitFile;
use App\Models\Bits\Type;
use App\Models\File;
use App\Models\User;
use App\Tracing\Traits\Traceable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Class Team
 * @package App\Models\Teams
 * @property int id
 * @property int user_id
 * @property string customer_code
 * @property string subdomain
 * @property string name
 * @property string photo
 * @property boolean uses_external_storage
 * @property boolean suspended
 * @property int storage_limit
 * @property string aws_key
 * @property string aws_secret
 * @property string aws_bucket
 * @property string aws_region
 * @property string cdn_url
 * @property Collection users
 * @property Collection invitations
 * @property Collection shareables
 * @property Collection subscriptions
 * @property User owner
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property Carbon customer_created_at
 * @property Carbon customer_updated_at
 */
class Team extends Model {
    use Traceable, SoftDeletes;

    protected $table = 'teams';
    protected $fillable = ['subdomain', 'name', 'photo'];
    protected $visible = ['id', 'subdomain', 'name', 'photo', 'users', 'user_id', 'locked', 'storage_percentage'];
    protected $dates = [
        'created_at',
        'updated_at',
        'customer_created_at',
        'customer_updated_at',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'team_users');
    }

    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invitations() {
        return $this->hasMany(Invitation::class);
    }

    public function integrations() {
        return $this->hasMany(Integration::class);
    }

    public function shareables() {
        return $this->hasMany(TeamShareable::class);
    }

    public function subscriptions() {
        return $this->hasMany(Subscription::class);
    }

    public function bitTypes() {
        return $this->belongsToMany(Type::class, 'bit_type_teams');
    }

    public function getActiveSubscription($type) {
        return $this->subscriptions()
            ->where('active', true)
            ->where('type', $type)
            ->first();
    }

    public function hasUnpaidSubscriptions(){
        return $this->subscriptions()
                    ->where('active', true)
                    ->where('status', 'unpaid')
                    ->count() > 0;
    }

    /**
     * Checks whether this team will exceed its storage limit by adding a given size
     * @param int $size The size to check in bytes
     * @return bool
     */
    public function exceedsStorageLimit($size) {
        if ($this->uses_external_storage || $this->storage_limit == null) {
            return false;
        }

        $total = $this->getTotalUsedStorage();

        return $total + $size > $this->storage_limit;
    }

    public function getInternalUsedStorage() {
        return (int)File::query()
            ->where('team_id', $this->id)
            ->sum('size');
    }

    public function getBitUsedStorage() {
        return (int)BitFile::query()
            ->whereHas('bit', function ($query) {
                $query->where('team_id', $this->id);
            })
            ->sum('size');
    }

    public function getTotalUsedStorage() {
        return $this->getInternalUsedStorage() + $this->getBitUsedStorage();
    }

    public function useExternalStorage() {
        config([
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key'    => decrypt($this->aws_key),
                'secret' => decrypt($this->aws_secret),
                'bucket' => $this->aws_bucket,
                'region' => $this->aws_region
            ]
        ]);
    }
}
