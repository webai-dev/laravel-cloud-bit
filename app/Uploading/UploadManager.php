<?php

namespace App\Uploading;



use App\Exceptions\StorageExceededException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadManager {

    /**
     * Uploads a given item
     * @param Storable $item
     * @param UploadedFile $upload
     */
    public function upload(Storable $item,UploadedFile $upload){
        $team = $item->getTeam();

        if ($team->exceedsStorageLimit($upload->getSize())) {
            throw new StorageExceededException();
        }

        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        $item->setFilename($upload->getClientOriginalName());
        $item->setMimeType($upload->getMimeType());
        $item->setSize($upload->getSize());
        $item->setExtension($upload->getClientOriginalExtension());

        $filename = $this->generateFileName($item);
        $path = $item->generatePath();
        $full_path = "$path/$filename";
        $item->setPath($full_path);

        Storage::putFileAs($path,$upload,$filename);
        Storage::setVisibility($full_path,'private');
    }

    /**
     * Replaces an item with the given upload
     * @param Storable $item
     * @param UploadedFile $upload
     */
    public function reupload(Storable $item,UploadedFile $upload){
        if ($upload->getClientOriginalExtension() != $item->getExtension()) {
            abort(400, __('files.extension_mismatch'));
        }

        $team = $item->getTeam();

        //Only check size difference
        $size = $upload->getSize() - $item->getSize();
        if ($team->exceedsStorageLimit($size)) {
            throw new StorageExceededException();
        }

        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        $item->setSize($upload->getSize());
        $path = pathinfo($item->getPath());

        Storage::putFileAs($path['dirname'],$upload,$path['basename']);
        Storage::setVisibility($item->getPath(),'private');
    }

    /**
     * Copies both the data and the attributes of an item to another
     * @param Storable $source
     * @param Storable $target
     */
    public function copy(Storable $source,Storable $target){
        $team = $source->getTeam();

        if ($team->exceedsStorageLimit($source->getSize())) {
            throw new StorageExceededException();
        }

        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        $target->setFilename($source->getFilename());
        $target->setMimeType($source->getMimeType());
        $target->setSize($source->getSize());
        $target->setExtension($source->getExtension());

        $filename = $this->generateFileName($target);
        $path = $target->generatePath();
        $full_path = "$path/$filename";

        $target->setPath($full_path);

        Storage::copy($source->getPath(), $full_path);
    }


    /**
     * Generates an upload-safe filename
     * @param Storable $item
     * @return string
     */
    protected function generateFileName(Storable $item){
        return time() . "_" . hash('sha256',$item->getFilename()) . '.' . $item->getExtension();
    }

    /**
     * Returns the string beginning after the last slash of the full path
     * @param Storable $item
     * @return bool|string
     */
    protected function getUploadedName(Storable $item){
        $path = $item->getPath();
        return substr($path,strrpos($path,"/") + 1);
    }

    /**
     * Returns the full path up to (but not including) the last slash
     * @param Storable $item
     * @return bool|string
     */
    protected function getUploadedPath(Storable $item){
        $path = $item->getPath();
        return substr($path,0,strrpos($path,"/") - 1);
    }
}