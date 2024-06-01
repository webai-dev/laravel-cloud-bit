<?php

namespace App\Util;

class FirebaseUtils
{
    public static function token($uid) {
        $config = static::getConfig();

        $now = time();
        $payload = [
            "iss" => $config->client_email,
            "sub" => $config->client_email,
            "aud" => "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit",
            "iat" => $now,
            "exp" => $now + 60 * 60,
            "uid" => $uid
        ];

        return JWT::encode($payload, $config->private_key, "RS256");
    }

    private static function getConfig () {
        $configFileContents = file_get_contents(resource_path('configs/firebase.json'));
        return json_decode($configFileContents);
    }
}