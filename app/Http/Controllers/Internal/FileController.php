<?php

namespace App\Http\Controllers\Internal;

use App\Models\File;
use App\Models\Folder;
use App\Uploading\PreviewManager;
use App\Uploading\S3OperationsManager;
use App\Uploading\UploadManager;
use App\Util\URL;
use App\Util\Enums\Environment;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use App\Mail\FilePublished;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Traits\InteractsWithShareables;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class FileController extends Controller {

    use InteractsWithShareables;

    protected $uploadManager;
    protected $previewManager;
    protected $operationsManager;

    public function __construct(UploadManager $uploadManager,
        PreviewManager $previewManager,
        S3OperationsManager $operationsManager) {
        $this->uploadManager = $uploadManager;
        $this->previewManager = $previewManager;
        $this->operationsManager = $operationsManager;
    }

    public function store(Request $request) {

        if($request->input('folder_id') == "null"){
            abort(400, __("files.no_files_on_root"));
        }

        $this->validate($request, [
            'folder_id' => 'required|integer|exists:folders,id',
            'data'      => 'required|file|max:' . config('filesystems.max_upload_size')
        ]);

        $team_id = $request->getTeam()->id;

        $this->authorize('create', File::class);
        $has_shared_parent = false;

        $file = new File([
            'folder_id' => $request->input('folder_id'),
            'team_id'   => $team_id,
        ]);

        if ($request->has('folder_id')) {
            $parent = Folder::where('id', $request->input('folder_id'))
                ->where('team_id', $team_id)->firstOrFail();
            $this->authorize('edit', $parent);

            $file->owner_id = $parent->owner_id;
            $has_shared_parent = $parent->is_shared || $parent->has_shared_parent;
        }else{
            $file->owner_id = Auth::id();
        }

        $upload = $request->file('data');

        $file->has_shared_parent = $has_shared_parent;
        $file->user_id      = Auth::id();
        $this->uploadManager->upload($file,$upload);
        $file->preview_url  = $this->previewManager->generatePreviewUrl($file,$upload);
        $file->s3_version_id= $this->operationsManager->getS3VersionId($file);
        $file->keep = $request->input('keep',false);
        $file->save();

        $file->shares_count = 0;

        $file->trace('upload',[
            'original_filename' => $upload->getClientOriginalName()
        ]);
        $file->triggerEvent('uploaded');

        $file->makeVersion();

        return $file;
    }

    public function getPath(Request $request) {
        if($request->input('folder_id') == "null"){
            abort(400, __("files.no_files_on_root"));
        }

        $this->validate($request, [
            'folder_id' => 'required|integer|exists:folders,id',
            'filename' => 'required|string',
        ]);

        $team_id = $request->getTeam()->id;
        $this->authorize('create', File::class);

        $file = new File([
            'folder_id' => $request->input('folder_id'),
            'team_id'   => $team_id,
            'title' => $request->input('filename'),
            'extension' => $request->input('extension'),
        ]);

        if ($request->has('folder_id')) {
            $parent = Folder::where('id', $request->input('folder_id'))
                ->where('team_id', $team_id)->firstOrFail();
            $this->authorize('edit', $parent);
        }

        return $this->operationsManager->getPresignedUrlForUpload($file);
    }

    public function createFile(Request $request) {
        if($request->input('folder_id') == "null"){
            abort(400, __("files.no_files_on_root"));
        }

        $this->validate($request, [
            'folder_id' => 'required|integer|exists:folders,id',
            'filename' => 'required|string',
            'path' => 'required|string',
        ]);

        $filename = $request->input('filename');
        $extension = $request->input('extension');
        $path = $request->input('path');

        $team = $request->getTeam();
        $size = Storage::size($path);

        if ($team->exceedsStorageLimit($size)) {
            Storage::delete($path);
            throw new StorageExceededException();
        }

        $team_id = $team->id;
        $this->authorize('create', File::class);
        $has_shared_parent = false;

        $file = new File([
            'folder_id' => $request->input('folder_id'),
            'team_id'   => $team_id,
        ]);

        if ($request->has('folder_id')) {
            $parent = Folder::where('id', $request->input('folder_id'))
                ->where('team_id', $team_id)->firstOrFail();
            $this->authorize('edit', $parent);

            $file->owner_id = $parent->owner_id;
            $has_shared_parent = $parent->is_shared || $parent->has_shared_parent;
        }else{
            $file->owner_id = Auth::id();
        }

        $file->title = $filename;

        if ($extension) {
            $file->title = $file->title . '.' . $extension;
        }

        $file->path = $path;
        $file->extension = $extension ? $extension : '';
        $file->size = Storage::size($path);
        $file->mime_type = Storage::getMimetype($path);
        $file->user_id = Auth::id();
        $file->keep = $request->input('keep',false);
        $file->has_shared_parent = $has_shared_parent;
        $file->s3_version_id = $this->operationsManager->getS3VersionId($file);
        
        if ($file->size < config('app.image_preview_limit_size') && strpos($file->mime_type, 'image') === 0) {
            $file->preview_url  = $this->previewManager->generatePreviewUrl($file, Storage::get($path));
        }

        $file->save();

        return $file;
    }

    public function show($id, Request $request) {
        $user = Auth::user();

        /** @var File $file */
        $file = File::where('id', $id)
            ->withLocked($user->id)
            ->withCount('shares')
            ->with('shares')
            ->withRenamedTitle($user->id)
            ->firstOrFail();

        $this->authorize('view', $file);

        if (!$request->input('view') == "simple") {
            $expiration = config('filesystems.download_link_duration');

            $response = [
                'title'     => $file->title,
                'extension' => $file->extension,
                'expires'   => $expiration,
                'url'       => $this->operationsManager->getS3TemporaryUrl($file,$expiration)
            ];

            $file->triggerEvent('opened');
        } else {
            if ($file->user_id != $user->id && $file->is_shared && !$file->isParentRecursivelySharedWith($user->id)){
                $file->folder_id = null;
            }
            $response = $file;
        }

        return response()->json($response);
    }

    public function showPublic($token) {
        $file = File::where('public_token', $token)->firstOrFail();
        $url = $this->operationsManager->getS3TemporaryUrl($file,config('filesystems.download_link_duration'));
        $title = $file->title;
        $extension = $file->extension;

        $file->triggerEvent('opened');

        return response()->json(compact('url', 'expires', 'title', 'extension'));
    }

    public function update(File $file, Request $request) {
        $this->authorize('view', $file);

        $this->validate($request, [
            'title' => 'string',
            'keep'  => 'boolean'
        ]);

        if ($request->has('title')){
            $file->trace('rename');
            $file->title = $request->input('title');
        }
        $file->keep = $request->input('keep',$file->keep);
        $file->save();

        $file->versions()->update(['keep' => $file->keep]);

        return $file;
    }

    public function copy(File $file, Request $request) {
        $this->validate($request, [
            'folder_id' => 'exists:folders,id',
            'team_id'   => 'required|exists:teams,id'
        ]);

        $this->authorize('view', $file);

        $has_shared_parent = false;

        if ($request->has('folder_id')) {
            $parent = Folder::where('id', $request->input('folder_id'))
                ->where('team_id', $request->input('team_id'))->firstOrFail();
            $this->authorize('edit', $parent);
            $has_shared_parent = $parent->is_shared || $parent->has_shared_parent;
        }

        $copy = new File([
            'folder_id' => $request->input('folder_id'),
            'team_id'   => $request->input('team_id')
        ]);

        $copy->has_shared_parent = $has_shared_parent;
        $copy->user_id      = Auth::id();
        $this->uploadManager->copy($file,$copy);
        $copy->s3_version_id= $this->operationsManager->getS3VersionId($copy);
        $copy->preview_url = $file->preview_url;
        $copy->keep = $file->keep;
        $copy->owner_id = $file->owner_id;
        $copy->save();

        $copy->makeVersion();

        $copy->triggerEvent('copied');

        return $copy;
    }

    public function link(File $file) {
        $user = Auth::user();
        $this->authorize('share', $file);

        if ($file->public_token == null) {
            $file->public_token = $file->generatePublicToken($user);
            $file->save();
        }

        return response()->json([
            'url' => $file->getPublicUrl()
        ]);
    }

    public function publish(File $file, Request $request) {
        $this->validate($request, [
            'addresses'   => 'required|array|min:1',
            'addresses.*' => 'email'
        ]);

        $user = Auth::user();
        $this->authorize('share', $file);

        if ($file->public_token == null) {
            $file->public_token = $file->generatePublicToken($user);
            $file->save();
        }

        $addresses = array_map(function ($address) {
            $recipient = new \stdClass();
            $recipient->email = $address;
            return $recipient;
        }, $request->input('addresses'));

        Mail::to($addresses)->send(new FilePublished($file, $user));

        return $file;
    }

    public function unpublish(File $file) {
        $this->authorize('share', $file);

        $file->public_token = null;
        $file->save();

        return $file;
    }

    public function destroy(File $file) {
        $this->authorize('delete', $file);

        Storage::delete($file->path);

        $file->forceDelete();

        return $file;
    }
}
