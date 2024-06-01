<?php

namespace App\Util;

class URL {

    public $scheme = 'http';
    public $user = '';
    public $pass = '';
    public $host = '';
    public $port = '';
    public $path = '';
    public $query = '';
    public $fragment = '';

    public function __construct(array $parts) {
        foreach ($parts as $part => $value) {
            $this->{$part} = $value;
        }
    }

    public static function ensureSlash($url) {
        if (substr($url, -1) !== "/") {
            return $url . "/";
        }
        return $url;
    }

    public static function from($url) {
        $parts = parse_url($url);
        return new Url($parts);
    }

    public static function toCDN($url, $cdn_host = null) {
        $parsed = URL::from($url);

        if ($cdn_host != null) {
            $parsed->host = $cdn_host;
            $parsed->path = str_replace("/ybit", "", $parsed->path);
        }

        return $parsed->__toString();
    }

    public function __toString() {
        $url = $this->scheme . "://";

        if ($this->user !== '' && $this->pass !== '') {
            $url .= "$this->user@$this->pass";
        }

        //Strip trailing slash
        if (substr($this->host, -1) == "/") {
            $url .= substr($this->host, 0, -1);
        } else {
            $url .= $this->host;
        }

        if ($this->port !== '') {
            $url .= ":$this->port";
        }

        //Add leading slash (if required)
        if (substr($this->path, 0, 1) == "/") {
            $url .= $this->path;
        } else {
            $url .= "/" . $this->path;
        }

        if ($this->query !== '') {
            $url .= "?$this->query";
        }

        if ($this->fragment !== '') {
            $url .= "#$this->fragment";
        }

        return $url;
    }
}