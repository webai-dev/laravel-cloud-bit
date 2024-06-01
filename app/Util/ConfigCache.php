<?php
/**
 * Created by PhpStorm.
 * User: monospace
 * Date: 4/19/19
 * Time: 4:14 PM
 */

namespace App\Util;

class ConfigCache {
    private static $instances = [];

    public static function getInstance()
    {
        $cls = static::class;
        if (!isset(static::$instances[$cls])) {
            static::$instances[$cls] = new static;
        }

        return static::$instances[$cls];
    }


    public function cache(){
        $this->cache = config('filesystems.disks.s3');
    }

    public function restore(){
        return config(['filesystems.disks.s3' => $this->cache]);
    }
}