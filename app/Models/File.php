<?php

namespace App\Models;

use App\Sharing\Shareable;
use App\Sharing\Visitors\ShareableVisitor;
use App\Uploading\Storable;
use App\Util\ConfigCache;
use App\Util\FileUtils;
use App\Util\URL;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Class File
 * @package App\Models
 * @property string path
 * @property string s3_version_id
 * @property string mime_type
 * @property string extension
 * @property int size The file size in bytes
 * @property string public_token
 * @property string preview_url
 * @property Collection versions
 * @property boolean keep
 */
class File extends Shareable implements Storable
{
    protected $dates = ['created_at','updated_at','deleted_at'];
    protected $hidden = ['path','s3_version_id'];
    protected $fillable = ['title','path','s3_version_id','mime_type','size','extension','folder_id','team_id','public_token','preview_url'];

    protected $observables = ['uploaded','copied','opened', 'teammate_removed'];
    public static $exclude_default_events = ['created','updating'];

    public function getType(){
        return 'file';
    }

    public function generatePublicToken($user){
        return hash('sha256',$user->id.'_'.$this->id."_".microtime(true));
    }

    public function getPublicUrl(){
        $url = URL::from(config('app.web_url'));

        $url->path = 'files/'.$this->public_token;

        return $url->__toString();
    }

    public function versions(){
        return $this->hasMany(FileVersion::class);
    }

    public function makeVersion(){
        $count = $this->versions()->count();
        $this->versions()->update(['current' => false]);
        return $this->versions()->create([
            'user_id' => Auth::id(),
            'filename'=> $this->title,
            's3_id'   => $this->s3_version_id,
            'name'    => 'Version '.($count + 1),
            'size'    => $this->size,
            'current' => true,
            'keep'    => $this->keep
        ]);
    }

    public function toDocument(){
        $document = parent::toDocument();

        $document->type_meta = FileUtils::getMimeGroup($this->mime_type);

        // replace size with env var
        if(in_array($document->type_meta , array('text','spreadsheet', 'pdf'))
            && $this->getSize() <= config('elasticsearch.max_filesize')){
            try {
                if($this->team->uses_external_storage){
                    $cc = ConfigCache::getInstance();
                    $cc->cache();

                    $this->team->useExternalStorage();
                    $document->data = base64_encode(Storage::get($this->path));

                    $cc->restore();
                }
                else $document->data = base64_encode(Storage::get($this->path));
            } catch (\Exception $e){
                \Log::error('Caught exception: '.$e->getMessage()."\n");
            }
        }

        return $document;
    }

    public function getFilename() {
        return $this->title;
    }

    public function setFilename($filename) {
        $this->title = $filename;
    }

    public function getPath() {
        return $this->path;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function getSize() {
        return $this->size;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function getExtension() {
        return $this->extension;
    }

    public function setExtension($extension) {
        $this->extension = $extension;
    }

    public function getMimeType() {
        return $this->mime_type;
    }

    public function setMimeType($mime_type) {
        $this->mime_type = $mime_type;
    }

    public function getTeam() {
        return $this->team;
    }

    public function generatePath(){
        return "teams/$this->team_id/files/user_$this->user_id";
    }

    public function generateFullPath() {
        return "teams/$this->team_id/files/user_" . Auth::id() . '/' . time() . "_" . hash('sha256',$this->title . '.' . $this->extension) . ($this->extension ? '.' . $this->extension : '');
    }

    public function accept(ShareableVisitor $visitor) {
        $visitor->visitFile($this);
    }
}
