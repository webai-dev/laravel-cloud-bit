<?php

namespace App\Util;

use App\Models\Teams\Integration;
use DateTime;
use Firebase\JWT\ExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWT extends \Firebase\JWT\JWT {

    public static function getIntegrationPayload($jwt) {
        $payload = self::decodeUnverified($jwt);

        /** @var Integration $integration */
        $integration = Integration::query()
                                  ->where(["key" => $payload->iss])
                                  ->first();

        if ($integration == null) return [null, $payload];

        //Properly verify token
        try{
            JWT::decode($jwt, $integration->secret, ['HS256']);
        } catch (\Exception $e){
            throw new JWTException($e->getMessage());
        }


        return [$integration, $payload];
    }

    public static function decodeUnverified($jwt) {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new JWTException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;

        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
            throw new JWTException('Invalid header encoding');
        }
        if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
            throw new JWTException('Invalid claims encoding');
        }

        if (empty($header->alg)) {
            throw new JWTException('Empty algorithm');
        }
        if (empty(parent::$supported_algs[$header->alg])) {
            throw new JWTException('Algorithm not supported');
        }

        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && $payload->nbf > (time() + self::$leeway)) {
            throw new JWTException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
            );
        }

        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) && $payload->iat > (time() + self::$leeway)) {
            throw new JWTException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
            );
        }

        return $payload;
    }

}