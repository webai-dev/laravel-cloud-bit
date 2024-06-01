<?php

namespace App\Uploading;

use App\Util\Enums\Environment;
use App\Util\URL;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Facades\Image;

class PreviewManager {

    const PREVIEW_WIDTH = 50;
    const PREVIEW_HEIGHT = 50;

    const QUALITY = 75;

    public function generatePreviewUrl(Storable $item, $upload, $path_prefix = "images/previews") {
        if (config('app.env') == Environment::TESTING){
            return null;
        }

        try {
            $image = Image::make($upload);
        } catch (NotReadableException $e) {
            //File doesn't support preview
            return null;
        }

        $image = $image->fit(self::PREVIEW_WIDTH, self::PREVIEW_HEIGHT);
        $image = (string)$image->encode('jpg', self::QUALITY);

        $path = $path_prefix . "/" . $item->getPath();
        Storage::put($path, $image);

        $url = Storage::cloud()->url($path);

        if (config('app.env') == Environment::PRODUCTION) {
            $url = URL::toCDN($url);
        }

        return $url;
    }
}