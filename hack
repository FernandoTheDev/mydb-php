<?php

$json = json_decode(
    file_get_contents(__DIR__ . "/db/fernando/users.json")
    ,
    true
);

for ($i = 0; $i < 100000; $i++) {
    $json["data"][] = [
        "user" => "fefe {$i}",
        "name" => "Fernando C {$i}"
    ];
}

file_put_contents(__DIR__ . "/db/fernando/users.json", json_encode($json));