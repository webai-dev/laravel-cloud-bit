<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ExecutesSQL {

    public function getQuery($name,array $bindings = []){
        $filename = substr($name,-4) == ".sql" ? $name : $name . ".sql";
        $sql = file_get_contents(resource_path("sql/$filename"));
        return DB::select(DB::raw($sql),$bindings);
    }
}