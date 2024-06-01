<?php

namespace App\Models;

use App\Tracing\Traits\Traceable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FileVersion
 * @package App\Models
 * @property string name
 * @property string filename
 * @property string s3_id
 * @property boolean keep
 * @property boolean current
 * @property int user_id
 * @property int file_id
 * @property int size
 * @property File file
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class FileVersion extends Model {

    use Traceable;

    protected $hidden = ['s3_id'];
    protected $fillable = ['name', 'keep','user_id','filename','s3_id','created_at','size','current'];
    protected $table = 'file_versions';

    public function file() {
        return $this->belongsTo(File::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function restore(){
        $this->current = true;
        $this->save();

        $file = $this->file;
        $file->s3_version_id = $this->s3_id;
        $file->size = $this->size;
        $file->save();
    }
}
