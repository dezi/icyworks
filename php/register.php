<?php

include "../php/json.php";
include "../php/util.php";

header("Content-Type: text/plain");

function flush_and_close($result)
{
	ob_start();
	
	echo json_encdat($result) . "\n";
	$size = ob_get_length();
	
	header("Content-Length: $size");
	header("Connection: close");
	
	ob_end_flush();
	ob_flush();
	flush();
}

function register_track()
{
	umask(0002);
		
	$result = Array();
	
	if ((! isset($_GET[ "channel" ])) ||
		(! isset($_GET[ "title"   ])) ||
		(! isset($_GET[ "host"    ])))
	{
		$result[ "status" ] = "Bad request";
		return $result;
	}
	
	//
	// Process register.
	//
	
	$result[ "status" ] = "Ok";
	
	$channel = $_GET[ "channel" ];
	$title   = $_GET[ "title"   ];
	$host    = $_GET[ "host"    ];
	
	//
	// Check if known.
	//
	
	$cached = "../var/cached/$title.json";
	$cachedjson = null;
	
	if (file_exists($cached))
	{
		$result[ "known" ] = "cached";
		flush_and_close($result);
			
		$cachedjson = json_decdat(file_get_contents($cached));
	}
	else
	{
		if (title_exists($cached))
		{
			$result[ "known" ] = "archive";
			flush_and_close($result);
			
			$cachedjson = json_decdat(file_get_contents($cached));
		}
		else
		{
			make_final($title);
			
			$parts = explode(" - ",$title);
		
			if (count($parts) == 2)
			{
				$test = $parts[ 1 ] . " - " . $parts[ 0 ];
			
				if (file_exists("../var/cached/$test.json"))
				{
					$result[ "known" ] = "cached";
					flush_and_close($result);
				
					$title = $test;
					$cached = "../var/cached/$title.json";
					$cachedjson = json_decdat(file_get_contents($cached));
				}
			}
		
			if (count($parts) == 3)
			{
				$test = $parts[ 0 ] . " - " . $parts[ 2 ];
			
				if (file_exists("../var/cached/$test.json"))
				{
					$result[ "known" ] = "cached";
					flush_and_close($result);
				
					$title = $test;
					$cached = "../var/cached/$title.json";
					$cachedjson = json_decdat(file_get_contents($cached));
				}
				else
				{
					$test = $parts[ 0 ] . " - " . $parts[ 2 ];
			
					if (file_exists("../var/cached/$test.json"))
					{
						$result[ "known" ] = "cached";
						flush_and_close($result);
				
						$title = $test;
						$cached = "../var/cached/$title.json";
						$cachedjson = json_decdat(file_get_contents($cached));
					}
				}
			}
		
			if (count($parts) == 4)
			{
				$test = $parts[ 0 ] . " - " . $parts[ 3 ];
			
				if (file_exists("../var/cached/$test.json"))
				{
					$result[ "known" ] = "cached";
					flush_and_close($result);
				
					$title = $test;
					$cached = "../var/cached/$title.json";
					$cachedjson = json_decdat(file_get_contents($cached));
				}
			}
		}
	}
	
	if ($cachedjson == null)
	{		
		$title = $_GET[ "title" ];
		make_final($title,true);

		$noartist = "../var/noartist/$title.json";
		$notrack  = "../var/notrack/$title.json";
		$pending  = "../var/pending/$title.json";

		if (file_exists($noartist)) 
		{
			$result[ "known" ] = "noartist";
			flush_and_close($result);

			file_put_contents($noartist,".",FILE_APPEND);
		}
		else
		if (file_exists($notrack)) 
		{
			$result[ "known" ] = "notrack";
			flush_and_close($result);

			file_put_contents($notrack,".",FILE_APPEND);
		}
		else
		{
			$result[ "known" ] = "pending";
			flush_and_close($result);

			file_put_contents($pending,".",FILE_APPEND);
		}
	}
	
	//
	// Check last icy.
	//
	
	$lasticys = "../var/lasticys/$channel.txt";
	$lasticy = @file_get_contents($lasticys);
	
	if ($lasticy == $title) return;
	
	file_put_contents($lasticys,$title);
	
	//
	// Create item.
	//
	
	$item = Array();
	
	$item[ "channel" ] = $channel;
	$item[ "airtime" ] = gmdate("Ymd.His");
	$item[ "title"   ] = $title;
	$item[ "known"   ] = $result[ "known" ];

	//
	// Look for a cover.
	//
	
	if ($cachedjson)
	{
		if (isset($cachedjson[ "itunes" ]))
		{
			foreach ($cachedjson[ "itunes" ] as $collection)
			{
				if (isset($collection[ "cover" ]))
				{
					$item[ "cover" ] = $collection[ "cover" ];
					break;
				}
			}
		}
		else
		if (isset($cachedjson[ "discogs" ]))
		{
			foreach ($cachedjson[ "discogs" ] as $release)
			{
				if (isset($release[ "cover" ]))
				{
					$item[ "cover" ] = $release[ "cover" ];
					break;
				}
			}
		}
		else
		{
			foreach ($cachedjson as $release)
			{
				if (isset($release[ "cover" ]))
				{
					$item[ "cover" ] = $release[ "cover" ];
					break;
				}
			}
		}
	}
	
	//
	//	Put into playlist.
	//
	
	$playlist = "../var/playlists/" . gmdate("Y.m.d");
	if (! is_dir($playlist)) mkdir($playlist,0775);
	$playlist = $playlist . "/$channel.json";
	
	file_put_contents($playlist,json_encdat($item) . ",\n",FILE_APPEND);	
	
	//
	//	Put into now playing.
	//
	
	$now = time();
	
	$nowplaying = "../var/nowplaying/" . gmdate("Y.m.d",$now);
	if (! is_dir($nowplaying)) mkdir($nowplaying,0775);
	$nowplaying = $nowplaying . "/" . gmdate("Y.m.d.Hi",$now) . ".json";
	
	file_put_contents($nowplaying,json_encdat($item) . ",\n",FILE_APPEND+LOCK_EX);	
}

register_track();

?>