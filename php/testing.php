<?php

include "../php/json.php";
include "../php/util.php";

function search_artist($track)
{
	$parts = explode(" - ",$track);
	if (count($parts) != 2) return false;
	
	$artist = comp_artist($parts[ 0 ]);
	
	//echo "COMP $artist\n";
	
	$search = explode(" and ",$artist);
	if (count($search) > 1) array_unshift($search,$artist);
	
	foreach ($search as $item)
	{
		if (strstr($item,", "))
		{
			$parts = explode(", ",$item);
			if (count($parts) != 2) continue;
			array_push($search,$parts[ 1 ] . " " . $parts[ 0 ]);
		}
	}
	
	foreach ($search as $item)
	{
		if ($item == "pink" ) array_unshift($search,"p!nk" );	
		if ($item == "a ha" ) array_unshift($search,"a-ha" );	
		if ($item == "acdc" ) array_unshift($search,"ac/dc");	
		if ($item == "ac-dc") array_unshift($search,"ac/dc");
		if ($item == "rem"  ) array_unshift($search,"r.e.m.");
		
		if ($item == "groehnemeyer") array_unshift($search,"herbert groenemeyer");	
		if ($item == "omd") array_unshift($search,"orchestral manoeuvres in the dark");	
	}
	
	if ($GLOBALS[ "debug" ])
	{
		echo "ARTIST: " . implode(", ",$search) . "\n";
	}

	$direct = Array();
	$levens = Array();
	
	for ($run = 0; $run <= 3; $run++)
	{
		foreach ($search as $item)
		{
			if ($run == 1) $item = "Die " . $item;
			
			if ($run == 2)
			{
				$item = str_replace(", "," ",$item);
				$item = str_replace("  "," ",$item);
				
				$parts = explode(" ",$item);
				
				if (count($parts) == 2) 
				{
					$item = $parts[ 1 ] . " " . $parts[ 0 ];
				}
				else
				{
					continue;
				}
			}
			
			if ($run == 3)
			{
				$oldi = $item;
				$item = str_replace(", "," and ",$item);
				
				if ($oldi == $item) continue;
			}
			
			$tag = substr($item,0,1);
	
			if (! strstr("0123456789abcdefghijklmnopqrstuvwxyz",$tag))
			{
				$tag = (ord($tag) < 128) ? '#' : '~';
			}
	
			$artistfile = "../var/indices/artists.$tag.txt";
	
			$fd = fopen($artistfile,"r");
			
			if ($GLOBALS[ "debug" ])
			{
				echo "ARTIST: $artistfile $item\n";
			}
			
			while (($line = fgets($fd)) != null)
			{
				$compare = substr($line,11,-1);
				
				if ($compare == $item)
				{
					//echo "=========> $compare\n";
					
					$artid = substr($line,0,10);
					
					if (! is_file("../var/deadstuff/artist.$artid.dead"))
					{
						array_push($direct,$artid);
						$doleven = false;
					}
				}
				else
				if (comp_levenshtein($compare,$item))
				{
					//echo "~~~~~~~~~> $compare\n";
					
					$artid = substr($line,0,10);
					
					if (! is_file("../var/deadstuff/artist.$artid.dead"))
					{
						array_push($levens,$artid);
					}
				}
			}
		
			fclose($fd);
		}
	
		if (count($direct) > 0) return $direct;
		if (count($levens) > 0) return $levens;
	}
	
	return false;	
}

function schedule_artist($track)
{
	if (substr($track,-5) == ".json") $track = substr($track,0,-5);

	$parts = explode(" - ",$track);
	if (count($parts) != 2) return false;
	
	$artists = search_artist($track);
		
	if (($artists === false) || ! count($artists))
	{
		$track = $parts[ 1 ] . " - " . $parts[ 0 ];
		$parts = explode(" - ",$track);

		$artists = search_artist($track);
		
		if (($artists === false) || ! count($artists))
		{
			return 'noartist';
		}
	}

	if (count($artists) > 1) return "notnow";
	
	return count($artists);
}

function search_track($track)
{
	if (substr($track,-5) == ".json") $track = substr($track,0,-5);

	$parts = explode(" - ",$track);
	if (count($parts) != 2) return false;
	
	$artists = search_artist($track);
		
	if (($artists === false) || ! count($artists))
	{
		$track = $parts[ 1 ] . " - " . $parts[ 0 ];
		$parts = explode(" - ",$track);

		$artists = search_artist($track);
		
		if (($artists === false) || ! count($artists))
		{
			return 'noartist';
		}
	}
	
	$oname = $parts[ 0 ];
	$title = comp_title($parts[ 1 ]);
	
	$result = Array();
	
	foreach ($artists as $artist)
	{
		$tag = substr($artist,8,2);
		
		$deadstuff = "../var/deadstuff/artist.$artist.dead";
		$isdead    = file_exists($deadstuff) ? '-' : '+';
		
		if ($isdead == '-') continue;
		
		$trackindex = "../var/indices/tracks.large.$tag.txt";
		$trackorig  = "../var/indices/tracks.orig.$tag.txt";
		
		while (true)
		{
			$pfd = popen("grep \"^$artist\" $trackindex","r");
			$ofd = fopen($trackorig,"r");

			$havestuff = false;
		
			while (($line = fgets($pfd)) != null)
			{
				$havestuff = true;
				$line      = substr($line,0,-1);
				$parts     = explode(" - ",$line);
				$compare   = array_pop($parts);
				$release   = substr($line,11,10);
				$origpos   = intval(substr($line,22,10));
			
				$distance = -1;
			
				if ($compare == $title)
				{
					$distance = 0;
				}
				else
				if (comp_levenshtein($compare,$title))
				{
					$distance = levenshtein($compare,$title);
				}
			
				if ($distance >= 0)
				{
					fseek($ofd,$origpos);
					$orig = substr(trim(fgets($ofd)),22);
				
					$match = Array();
					$match[ "title"    ] = $orig;
					$match[ "release"  ] = $release;
					$match[ "distance" ] = $distance;
				
					array_push($result,$match);
				
					echo "[$release] ($distance) $orig\n";
				}
			}
		
			pclose($pfd);
			fclose($ofd);
			
			if (count($result)) break;
			
			$pos = strrpos($title,"(");
			if ($pos === false) break;
			
			$title = trim(substr($title,0,$pos));
		}
		
		if (! $havestuff) file_put_contents($deadstuff,"");
	}
	
	return $result;
}

	$GLOBALS[ "debug" ] = false;

	$workdir = $GLOBALS[ "debug" ] ? "../var/debug" : "../var/pending"; 
	$library = get_directory($workdir);
	$lastart = "";
	
	foreach ($library as $track)
	{
		$parts = explode(" - ",$track);
		
		if ($parts[ 0 ] != $lastart)
		{
			$result = search_artist($track);
			$lastart = $parts[ 0 ];
		}
		
		if ($result === false)
		{
			echo "No Artist => $track\n";

			rename("$workdir/$track","../var/noartist/$track");

			continue;
		}
	
		if ($result && (count($result) <= 2))
		{
			$artid = substr($result[ 0 ],-2);
			$tag = str_pad($artid,2,"0",STR_PAD_LEFT);

			echo "Schedule $tag => $track\n";
		
			$optimized = "../var/optimized/$tag.txt";
			file_put_contents($optimized,$track . "\n",FILE_APPEND);
			
			continue;
		}
		
		echo "Not now => $track\n";
	}
?>