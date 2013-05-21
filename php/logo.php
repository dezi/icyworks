<?php

header("Content-Type: image/png");

$logo = explode("/",$_SERVER[ "REQUEST_URI" ]);
$logo = array_pop($logo);
$path = "../etc/logos/$logo";

readfile($path);

?>
