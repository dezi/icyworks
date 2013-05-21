<?php

header("Content-Type: image/png");

$cover = explode("/",$_SERVER[ "REQUEST_URI" ]);
$cover = array_pop($cover);
$parts = explode("-",$cover);
$area  = ($cover[ 0 ] == "R") ? "releases" : "itunes";
$sdir  = ($cover[ 0 ] == "R") ? substr($parts[ 2 ],-3) : substr($parts[ 2 ],-7,3);
$path  = "../lib/images/$area/$sdir/$cover";

readfile($path);

?>
