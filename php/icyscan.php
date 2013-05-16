<?php

include "../php/json.php";

function register_icy($channel,$icy)
{
	//return null;
	
	$url = "http://dezimac.local:80/register"
		 . "?channel=" . urlencode($channel)
		 . "&title=" .  urlencode($icy)
		 . "&host=" .  urlencode(gethostname())
		 ;
	
	//
	// Do NOT use file_get_contents here.
	//
	// It will wait until stream is closed and
	// not honor content length.
	//	 

	$response = "";
	
	$urlparts  = parse_url($url);

	$host  = $urlparts[ "host" ];
	$port  = $urlparts[ "port" ];
	$path  = $urlparts[ "path" ];
	$path .= "?" .$urlparts[ "query"    ];

	$fd = @fsockopen($host,$port,$errno,$errstr,4.0);
	
	if ($fd != null)
	{
		$header = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";

		fwrite($fd,$header);
		fflush($fd);
		
		$csize = 0;
		
		while (! feof($fd))
		{
			$line = fgets($fd);
			if (trim($line) == "") break;

			if (strtolower(substr($line,0,15)) == "content-length:")
			{
				$csize = intval(substr($line,15));
			}			
		}

		if ($csize && ! feof($fd)) $response = fread($fd,$csize);
		
		fclose($fd);
	}
	
	if ($response) $response = json_decdat($response);
	
	return $response;
}

function icy_configure()
{	
	if (isset($GLOBALS[ "icy_configtime" ]) && 
		((time() - $GLOBALS[ "icy_configtime" ]) < 10))
	{
		return;
	}
	
	$GLOBALS[ "icy_configtime" ] = time();
	
	$icyjson = file_get_contents("../php/icyscan.json");
	$icyscan = json_decdat($icyjson);
	
	if (! $icyscan)
	{
		echo "--------------------> config fucked...\n";
	}
	
	if (isset($icyscan[ "reverse" ]))
	{
		$reverse = Array();
		
		foreach ($icyscan[ "reverse" ] as $channel)
		{
			if (! strlen($channel)) continue;
			$reverse[ $channel ] = true;
		}
		
		$GLOBALS[ "reverse" ] = &$reverse;
	}
	
	if (isset($icyscan[ "ignore" ]))
	{
		$ignore = Array();
		
		foreach ($icyscan[ "ignore" ] as $channel)
		{
			if (! strlen($channel)) continue;
			$ignore[ $channel ] = true;
			
			//
			// Remove playlist.
			//
			
			$playlist = "../var/scanlists/$channel.txt";
			if (file_exists($playlist)) unlink($playlist);
		}
		
		$GLOBALS[ "ignore" ] = &$ignore;
	}

	$fixups = Array();
	
	if (isset($icyscan[ "fixups" ]))
	{
		if (isset($icyscan[ "fixups" ][ "str_replace" ]))
		{
			$str_replace = Array();
			
			foreach ($icyscan[ "fixups" ][ "str_replace" ] as $line)
			{
				if (! strlen($line)) continue;
				
				$first = substr($line,0,1);
				$parts = explode($first,$line);
				$parts[ 0 ] = $first;
				$channel = $parts[ 1 ];
				
				if (! isset($str_replace[ $channel ])) $str_replace[ $channel ] = Array();
				
				array_push($str_replace[ $channel ],$parts);
			}
			
			$fixups[ "str_replace" ] = &$str_replace;
		}
		
		if (isset($icyscan[ "fixups" ][ "preg_replace" ]))
		{
			$preg_replace = Array();
			
			foreach ($icyscan[ "fixups" ][ "preg_replace" ] as $line)
			{
				if (! strlen($line)) continue;
				
				$first = substr($line,0,1);
				$parts = explode($first,$line);
				$parts[ 0 ] = $first;
				$channel = $parts[ 1 ];
				
				if (! isset($preg_replace[ $channel ])) $preg_replace[ $channel ] = Array();
				
				array_push($preg_replace[ $channel ],$parts);
			}
			
			$fixups[ "preg_replace" ] = &$preg_replace;
		}
	}
	
	$GLOBALS[ "fixups" ] = &$fixups;
	
	$nuke = Array();
	
	if (isset($icyscan[ "nuke" ]))
	{
		if (isset($icyscan[ "nuke" ][ "strstr" ]))
		{
			$strstr = Array();
			
			foreach ($icyscan[ "nuke" ][ "strstr" ] as $line)
			{
				if (! strlen($line)) continue;
				
				$first = substr($line,0,1);
				$parts = explode($first,$line);
				$parts[ 0 ] = $first;
				$channel = $parts[ 1 ];
				
				if (! isset($strstr[ $channel ])) $strstr[ $channel ] = Array();
				
				array_push($strstr[ $channel ],$parts);
			}
			
			$nuke[ "strstr" ] = &$strstr;
		}
		
		if (isset($icyscan[ "nuke" ][ "preg_match" ]))
		{
			$preg_match = Array();
			
			foreach ($icyscan[ "nuke" ][ "preg_match" ] as $line)
			{
				if (! strlen($line)) continue;
				
				$first = substr($line,0,1);
				$parts = explode($first,$line);
				$parts[ 0 ] = $first;
				$channel = $parts[ 1 ];
				
				if (! isset($preg_match[ $channel ])) $preg_match[ $channel ] = Array();
				
				array_push($preg_match[ $channel ],$parts);
			}
			
			$nuke[ "preg_match" ] = &$preg_match;
		}
	}
	
	$GLOBALS[ "nuke" ] = &$nuke;
}

function fixups_icy($channel,$icy)
{
	if (isset($GLOBALS[ "fixups" ]) &&
	    isset($GLOBALS[ "fixups" ][ "str_replace" ]) &&
		isset($GLOBALS[ "fixups" ][ "str_replace" ][ $channel ]))
	{
		foreach ($GLOBALS[ "fixups" ][ "str_replace" ][ $channel ] as $parts)
		{
			$from = $parts[ 2 ];
			$toto = $parts[ 3 ];
			
			//echo "====> fixups str_replace($from,$toto) => $icy\n";

			$icy = trim(str_replace($from,$toto,$icy));
		}
	}
	
	if (isset($GLOBALS[ "fixups" ]) &&
	    isset($GLOBALS[ "fixups" ][ "preg_replace" ]) &&
		isset($GLOBALS[ "fixups" ][ "preg_replace" ][ $channel ]))
	{
		foreach ($GLOBALS[ "fixups" ][ "preg_replace" ][ $channel ] as $parts)
		{
			$sepa = $parts[ 0 ];
			$from = $parts[ 2 ];
			$toto = $parts[ 3 ];
			$modi = $parts[ 4 ];
			
			$pattern = $sepa . $from . $sepa . $modi;
			
			$ret = preg_replace($pattern,$toto,$icy);
			
			if ($ret === null)
			{
				echo "====> fixups failed $channel preg_replace($pattern) => $icy\n";
			}
			else
			{
				$icy = trim($ret);
			}
		}
	}
	
	return $icy;
}

function nuke_icy($channel,$icy)
{
	if (isset($GLOBALS[ "nuke" ]) &&
	    isset($GLOBALS[ "nuke" ][ "strstr" ]) &&
		isset($GLOBALS[ "nuke" ][ "strstr" ][ $channel ]))
	{		
		foreach ($GLOBALS[ "nuke" ][ "strstr" ][ $channel ] as $parts)
		{
			$pattern = $parts[ 2 ];
			
			if (strstr(strtolower($icy),strtolower($pattern)) !== false) 
			{
				//echo "====> nuke strstr($pattern) => $icy\n";

				return true;
			}
		}
	}
	
	if (isset($GLOBALS[ "nuke" ]) &&
	    isset($GLOBALS[ "nuke" ][ "strstr" ]) &&
		isset($GLOBALS[ "nuke" ][ "strstr" ][ "*" ]))
	{		
		foreach ($GLOBALS[ "nuke" ][ "strstr" ][ "*" ] as $parts)
		{
			$pattern = $parts[ 2 ];
			
			if (strstr(strtolower($icy),strtolower($pattern)) !== false) 
			{
				//echo "====> nuke strstr($pattern) => $icy\n";

				return true;
			}
		}
	}
	
	if (isset($GLOBALS[ "nuke" ]) &&
	    isset($GLOBALS[ "nuke" ][ "preg_match" ]) &&
		isset($GLOBALS[ "nuke" ][ "preg_match" ][ $channel ]))
	{
		foreach ($GLOBALS[ "nuke" ][ "preg_match" ][ $channel ] as $parts)
		{
			$sepa = $parts[ 0 ];
			$from = $parts[ 2 ];
			$modi = $parts[ 3 ];
			
			$pattern = $sepa . $from . $sepa . $modi;
			
			if (preg_match($pattern,$icy) === 1) 
			{			
				return true;
			}
		}
	}
	
	if (isset($GLOBALS[ "nuke" ]) &&
	    isset($GLOBALS[ "nuke" ][ "preg_match" ]) &&
		isset($GLOBALS[ "nuke" ][ "preg_match" ][ "*" ]))
	{
		foreach ($GLOBALS[ "nuke" ][ "preg_match" ][ "*" ] as $parts)
		{
			$sepa = $parts[ 0 ];
			$from = $parts[ 2 ];
			$modi = $parts[ 3 ];
			
			$pattern = $sepa . $from . $sepa . $modi;
			
			if (preg_match($pattern,$icy) === 1) 
			{			
				return true;
			}
		}
	}

	return ($channel != "*") ? nuke_icy("*",$icy) : false;
}

function nice_fup($icy)
{
	//$icy = mb_convert_case($icy,MB_CASE_LOWER,"UTF-8");
	$icy = mb_convert_case($icy,MB_CASE_TITLE,"UTF-8");
	
	/*
	$tst = " " . $icy;
	$len = strlen($tst);
	$icy = "";
	
	for ($inx = 1; $inx < $len; $inx++)
	{
		if (ctype_upper($tst[ $inx ]) && 
			(ctype_alnum($tst[ $inx - 1 ]) || 
			($tst[ $inx - 1 ] == "'") ||
			($tst[ $inx - 1 ] == "`")))
		{
			$icy .= strtolower($tst[ $inx ]);
		}
		else
		{
			$icy .= $tst[ $inx ];
		}
	}
	*/
	
	return $icy;
}

function nice_icy($icy)
{		
	//
	// Check for .mp3 at end.
	//
	
	if (substr($icy,-4) == ".MP3") $icy = substr($icy,0,-4);
	if (substr($icy,-4) == ".mp3") $icy = substr($icy,0,-4);
	if (substr($icy,-3) ==  "mp3") $icy = substr($icy,0,-3);
	if (substr($icy,-3) ==  "...") $icy = substr($icy,0,-3);
	if (substr($icy,-1) ==    "#") $icy = substr($icy,0,-1);

	//
	// Check for addition in square brackets.
	//
	
	if (substr(trim($icy),-1) == "]")
	{
		$icy = str_replace("[","(",$icy);
		$icy = str_replace("]",")",$icy);
	}
	
	//
	// Check for semicolon tags.
	//
	
	$parts = explode(";",$icy);
	
	if (count($parts) == 4)
	{
		$icy = $parts[ 1 ] . " - " . $parts[ 2 ];
	}
	
	//
	// Check for single chars > 127 => ISO-LATIN.
	//
	
	$tst = " " . $icy . " ";
	$len = strlen($tst) - 1;
	$iso = false;
	
	for ($inx = 1; $inx < $len; $inx++)
	{
		if ((ord($tst[ $inx - 1 ])  < 128) &&
			(ord($tst[ $inx + 0 ]) >= 128) && 
			(ord($tst[ $inx + 1 ])  < 128))
		{
			$iso = true;
			break;
		}
	}
	
	if ($iso) $icy = utf8_encode($icy);
	
	//
	// Common shit.
	//
	
	$icy = str_replace("_"," ",$icy);
	$icy = str_replace("´","'",$icy);
	$icy = str_replace("`","'",$icy);
	
	$icy = str_replace("( ","(",$icy);
	$icy = str_replace(" )",")",$icy);
	$icy = str_replace("("," (",$icy);
	
	$icy = str_replace("\\'","'",$icy);
	$icy = str_replace("\""," ",$icy);
	$icy = str_replace(" / "," - ",$icy);
	$icy = str_replace(" -- "," - ",$icy);
	$icy = str_replace(", - "," - ",$icy);
	$icy = str_replace(" -=- "," - ",$icy);
	
	$icy = str_replace("    "," ",$icy);
	$icy = str_replace("   "," ",$icy);
	$icy = str_replace("  "," ",$icy);
	
	$icy = trim($icy);
	
	//
	// Sie hören bla bla.
	//
	
	if (strstr($icy,"Sie hören \"") !== false)
	{
		$icy = substr($icy,12);
		if (substr($icy,-1) == "\"") $icy = substr($icy,0,-1);
		$icy = str_replace("\" mit \""," - ",$icy);
	}
		
	if (substr($icy,-10) == " auf WDR 2")
	{
		$icy = substr($icy,0,-10);
	}
	
	if (strstr($icy," auf WDR 2 - \"") !== false)
	{
		$icy = str_replace(" auf WDR 2 - \""," - ",$icy);
		if (substr($icy,-1) == "\"") $icy = substr($icy,0,-1);
	}
	
	if (strstr($icy," - Immer Ihre Musik: \"") !== false)
	{
		$icy = str_replace(" - Immer Ihre Musik: \""," - ",$icy);
		if (substr($icy,-1) == "\"") $icy = substr($icy,0,-1);
	}
 
	//
	// No hyphen but " mit ".
	//
	
	if ((strstr($icy, " - " ) === false) && 
		(strstr($icy," mit ") !== false))
	{
		$icy = str_replace(" mit "," - ",$icy);
	}
	
	//
	// No hyphen but " von ".
	//
	
	if ((strstr($icy, " - " ) === false) && 
		(strstr($icy," von ") !== false))
	{
		$parts = explode(" von ",$icy);
		$icy = trim($parts[ 1 ] . " - " . $parts[ 0 ]);
	}
	
	if ((strstr($icy," - ") === false) && 
		(strstr($icy,"|") !== false))
	{
		$parts = explode("|",$icy);
		$icy = trim($parts[ 1 ] . " - " . $parts[ 0 ]);
	}
	
	if ((strstr($icy," - ") === false) && 
		(strstr($icy,"-") !== false))
	{
		$icy = str_replace("-"," - ",$icy);
	}
	
	if (substr($icy,-1) == "-") $icy = trim(substr($icy,0,-1));
	if (substr($icy,-1) == "*") $icy = trim(substr($icy,0,-1));
	
	if ((strstr($icy,"(") !== false) && 
		(strstr($icy,")") === false))
	{
		$icy .= ")";
	}
	
	$icy = trim($icy);
	
	if (substr($icy,-1) == ")")
	{
		if (($pos = strpos($icy,"(ft ")) ||
			($pos = strpos($icy,"(Ft ")) ||
			($pos = strpos($icy,"(FT ")) ||
			($pos = strpos($icy,"(ft. ")) ||
			($pos = strpos($icy,"(Ft. ")) ||
			($pos = strpos($icy,"(FT. ")) ||
			($pos = strpos($icy,"(feat ")) ||
			($pos = strpos($icy,"(Feat ")) ||
			($pos = strpos($icy,"(FEAT ")) ||
			($pos = strpos($icy,"(feat. ")) ||
			($pos = strpos($icy,"(Feat. ")) ||
			($pos = strpos($icy,"(FEAT. ")) ||
			($pos = strpos($icy,"(featuring ")) ||
			($pos = strpos($icy,"(Featuring ")) ||
			($pos = strpos($icy,"(FEATURING ")))
		{
			$feat  = substr($icy,$pos + 1,-1);
			$icy   = substr($icy,0,$pos);
			$parts = explode(" - ",$icy);
			$parts[ 0 ] .= " " . $feat;
			$icy = implode(" - ",$parts);
		}	
	}
	
	return $icy;
}

function nice_artist($icy)
{
	$parts = explode(" - ",$icy);
	if (count($parts) != 2) return $icy;
	
	$artist = $parts[ 0 ];
	$artist .= " ";
	
	if (substr($artist,0,4) == "The ") $artist = substr($artist,4);
	 	
	$artist = str_replace("* "," ",$artist);
	$artist = str_replace(",",", ",$artist);
	$artist = str_replace(" ,",",",$artist);
	$artist = str_replace("  "," ",$artist);
	
	$artist = str_replace(", The "," ",$artist);
	$artist = str_replace(", The*"," ",$artist);
	$artist = str_replace(", The("," (",$artist);
	$artist = str_replace(", The\""," \"",$artist);
	
	$artist = str_replace(",the "," ",$artist);
	$artist = str_replace(",the*"," ",$artist);
	$artist = str_replace(",the("," (",$artist);
	$artist = str_replace(",the\""," \"",$artist);
	
	$artist = str_replace(", Die "," ",$artist);
	$artist = str_replace(", Die*"," ",$artist);
	$artist = str_replace(", Die("," (",$artist);
	$artist = str_replace(", Die\""," \"",$artist);

	$artist = str_replace(",die "," ",$artist);
	$artist = str_replace(",die*"," ",$artist);
	$artist = str_replace(",die("," (",$artist);
	$artist = str_replace(",die\""," \"",$artist);
	
	$artist = str_replace("("," ",$artist);
	$artist = str_replace(")"," ",$artist);
	
	$artist = str_replace("  "," ",$artist);
	$artist = str_replace("  "," ",$artist);
	$artist = trim($artist);
	
	return $artist . " - " . $parts[ 1 ];
}

function feat_icy($icy)
{
	$parts = explode(" - ",$icy);
	
	if ((count($parts) == 2) && (strpos($parts[ 1 ],"(") === false))
	{
		$funzs = explode(" Feat. ",$parts[ 1 ]);
		
		if (count($funzs) == 2)
		{
			$icy = $parts[ 0 ] 
				 . " Feat. " 
				 . $funzs[ 1 ] 
				 . " - "
				 . $funzs[ 0 ];
		}
	}

	return $icy;
}

function reverse_icy($channel,$icy)
{
	if (isset($GLOBALS[ "reverse" ][ $channel ]))
	{
		$parts = explode(" - ",$icy);
		
		if (count($parts) == 2)
		{
			$temps = $parts[ 0 ];
			$parts[ 0 ] = $parts[ 1 ];
			$parts[ 1 ] = $temps;
			$icy = implode(" - ",$parts);
		}
	}
	
	return $icy;
}

function case_icy($icy)
{
	//
	// Check all upper case.
	//
	
	/*
	if (strtoupper($icy) == $icy)
	{
		$icy = nice_fup($icy);
	}
	else
	{
		//
		// Check for artist xor title all upper case.
		//
		
		if (strstr($icy," - ") !== false)
		{
			$parts = explode(" - ",$icy);
			
			if (strtoupper($parts[ 0 ]) == $parts[ 0 ])
			{
				$parts[ 0 ] = nice_fup($parts[ 0 ]);

				$icy = implode(" - ",$parts);
			}
			
			if (strtoupper($parts[ 1 ]) == $parts[ 1 ])
			{
				$parts[ 1 ] = nice_fup($parts[ 1 ]);

				$icy = implode(" - ",$parts);
			}
		}
	}
	*/
	
	$icy = nice_fup($icy);

	//
	// Common abbreviations.
	//
	
	$icy = str_replace("Ac Dc","Ac-Dc",$icy);
	$icy = str_replace("Ac/Dc","Ac-Dc",$icy);
	$icy = str_replace("Ac/dc","Ac-Dc",$icy);

	$icy = str_replace("&acute;","'",$icy);
	$icy = str_replace("&nbsp;"," ",$icy);
	$icy = str_replace("&amp;","&",$icy);
	$icy = str_replace("&auml;","ä",$icy);
	$icy = str_replace("&ouml;","ö",$icy);
	$icy = str_replace("&uuml;","ü",$icy);
	$icy = str_replace("&#039;","'",$icy);
	
	$icy = str_replace(" vs."," Vs. ",$icy);
	$icy = str_replace(" Vs."," Vs. ",$icy);
	$icy = str_replace(" ft."," Feat. ",$icy);
	$icy = str_replace(" Ft "," Feat. ",$icy);
	$icy = str_replace(" Feat "," Feat. ",$icy);
	$icy = str_replace(" Ft."," Feat. ",$icy);
	$icy = str_replace(" Feat."," Feat. ",$icy);
	
	$icy = trim(str_replace("P!nk"," Pink",$icy));
	$icy = trim(str_replace("(Radio)"," ",$icy));
	$icy = trim(str_replace("(Radio Edit)"," ",$icy));
	$icy = trim(str_replace("(Radio Version)"," ",$icy));
	
	$icy = trim(str_replace(", The - "," - ",$icy));
	$icy = trim(str_replace(", Die - "," - ",$icy));
	
	$icy = str_replace("   "," ",$icy);
	$icy = str_replace("  "," ",$icy);
	
	return $icy;
}

function get_channels($what)
{
	$channels = Array();
	
	$dd = opendir("../etc/$what");
	
	while (($file = readdir($dd)) !== false)
	{
		if ($file == ".") continue;
		if ($file == "..") continue;
		
		array_push($channels,$file);
	}
	
	closedir($dd);
	
	return $channels;
}

function get_channel_config($channel)
{
	$jsonfile = "../etc/channels/$channel/$channel.json";
	$jsoncont = file_get_contents($jsonfile);
	$json = json_decdat($jsoncont);
	
	return $json;
}

function open_channel(&$havechannels,&$openchannels,&$deadchannels)
{
	if (isset($GLOBALS[ "kbits" ]))
	{
		if ($GLOBALS[ "kbits" ] > 1000000) 
		{
			if ($GLOBALS[ "actopens" ] > $GLOBALS[ "minopens" ])
			{
				$GLOBALS[ "actopens" ] -= 1;
			}
		}
	}
	
	if (count($havechannels) == 0) 
	{
		$havechannels = get_channels("channels");
		
		if ($GLOBALS[ "roundcount" ] > 0)
		{
			$round = $GLOBALS[ "roundcount" ];
			$time  = time() - $GLOBALS[ "roundtime" ];
			
			echo "===============================================> $round $time\n";
		}
		
		$GLOBALS[ "roundcount" ] += 1;
		$GLOBALS[ "roundtime"  ]  = time();
	}
	
	if (count($havechannels) == 0) return;
	
	$channel = array_pop($havechannels);
	if (isset($GLOBALS[ "ignore" ][ $channel ])) return;
	
	if (isset($deadchannels[ $channel ]))
	{
		if (time() < $deadchannels[ $channel ]) return;
		
		unset($deadchannels[ $channel ]);
	}
	
	$setup = get_channel_config($channel);
	if ($setup == null) return;
	
	if (! isset($setup[ "broadcast" ])) return;
	if (! isset($setup[ "broadcast" ][ "streamUrls" ])) return;
	if (! isset($setup[ "broadcast" ][ "streamUrls" ][ 0 ])) return;
	
	$streamconf = $setup[ "broadcast" ][ "streamUrls" ][ 0 ];
	
	if (! isset($streamconf[ "streamUrl" ])) return;
	$streamurl = $streamconf[ "streamUrl" ];
	
	$oc = Array();
	$oc[ "channel" ] = $channel;
	$oc[ "setup"   ] = $setup;
	$oc[ "url"     ] = $streamurl;
	$oc[ "head"    ] = false;
	$oc[ "headers" ] = Array();
	$oc[ "start"   ] = time();
	
	array_push($oc[ "headers" ],$streamurl);
	array_push($openchannels,$oc);	
}

function put_deadchannel(&$deadchannels,$channeldata,$reason = null,$penalty = 0)
{
	$channel  = $channeldata[ "channel" ];
	$deadfile = "../var/scanerrors/$channel.json";
	
	if (($reason === null) && ($penalty == 0))
	{
		if (file_exists($deadfile)) unlink($deadfile);
		if (isset($deadchannels[ $channel ])) unset($deadchannels[ $channel ]);
		
		return;
	}
	
	$dump = Array();
	
	if (file_exists($deadfile))
	{
		$dump = json_decdat(file_get_contents($deadfile));
	}

	if (! isset($dump[ "retries" ])) $dump[ "retries" ] = 0;
	
	$dump[ "reason"  ]  = $reason;
	$dump[ "url"     ]  = $channeldata[ "url" ];
	$dump[ "header"  ]  = $channeldata[ "headers" ];
	$dump[ "retries" ] += 1;
	
	file_put_contents($deadfile,json_encdat($dump) . "\n");

	if ($penalty < 0) $penalty = $dump[ "retries" ] * -$penalty;
	
	$deadchannels[ $channel ] = time() + $penalty;
}

function process_channel(&$openchannels,&$deadchannels)
{
	for ($inx = 0; $inx < count($openchannels); $inx++)
	{		
		$channel = $openchannels[ $inx ][ "channel" ];
		$start   = $openchannels[ $inx ][ "start"   ];
		$elapsed = time() - $start;
		
		if ($elapsed > 20)
		{
			if (isset($openchannels[ $inx ][ "fd" ]))
			{
				fclose($openchannels[ $inx ][ "fd" ]);
			}
			
			echo "--------------------> elapsed $channel\n";
	
			array_splice($openchannels,$inx--,1);
			
			//
			// Reduce channel load.
			//
			
			if ($GLOBALS[ "actopens" ] > $GLOBALS[ "minopens" ])
			{
				$GLOBALS[ "actopens" ] -= 1;
			}
			
			continue;
		}
		
		if (! isset($openchannels[ $inx ][ "fd" ]))
		{
			$streamurl = $openchannels[ $inx ][ "url" ];
			$urlparts  = parse_url($streamurl);
			
			if (! isset($urlparts[ "host" ]))
			{
				put_deadchannel($deadchannels,$openchannels[ $inx ],"badurl",3600);
				array_splice($openchannels,$inx--,1);
				
				echo "--------------------> invalid $streamurl\n";
				continue;
			}
			
			$host = $urlparts[ "host" ];
			$port = isset($urlparts[ "port" ]) ? $urlparts[ "port" ] : 80;
			$path = isset($urlparts[ "path" ]) ? $urlparts[ "path" ] : "/";
			
			if (isset($urlparts[ "query"    ])) $path .= "?" .$urlparts[ "query"    ];
			if (isset($urlparts[ "fragment" ])) $path .= "?" .$urlparts[ "fragment" ];
			
			$fd = @fsockopen($host,$port,$errno,$errstr,4.0);
			
			if ($fd == null) 
			{
				put_deadchannel($deadchannels,$openchannels[ $inx ],"timeout",-10);
				array_splice($openchannels,$inx--,1);
				
				echo "--------------------> timeout $channel $streamurl\n";
				continue;
			}
			
			$header = "GET $path HTTP/1.1\r\nHost: $host\r\nIcy-Metadata: 1\r\n\r\n";
			
			fwrite($fd,$header);
			fflush($fd);
			
			stream_set_blocking($fd,0);
			
			$openchannels[ $inx ][ "fd" ] = $fd;
						
			//echo "-----> $channel\n";

			continue;
		}
		
		$fd = $openchannels[ $inx ][ "fd" ];
		
		if (feof($fd))
		{
			fclose($fd);
			array_splice($openchannels,$inx--,1);

			echo "------------------------> " 
				. $channel
				. " (died)\n";
					
			continue;
		}

		if ($openchannels[ $inx ][ "head" ])
		{
			$chunk  = $openchannels[ $inx ][ "chunk"  ];
			$toread = $openchannels[ $inx ][ "toread" ];
			
			if ($toread > 0)
			{
				$mp3data = fread($fd,$toread);
				if ($mp3data == null) continue;
				$toread -= strlen($mp3data);
				$openchannels[ $inx ][ "toread" ] = $toread;
				$GLOBALS[ "downbytes" ] += strlen($mp3data);
			}
			
			if ($toread == 0)
			{
				/*
				echo "-----> " 
					. $channel
					. " (icy!)\n";
				*/
				
				stream_set_blocking($fd,1);

				$len = fread($fd,1);
				$taglen = 16 * ord($len);
				
				if ($taglen == 0)
				{
					/*
					echo "-----> " 
						. $channel
						. " (icy?)\n";
					*/
					
					fclose($fd);
					array_splice($openchannels,$inx--,1);
					
					continue;
				}
				
				$icyline = fread($fd,$taglen);
				
				if (preg_match_all("|StreamTitle='(.*?)';|",$icyline,$icyres))
				{
					$icy = $icyres[ 1 ][ 0 ];
					
					if (! strlen($icy))
					{
						put_deadchannel($deadchannels,$openchannels[ $inx ],"emptyicy",-10);
					}
					else
					{
						put_deadchannel($deadchannels,$openchannels[ $inx ]);
						
						//
						// Check against last original icy string.
						//

						$lasticytext = "";
						$lasticyfile = "../var/lasticys/$channel.orig.txt";
						
						if (file_exists($lasticyfile))
						{
							$lasticytext = file_get_contents($lasticyfile);
						}
						
						if ($lasticytext == $icy)
						{
							$GLOBALS[ "samescan" ][ $channel ] = time();
							$deadchannels[ $channel ] = time() + 20;
							
							continue;
						}

						file_put_contents($lasticyfile,$icy);
						
						//
						// Tune icy string.
						//
						
						$icy = nice_icy($icy);
						$icy = fixups_icy($channel,$icy);
						$icy = case_icy($icy);
						$icy = reverse_icy($channel,$icy);
						$icy = feat_icy($icy);
						$icy = nice_artist($icy);
						
						if (nuke_icy($channel,$icy)) continue;

						//
						// Check against last validated icy string.
						//

						$lasticytext = "";
						$lasticyfile = "../var/lasticys/$channel.song.txt";
						
						if (file_exists($lasticyfile))
						{
							$lasticytext = file_get_contents($lasticyfile);
						}
						
						if ($lasticytext == $icy)
						{
							continue;
						}

						file_put_contents($lasticyfile,$icy);

						$total = time() - $start;
						$total = str_pad($total,2," ",STR_PAD_LEFT); 
						$opens = count($openchannels); 
						$opens = str_pad($opens,2," ",STR_PAD_LEFT); 
						$deads = count($deadchannels); 
						$deads = str_pad($deads,3," ",STR_PAD_LEFT);
						
						$kbits = $GLOBALS[ "downbytes" ] * 10;
						$kbits = $kbits / (time() - $GLOBALS[ "downstamp" ]);
						$kbits = (int) ($kbits / 1024);
						$kbits = str_pad($kbits,5," ",STR_PAD_LEFT);
						
						$GLOBALS[ "kbits" ] = $kbits;
						
						$line = date("Ymd.His")
							  . " "
							  . str_pad($channel,30," ",STR_PAD_RIGHT) 
							  . " $icy\n";
						
						$playlistfile = "../var/scanlists/$channel.txt";
						file_put_contents($playlistfile,$line,FILE_APPEND);
						
						$response = register_icy($channel,$icy);						
						
						$query = " ";
							
						if ($response) 
						{
							if ($response[ "known" ] == "cached" ) $query = "$";
							if ($response[ "known" ] == "archive") $query = "@";
						}
						
						$line = date("Ymd.His")
							  . " "
							  . str_pad($channel,30," ",STR_PAD_RIGHT) 
							  . " $query$icy\n";
						
						if ($query != '?') $GLOBALS[ "itemsfound" ]++;
						if ($query == '$') $GLOBALS[ "itemsknown" ]++;
						
						if ($GLOBALS[ "itemsfound" ] > 1000)
						{
							$GLOBALS[ "itemsfound" ] = floor($GLOBALS[ "itemsfound" ] / 10);
							$GLOBALS[ "itemsknown" ] = floor($GLOBALS[ "itemsknown" ] / 10);
						}
						
						$got = floor(99 * $GLOBALS[ "itemsknown" ] / $GLOBALS[ "itemsfound" ]);
						
						$sscan = isset($GLOBALS[ "samescan" ][ $channel ]) ? (time() - $GLOBALS[ "samescan" ][ $channel ]) : "-"; 
						$lscan = isset($GLOBALS[ "lastscan" ][ $channel ]) ? (time() - $GLOBALS[ "lastscan" ][ $channel ]) : "-"; 
						
						if ($sscan > 100) $sscan = "-";
						
						$sscan = str_pad($sscan,3," ",STR_PAD_LEFT); 
						$lscan = str_pad($lscan,3," ",STR_PAD_LEFT); 

						echo "$total $opens/$deads $kbits kbit/s $sscan $lscan $got% $line";
												
						//
						// Disable scan of channel for a while.
						//
						
						if (($sscan > 0) && ($lscan > 0))
						{
							if ($lscan > 150) $lscan = 150;
							$wait = $lscan - $sscan;
							if ($wait > 0) $deadchannels[ $channel ] = time() + $wait;
						}
						
						$GLOBALS[ "lastscan" ][ $channel ] = time();

						//
						// Check channel load.
						//
						
						if ($total < 5)
						{
							if ($GLOBALS[ "actopens" ] < $GLOBALS[ "maxopens" ])
							{
								$GLOBALS[ "actopens" ] += 1;
							}
						}
						else
						{
							if ($GLOBALS[ "actopens" ] > $GLOBALS[ "minopens" ])
							{
								$GLOBALS[ "actopens" ] -= 1;
							}
						}
					}
				}
				
				fclose($fd);
				array_splice($openchannels,$inx--,1);
				
				continue;
			}
		}
		else
		{
			$line = fgets($fd);
			if ($line == null) continue;
			
			if ($line == "\r\n")
			{
				/*
				echo "-----> " 
					. $channel
					. " (head)\n";
				*/
				
				if (! isset($openchannels[ $inx ][ "chunk" ]))
				{					
					fclose($fd);
					
					put_deadchannel($deadchannels,$openchannels[ $inx ],"nochunk",3600);
					array_splice($openchannels,$inx--,1);
					
					continue;
				}
					
				$openchannels[ $inx ][ "head" ] = true;
				continue;
			}
			
			array_push($openchannels[ $inx ][ "headers" ],trim($line));
			
			if (substr($line,1,8) == "ocation:")
			{
				$reloc = trim(substr($line,9));
				fclose($fd);

				unset($openchannels[ $inx ][ "fd" ]);
				
				$openchannels[ $inx ][ "url"  ] = $reloc;
				$openchannels[ $inx ][ "head" ] = false;
			}
			
			if (substr($line,0,12) == "icy-metaint:")
			{	
				$openchannels[ $inx ][ "chunk"  ] = (int) substr($line,12);
				$openchannels[ $inx ][ "toread" ] = (int) substr($line,12);
				
				/*
				echo "-----> " 
					. $channel
					. " ("
					. $openchannels[ $inx ][ "chunk" ] 
					. ")\n";
				*/
			}
		}
	}
}
	
	$havechannels = Array();
	$openchannels = Array();
	$deadchannels = Array();
	
	$samescan = Array();
	$lastscan = Array();
	
	$downbytes  = 0;
	$downstamp  = time() - 1;
	$roundcount = 0;
	$roundtime  = 0;
	$itemsknown = 0;
	$itemsfound = 0;
	
	$minopens   =  5;
	$maxopens   = 40;
	$actopens   =  5;
	
	while (true)
	{
		icy_configure();
		
		if (count($openchannels) < $actopens) open_channel($havechannels,$openchannels,$deadchannels);
		if (count($openchannels) < $actopens) open_channel($havechannels,$openchannels,$deadchannels);
		if (count($openchannels) < $actopens) open_channel($havechannels,$openchannels,$deadchannels);
		if (count($openchannels) < $actopens) open_channel($havechannels,$openchannels,$deadchannels);
		
		if (count($openchannels) > 0) process_channel($openchannels,$deadchannels);

		if (($act = (time() - $downstamp)) > 10)
		{
			$downbytes = (int) ($downbytes / 2);
			$downstamp = time() - ($act / 2);
		}
		
		usleep(1);
	}
?>
