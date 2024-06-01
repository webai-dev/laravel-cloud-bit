<?php

namespace Tests;

use App\Models\Teams\Team;

trait AttachesServerVars {

    protected function asTeam(Team $team){
        $subdomain = $team->subdomain;

        $this->serverVariables = array_merge($this->serverVariables,[
            'HTTP_REFERER' => "http://$subdomain.test.io"
        ]);

        return $this;
    }
}