<?php

namespace App\Http\Controllers\Bits;

use App\Models\Bits\BitFile;
use App\Models\Bits\Type;
use App\Uploading\S3OperationsManager;
use App\Uploading\UploadManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends IntegrationController {

    protected $uploadManager;

    protected $operationsManager;

    const URL_DURATION = '+5 minutes';

    public function __construct(
        Request $request,
        UploadManager $uploadManager,
        S3OperationsManager $operationsManager
    ) {
        parent::__construct($request);
        $this->uploadManager = $uploadManager;
        $this->operationsManager = $operationsManager;
    }

    public function upload(Type $type, Request $request) {
        $this->validate($request, [
            'data' => 'required|file|max:' . config('filesystems.max_upload_size')
        ]);

        $bit = $this->findBit($type);
        $upload = $request->file('data');

        $file = new BitFile();
        $file->bit_id = $bit->id;
        $this->uploadManager->upload($file, $upload);
        $file->save();

        $file->url = $this->operationsManager->getS3TemporaryUrl($file, self::URL_DURATION);
        $file->url_expires = strtotime(self::URL_DURATION);

        return $file;
    }

    public function show(Type $type, $id) {
        $bit = $this->findBit($type);

        /** @var BitFile $file */
        $file = $bit->files()->findOrFail($id);
        $file->url = $this->operationsManager->getS3TemporaryUrl($file, self::URL_DURATION);
        $file->url_expires = strtotime(self::URL_DURATION);

        return $file;
    }

    public function delete(Type $type, $id) {
        $bit = $this->findBit($type);

        /** @var BitFile $file */
        $file = $bit->files()->findOrFail($id);
        Storage::delete($file->path);
        $file->delete();

        return response()->json([
            'message' => 'File Deleted'
        ]);
    }
}
