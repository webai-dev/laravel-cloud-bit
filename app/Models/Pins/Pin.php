<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\FavouriteScope;
use App\Tracing\Traits\Traceable;

class Pin extends Model
{
    use Traceable;
    protected static function boot(){
        parent::boot();
        static::addGlobalScope(new FavouriteScope());
    }
    
    protected $visible = ['id','content_type','created_at','updated_at','content','user','favourite'];
    protected $fillable = ['team_id','type_id','user_id','content_id','content_type'];
    
    const MORPH_MAP = [
        'photo'        => Photo::class,   
        'text'         => TextNote::class, 
        'video'        => Video::class,
        'map'          => Map::class,
        'reminder'     => Reminder::class,
        'announcement' => Announcement::class,
    ];
    
    public function user(){
        return $this->belongsTo('App\Models\User')->select(['id','name']);
    }
    
    public function team(){
        return $this->belongsTo('App\Models\Teams\Team');
    }
    
    public function type(){
        return $this->belongsTo(Type::class);
    }
    
    public function content(){
        return $this->morphTo();
    }
}
