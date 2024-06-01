<?php

namespace App\Models\Bits;

use App\Sharing\Shareable;
use App\Sharing\Visitors\ShareableVisitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;

/**
 * Class Bit
 * @package App\Models\Bits
 * @property string metadata
 * @property int type_id
 * @property Type type
 * @property string color
 */
class Bit extends Shareable
{
    protected $fillable = ['title','user_id','type_id','folder_id','team_id','sandbox'];
    protected $hidden = ['metadata'];
    protected $observables = ['opened', 'teammate_removed'];

    public function tags(){
        return $this->morphMany('App\Models\Tag','taggable');
    }

    public function type(){
        return $this->belongsTo(Type::class,'type_id');
    }

    public function share(){
        return $this->morphOne('App\Models\Share','shareable');
    }

    public function getType(){
        return 'bit';
    }

    public function files(){
        return $this->hasMany(BitFile::class);
    }

    public static function getIndexQuery($team_id,$sort_by,$sort_order = 'ASC',$folder_id = null){
        $query = parent::getIndexQuery($team_id,$sort_by,$sort_order,$folder_id);
        return $query->with('type')
            ->withColor(Auth::id())
            ->addSelect('bits.user_id');
    }

    public function scopeWithColor(Builder $builder,$user_id){
        $builder->leftJoin('bit_colors',function(JoinClause $clause) use($user_id){
            $clause->on('bits.id','=','bit_colors.bit_id')
                ->where('bit_colors.user_id',$user_id);
        })->addSelect('bit_colors.color');
    }

    public function toDocument(){
        $document = parent::toDocument();

        $document->type_meta = "type_".$this->type_id;
        $document->data = base64_encode($this->metadata);
        $document->attachment = ['content' => $this->metadata];

        return $document;
    }

    public function accept(ShareableVisitor $visitor) {
        $visitor->visitBit($this);
    }
}
