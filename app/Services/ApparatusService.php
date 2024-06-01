<?php

namespace App\Services;

use WebThatMatters\Apparatus\Entities\User;

class ApparatusService extends \WebThatMatters\Apparatus\ApparatusService {

    public function createUser(User $userData) {
        $this->client->setAuthorization($this->createToken());

        $payload = [
            'name'               => $userData->name,
            'email'              => $userData->email,
            'phone'              => $userData->mobile,
            'ignore_terms_email' => true,
        ];

        $response = $this->client->post('/integration/register', $payload);

        $userData->id = $response->user->id;
        if (!property_exists($response->user, 'has_accepted_terms')) {
            $response->user->has_accepted_terms = false;
        }
        $userData->has_accepted_terms = $response->user->has_accepted_terms;

        return $userData;
    }

    public function acceptTerms($user_id) {

        $token = $this->createToken();
        $this->client->setAuthorization($token);
        $response = $this->client->post("/integration/accept/terms", ['user_id' => $user_id]);
        return response()->json($response);
    }

    public function getTerms() {
        $response = $this->client->get("/terms");
        return $response;
    }

}