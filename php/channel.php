<?php

include "../php/json.php";

header("Content-Type: text/plain");

$channel = explode("/",$_SERVER[ "REQUEST_URI" ]);
$channel = array_pop($channel);
$channel = explode("?",$channel);
$channel = array_shift($channel);
$path = "../etc/channels/$channel/$channel.json";

$cont = file_get_contents($path);
$json = json_decdat($cont);
$data = Array();

$data[ "channel"   ] = $channel;
$data[ "streamurl" ] = $json[ "broadcast" ][ "streamUrls" ][ 0 ][ "streamUrl" ];

echo "ICYChannelCallback(\n";
echo json_encdat($data);
echo "\n);\n";

?>
