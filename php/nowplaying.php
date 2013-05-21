<?php

include "../php/json.php";

function get_nowplaying()
{
	header("Content-Type: text/plain");

	$now = time() - 60;
	
	$list = Array();
	
	for ($chunk = 0; $chunk < 2; $chunk++)
	{
		$nowplaying = "../var/nowplaying/" . gmdate("Y.m.d",$now);
		$nowplaying = $nowplaying . "/" . gmdate("Y.m.d.Hi",$now) . ".json";
		
		$cont = @file_get_contents($nowplaying);
		if ($cont === false) continue;
		
		$cont = substr(trim($cont),0,-1);
		
		$data = json_decdat("[" .  $cont . "]");
		if ($data === null) continue;

		foreach ($data as $item)
		{
			if (! isset($item[ "logo"  ])) continue;
			if (! isset($item[ "cover" ])) continue;
			
			array_unshift($list,$item);
		}
		
		$now += 60;
	}
	
	echo "ICYNowplayingCallback(\n";
	echo json_encdat($list);
	echo "\n);\n";
}

get_nowplaying();

?>