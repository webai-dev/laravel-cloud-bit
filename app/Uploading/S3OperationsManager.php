<?php

namespace App\Uploading;

use App\Util\Enums\Environment;
use App\Util\URL;
use Illuminate\Support\Facades\Storage;

class S3OperationsManager {
    public function getS3TemporaryUrl(Storable $item, $expires = "+20 seconds", $version = null) {
        if (config('app.env') == Environment::TESTING) {
            return "https://testing.s3.url.com";
        }

        $team = $item->getTeam();
        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        /** @var  \Aws\S3\S3MultiRegionClient $client */
        $client = Storage::getDriver()->getAdapter()->getClient();

        $filename = $item->getFilename();

        //S3 cannot add non-ascii characters in response headers
        if (!mb_check_encoding($filename, 'ASCII')) {
            $filename = uniqid() . "." . $item->getExtension();
        }

        $params = [
            'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
            'Bucket'                     => config('filesystems.disks.s3.bucket'),
            'Key'                        => $item->getPath(),
        ];

        if ($version != null) {
            $params['VersionId'] = $version;
        }

        $command = $client->getCommand('GetObject', $params);

        $request = $client->createPresignedRequest($command, $expires);

        $url = (string)$request->getUri();

        if (config('app.env') == Environment::PRODUCTION) {
            $cdn_url = $team->uses_external_storage ? $team->cdn_url : config('filesystems.cdn_url');
            $url = URL::toCDN($url,$cdn_url);
        }
        return $url;
    }

    public function getPresignedUrlForUpload(Storable $item, $expires="+10 minutes") {
        if (config('app.env') == Environment::TESTING) {
            return "https://testing.s3.url.com";
        }

        $team = $item->getTeam();
        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        $client = Storage::getDriver()->getAdapter()->getClient();

        $full_path = $item->generateFullPath();

        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => $full_path,
        ]);

        $request = $client->createPresignedRequest($command, $expires);

        return [
            'presignedUrl' => (string) $request->getUri(),
            'fullPath' => $full_path,
        ];
    }

    public function getS3VersionId(Storable $item) {
        if (config('app.env') == Environment::TESTING) {
            return time() . "_test";
        }

        $team = $item->getTeam();
        if ($team->uses_external_storage) {
            $team->useExternalStorage();
        }

        /** @var  \Aws\S3\S3MultiRegionClient $client */
        $client = Storage::getDriver()->getAdapter()->getClient();

        $s3_object = $client->getObject([
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => $item->getPath(),
        ]);

        return $s3_object['VersionId'];
    }

    public function deleteS3Version(Storable $item, $version) {
        if (config('app.env') == Environment::TESTING) {
            return;
        }

        /** @var  \Aws\S3\S3MultiRegionClient $client */
        $client = Storage::getDriver()->getAdapter()->getClient();

        $client->deleteObject([
            'Bucket'    => config('filesystems.disks.s3.bucket'),
            'Key'       => $item->getPath(),
            'VersionId' => $version
        ]);
    }
}