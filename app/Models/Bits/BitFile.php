<?php

namespace App\Models\Bits;


use App\Models\Teams\Team;
use App\Uploading\Storable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BitFile
 * @package App\Models\Bits
 * @property integer id
 * @property string filename
 * @property string path
 * @property string mime_type
 * @property string extension
 * @property int size
 * @property int bit_id
 * @property Bit bit
 * @property-write string url
 * @property-write string url_expires
 */
class BitFile extends Model implements Storable {

    protected $table = 'bit_files';
    protected $guarded = [];
    protected $hidden = ['path'];

    public function bit(){
        return $this->belongsTo(Bit::class);
    }

    public function getFilename() {
        return $this->filename;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
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
        return $this->bit->team;
    }

    public function generatePath() {
        $team = $this->getTeam();
        $type_id = $this->bit->type_id;
        return "teams/$team->id/files/bits/$type_id/$this->bit_id";
    }
}