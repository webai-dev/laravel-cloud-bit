<?php

namespace App\Http\Controllers\Internal;

use App\Models\File;
use App\Models\FileVersion;
use App\Uploading\PreviewManager;
use App\Uploading\S3OperationsManager;
use App\Uploading\UploadManager;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FileVersionController extends Controller {
    protected $uploadManager;
    protected $operationsManager;
    protected $previewManager;

    public function __construct(
        UploadManager $uploadManager,
        PreviewManager $previewManager,
        S3OperationsManager $operationsManager
    ) {
        $this->uploadManager = $uploadManager;
        $this->previewManager = $previewManager;
        $this->operationsManager = $operationsManager;
    }

    public function index(File $file) {
        $this->authorize('view', $file);
        return $file->versions()
            ->orderBy('created_at', 'DESC')
            ->with('user')
            ->get();
    }

    public function store(File $file, Request $request) {
        $this->authorize('edit', $file);

        $this->validate($request, [
            'data' => 'required|file|max:' . config('filesystems.max_upload_size')
        ]);

        $upload = $request->file('data');

        $this->uploadManager->reupload($file, $upload);

        $file->trace('reupload', [
            'original_filename' => $upload->getClientOriginalName()
        ]);

        $file->update([
            's3_version_id' => $this->operationsManager->getS3VersionId($file),
            'mime_type'     => $upload->getMimeType(),
            'size'          => $upload->getSize(),
            'preview_url'   => $this->previewManager->generatePreviewUrl($file, $upload)
        ]);

        $version = $file->makeVersion();

        return $version;
    }

    public function update(File $file, FileVersion $version, Request $request) {
        $this->authorize('edit', $file);
        $this->validate($request, [
            'name' => 'string',
            'keep' => 'boolean'
        ]);

        $version->name = $request->input('name', $version->name);
        $version->keep = $request->input('keep', $version->keep);

        if ($file->keep && !$version->keep){
            abort(400,__('files.version_keep_conflict'));
        }

        $version->save();

        return $version;
    }

    public function show(File $file, FileVersion $version) {
        $this->authorize('view', $file);
        $expiration = config('filesystems.download_link_duration');

        $url = $this->operationsManager->getS3TemporaryUrl($file, $expiration, $version->s3_id);

        $response = [
            'title'     => $file->title,
            'extension' => $file->extension,
            'expires'   => $expiration,
            'url'       => $url
        ];

        $version->trace('show');

        return response()->json($response);
    }

    public function destroy(File $file, FileVersion $version) {
        $this->authorize('edit', $file);
        $this->operationsManager->deleteS3Version($file, $version->s3_id);
        if ($version->current) {
            /** @var FileVersion $previous */
            $previous = $file->versions()
                ->where('created_at', '<', $version->created_at)
                ->orderBy('created_at', 'DESC')
                ->first();
            if($previous == null){
                abort(400,__("files.version_delete_current"));
            }
            $previous->restore();
        }
        $version->delete();
        return $version;
    }
}
