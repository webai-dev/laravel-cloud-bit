<?php

namespace App\Uploading;

use App\Models\Teams\Team;

/**
 * Interface Storable
 * Classes implementing this interface represent stored files that relate to a team
 * @package App\Uploading
 */
interface Storable {

    /**
     * Return the original filename of the item, including extension
     * @return string
     */
    public function getFilename();

    public function setFilename($filename);

    /**
     * Return the full path of the item, including generated filename
     * @return mixed
     */
    public function getPath();

    public function setPath($path);

    /**
     * Return the path that this item is supposed to be uploaded at
     * @return mixed
     */
    public function generatePath();
    /**
     * Return the item's size in bytes
     * @return mixed
     */
    public function getSize();

    public function setSize($path);

    /**
     * Return the item's extension, without a dot
     * @return mixed
     */
    public function getExtension();

    public function setExtension($extension);

    /**
     * Return the item's mime type
     * @return mixed
     */
    public function getMimeType();

    public function setMimeType($mime_type);

    /**
     * Return the team that has stored this item
     * @return Team
     */
    public function getTeam();

}