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
	
	$cached = "$title.json";
	$cachedjson = null;

	if (title_exists($cached))
	{
		$result[ "title" ] = $title;
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
			if (file_exists("../var/archive/" . $parts[ 1 ]))
			{
				error_log(">1<" . $title);

				$test  = $parts[ 1 ] . " - " . $parts[ 0 ];
				$title = $test;
				
				if (file_exists($cached = "../var/archive/" . $parts[ 1 ] . "/$test.json"))
				{
					error_log(">2<" . $title);
	
					$result[ "title" ] = $title = $test;
					$result[ "known" ] = "archive";
					flush_and_close($result);
		
					$cachedjson = json_decdat(file_get_contents($cached));
				}
			}
		}

		if (count($parts) == 3)
		{
			if (file_exists("../var/archive/" . $parts[ 0 ]))
			{
				$test = $parts[ 0 ] . " - " . $parts[ 1 ];
				
				if (file_exists($cached = "../var/archive/" . $parts[ 0 ] . "/$test.json"))
				{					
					error_log(">3<" . $title);

					$result[ "title" ] = $title = $test;
					$result[ "known" ] = "archive";
					flush_and_close($result);
		
					$cachedjson = json_decdat(file_get_contents($cached));
				}
				else
				{
					$test = $parts[ 0 ] . " - " . $parts[ 2 ];
	
					if (file_exists($cached = "../var/archive/" . $parts[ 0 ] . "/$test.json"))
					{					
						error_log(">3<" . $title);

						$result[ "title" ] = $title = $test;
						$result[ "known" ] = "archive";
						flush_and_close($result);
		
						$cachedjson = json_decdat(file_get_contents($cached));
					}
				}
			}
			else
			if (file_exists("../var/archive/" . $parts[ 1 ]))
			{
				$test = $parts[ 1 ] . " - " . $parts[ 2 ];
				
				if (file_exists($cached = "../var/archive/" . $parts[ 1 ] . "/$test.json"))
				{					
					error_log(">3<" . $title);

					$result[ "title" ] = $title = $test;
					$result[ "known" ] = "archive";
					flush_and_close($result);
		
					$cachedjson = json_decdat(file_get_contents($cached));
				}
			}
		}

		if (count($parts) == 4)
		{
			$test = $parts[ 0 ] . " - " . $parts[ 3 ];
	
			if (file_exists($cached = "../var/archive/" . $parts[ 0 ] . "/$test.json"))
			{					
				error_log(">4<" . $title);

				$result[ "title" ] = $title = $test;
				$result[ "known" ] = "archive";
				flush_and_close($result);

				$cachedjson = json_decdat(file_get_contents($cached));
			}
		}
	}
	
	if ($cachedjson == null)
	{		
		//$title = $_GET[ "title" ];
		//make_final($title,true);
		
		$result[ "title" ] = $title;

		$parts  = explode(" - ",$title);
		
		if (file_exists("../var/archive/" . $parts[ 0 ]))
		{
			$result[ "known" ] = "artist";
			flush_and_close($result);
		}

		$noartist = "../var/noartist/$title.json";
		$notrack  = "../var/notrack/$title.json";
		$pending  = "../var/pending/$title.json";

		if (file_exists($noartist)) 
		{
			if ((! isset($result[ "known" ])) ||
				($result[ "known" ] != "artist"))
			{
				$result[ "known" ] = "noartist";
				flush_and_close($result);
			}
			
			file_put_contents($notrack,".",FILE_APPEND);
		}
		else
		if (file_exists($notrack)) 
		{
			if ((! isset($result[ "known" ])) ||
				($result[ "known" ] != "artist"))
			{
				$result[ "known" ] = "notrack";
				flush_and_close($result);
			}
			
			file_put_contents($notrack,".",FILE_APPEND);
		}
		else
		{
			if ((! isset($result[ "known" ])) ||
				($result[ "known" ] != "artist"))
			{
				$result[ "known" ] = "pending";
				flush_and_close($result);
			}
			
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
		if (isset($cachedjson[ "itunes" ]) && ! isset($item[ "cover" ]))
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

		if (isset($cachedjson[ "discogs" ]) && ! isset($item[ "cover" ]))
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

		if ((! isset($cachedjson[ "itunes"  ])) &&
			(! isset($cachedjson[ "discogs" ])) &&
			( ! isset($item[ "cover" ])))
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
	//	Get stream url.
	//
	
	$channelcont = file_get_contents("../etc/channels/$channel/$channel.json");
	$channeljson = json_decdat($channelcont);
	$item[ "streamurl" ] = $channeljson[ "broadcast" ][ "streamUrls" ][ 0 ][ "streamUrl" ];
	
	//
	//	Put into now playing.
	//
	
	$logopath = "../etc/logos/$channel.167x167.png";
	if (file_exists($logopath)) $item[ "logo" ] = "$channel.167x167.png";
	
	$now = time();
	
	$nowplaying = "../var/nowplaying/" . gmdate("Y.m.d",$now);
	if (! is_dir($nowplaying)) mkdir($nowplaying,0775);
	$nowplaying = $nowplaying . "/" . gmdate("Y.m.d.Hi",$now) . ".json";
	
	file_put_contents($nowplaying,json_encdat($item) . ",\n",FILE_APPEND+LOCK_EX);	
}

register_track();

?>