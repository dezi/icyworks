<?php

include "../php/json.php";
include "../php/util.php";

//
// https://itunes.apple.com/search?term=beatrice+egli&country=de&media=music&entity=music&attribute=mixTerm
// https://itunes.apple.com/search?term=beatrice+egli&country=DE&media=music&attribute=mixTerm
// https://itunes.apple.com/search?term=beatrice+egli+mein+herz&country=de&media=music&entity=song

//

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
	$dupldi = Array();
	$duplle = Array();
	
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
	
			$artistfile = "../var/indices/artists.sort.$tag.txt";
	
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
					$artid = substr($line,0,11);
					
					if (! isset($dupldi[ $artid ]))
					{
						$dupldi[ $artid ] = true;
						array_push($direct,$artid);
						//echo "=========> $artid $compare\n";
						$doleven = false;
					}
				}
				else
				if (comp_levenshtein($compare,$item))
				{
					$artid = substr($line,0,11);
					
					if (! isset($duplle[ $artid ]))
					{
						$duplle[ $artid ] = true;
						array_push($levens,$artid);
						//echo "~~~~~~~~~> $artid $compare\n";
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

function search_discogs($track,$nodead = false)
{
	if (substr($track,-5) == ".json") $track = substr($track,0,-5);

	$parts = explode(" - ",$track);
	if (count($parts) != 2) return false;
	
	$artists = search_artist($track);
		
	if (($artists === false) || ! count($artists))
	{
		return 'nodiscogs';
	}
	
	$oname = $parts[ 0 ];
	$title = comp_title($parts[ 1 ]);
	
	$result = Array();
	
	foreach ($artists as $artist)
	{
		if ((substr($artist,-1) == "+") && ! $nodead)
		{
			//
			// Deadstuff artist.
			//
			
			continue;
		}
		
		$artist = substr($artist,0,-1);
		$tag = substr($artist,8,2);
		
		if (! $nodead)
		{
			$deadstuff = "../var/deadstuff/artist.$artist.dead";
			$isdead    = file_exists($deadstuff) ? '-' : '+';
		
			if ($isdead == '-') continue;
		}
		
		$tracksort = "../var/indices/tracks.sort.$tag.txt";
		$trackorig = "../var/indices/tracks.orig.$tag.txt";

		$pfd = fopen($tracksort,"r");
		$ofd = fopen($trackorig,"r");

		$filesize = filesize($tracksort);
		$chunksiz = floor($filesize / 10);
		
		$startpos = $chunksiz;
		
		while (($startpos + $chunksiz) < $filesize)
		{
			fseek($pfd,$startpos);
			
			$line = fgets($pfd);
			$line = fgets($pfd);
			$artid = substr($line,0,10);
			
			if ($artid >= $artist) break;
			
			$startpos += $chunksiz;
		}
	
		$startpos -= $chunksiz;

		$havestuff = false;	
		
		while (true)
		{
			fseek($pfd,$startpos);
			if ($startpos > 0) $line = fgets($pfd);
			
			while (($line = fgets($pfd)) != null)
			{
				$artid = substr($line,0,10);
				if ($artid < $artist) continue;
				if ($artid > $artist) break;

				$line      = substr($line,0,-1);
				$havestuff = true;
				$release   = substr($line,11,10);
				$origpos   = intval(substr($line,22,10));

				$parts     = explode(" - ",$line);
				$compare   = array_pop($parts);
			
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
				
					echo "D[$release] ($distance) $orig\n";
				}
			}
			
			if (count($result)) break;
			
			$pos = strrpos($title,"(");
			if ($pos === false) break;
			
			$title = trim(substr($title,0,$pos));
		}
				
		fclose($pfd);
		fclose($ofd);

		if ((! $havestuff) && (! $nodead)) file_put_contents($deadstuff,"");
	}
	
	if (! count($result)) return "nodiscogs";
		
	$discogs = Array();
	$discogs[ "discogs" ] = &$result;
	
	return $discogs;
}

function search_library($workdir,&$library,$minsize,$maxrand,$nodead,$maxage)
{
	clearstatcache();
	 
	if (! isset($GLOBALS[ "hadvip" ]))
	{
		 $GLOBALS[ "hadvip" ] = true;
	}
	
	$viptracks = 0;
	
	foreach ($library as $track)
	{
		$pending = "$workdir/$track";
					
		$size = @filesize($pending);

		if (! make_final($track))
		{
			echo "Schrott => [$size] $track\n";
			unlink($pending);
		
			continue;
		}
		
		$checkit = $pending;
		
		if (title_known($checkit))
		{
			echo chr(7) . chr(7) . chr(7) . "Schon da => $track\n";
			unlink($pending);
			
			continue;
		}
		
		if ($size === false) continue;
	
		if ($size >= $minsize)
		{
			$viptracks++;
		}
		else
		{
			if (rand(0,$maxrand) || $GLOBALS[ "hadvip" ]) continue;
		}
	
		echo "[$size] $track\n";

		$itunes  = search_itunes ($track,$nodead);
		$discogs = search_discogs($track,$nodead);
		
		if ((($itunes  === false) || ($itunes  == "noitunes" )) && 
			(($discogs === false) || ($discogs == "nodiscogs")))
		{
			echo "Not found => $track\n";

			if ($maxage)
			{
				$mtime = filemtime($pending);
	
				if ((time() - $mtime) > ($maxage * 86400))
				{
					echo "Expired => [$size] $track\n";
					unlink($pending);
		
					continue;
				}
			}
		
			$parts = explode(" - ",$track);
			
			$targetdir = "../var/noartist/$track";

			if (file_exists("../var/archive/" . $parts[ 0 ]))
			{
				$targetdir = "../var/notrack/$track";
			}
			
			if ($pending != $targetdir) rename($pending,$targetdir);

			continue;
		}

		$result = Array();
		
		if (is_array($itunes ) && isset($itunes [ "itunes"  ])) $result[ "itunes"  ] = $itunes [ "itunes"  ];
		if (is_array($discogs) && isset($discogs[ "discogs" ])) $result[ "discogs" ] = $discogs[ "discogs" ];

		if (! isset($result[ "itunes"  ])) $result[ "itunes"  ] = Array();
		if (! isset($result[ "discogs" ])) $result[ "discogs" ] = Array();
		
		$images  = "../var/doimages/$track";
		
		title_put_contents($track,json_encdat($result) . "\n");
		file_put_contents($images,json_encdat($result) . "\n");
				
		unlink($pending);
	}
		
	$GLOBALS[ "hadvip" ] = ($viptracks > 0);
}

function search_itunes($track)
{
	$term   = str_replace(" - "," ",substr($track,0,-5));
	$search = "http://itunes.apple.com/search?country=de&media=music&entity=song&limit=200&term=" . urlencode($term);
	$json   = file_get_contents($search);
	$data   = json_decdat($json);
	$count  = $data[ "resultCount" ];

	if ($count == 0)
	{
		$term = str_replace("Ae","A",$term);
		$term = str_replace("Ue","U",$term);
		$term = str_replace("Oe","O",$term);
		$term = str_replace("ae","a",$term);
		$term = str_replace("ue","u",$term);
		$term = str_replace("oe","o",$term);
		
		$search = "http://itunes.apple.com/search?country=de&media=music&entity=song&limit=200&term=" . urlencode($term);
		$json   = file_get_contents($search);
		$data   = json_decdat($json);
		$count  = $data[ "resultCount" ];
	}

	$compare = substr($track,0,-5);
	make_final($compare);
	
	$results = Array();
	
	foreach($data[ "results" ] as $result)
	{
		$collect = str_pad($result[ "collectionId" ],10,"0",STR_PAD_LEFT);
		$artist  = $result[ "artistName" ];
					
		$foundit = false;

		for ($run = 0; $run <= 1; $run++)
		{
			if (($run == 0) && ! isset($result[ "trackName"          ])) continue;
			if (($run == 1) && ! isset($result[ "trackCensoredName"  ])) continue;
			
			$song    = ($run == 0) ? $result[ "trackName" ] : $result[ "trackCensoredName" ];
			$title   = $artist . " - " . $song;
		
			$title = preg_replace('/(.*?) - (.*?) \((Feat.*?)\) (.*)/iu',"$1 $3 - $2 $4",$title);
			$title = preg_replace('/(.*?) - (.*?) \((Feat.*?)\)/iu',"$1 $3 - $2 $4",$title);
		
			$title = preg_replace('/(.*?) - (.*?) \[(Feat.*?)\] (.*)/iu',"$1 $3 - $2 $4",$title);
			$title = preg_replace('/(.*?) - (.*?) \[(Feat.*?)\]/iu',"$1 $3 - $2 $4",$title);
		
			$item = $title;
			make_final($item);
		
			if (comp_levenshtein($compare,$item))
			{
				$foundit = true;
				breaK;
			}
			else
			{
				//
				// Retry w/o extras in title.
				//
			
				while (($pos = strrpos($item,"(")) > 0)
				{
					$item = trim(substr($item,0,$pos));
				
					if (comp_levenshtein($compare,$item))
					{
						$foundit = true;
						break;
					}
				}
			}
			
			if ($foundit) break;
		}
		
		if (! $foundit) continue;
		
		$distance = levenshtein($compare,$item);
				
		echo "I[$collect] ($distance) $title\n";

		$genre    = $result[ "primaryGenreName" ];
		$coverid  = "I-150-$collect.jpg";
		$arturl1  = $result[ "artworkUrl100" ];
		$arturl2  = str_replace(".100x100-",".150x150-",$arturl1);
		$coversub = substr($collect,-3);
		
		if (($arturl1 == $arturl2) || ! $arturl1)
		{
			echo json_encdat($result) . "\n";
			echo "NO URI 150 => $arturl2\n";
			exit(0);
		}
				
		$coverpath = "../lib/images/itunes/$coversub";
		if (! is_dir($coverpath)) @mkdir($coverpath,0775);
		$coverpath = "$coverpath/$coverid";
		
		if (! file_exists($coverpath))
		{
			$jpeg = @file_get_contents($arturl2);
		
			if ($jpeg === false) file_get_contents($arturl2);
			
			if ($jpeg === false)
			{
				echo "NO ARTWORK => $arturl2\n";
				
				continue;
			}
			else
			{
				file_put_contents($coverpath,$jpeg);
			}
		}
		
		$item = Array();
		$item[ "title"      ] = $title;
		$item[ "collection" ] = $collect;
		$item[ "distance"   ] = $distance;
		$item[ "cover"      ] = $coverid;
		$item[ "genre"      ] = $genre;
		
		$results[ $collect ] = $item;
	}
	
	if (count($results) == 0) return "noitunes";
	
	$itunes = Array();
	$itunes[ "itunes" ] = &$results;
	
	return $itunes;
}
	
	/*
	$title = "Dash Berlin - Waiting (feat. Emma Hewitt)";
	$title = preg_replace('/(.*?) - (.*?) \((Feat.*?)\)(.*)/iu',"$1 $3 - $2 $4",$title);
	echo "$title\n";
	exit(0);
	*/
	
	$GLOBALS[ "debug" ] = false;

	$notracks    = Array();
	$notracksdir = "../var/notrack";
	
	while (true)
	{
		echo "Scanning...\n";
		
		//$library = Array();
		//$library[] = "Ben Kweller - Wasted & Ready.json";

		if (true)
		{
			$workdir = "../var/pending";
			$library = get_directory($workdir);
			$maxrand = floor(sqrt(count($library)));
			$nodead  = true;
			$minsize = 0;
			$maxage  = 9999;
		
			search_library($workdir,$library,$minsize,$maxrand,$nodead,$maxage);
		}
				
		sleep(1);		

		continue;
		
		if (count($notracks) == 0) 
		{
			$songtracks = get_directory($notracksdir);
			
			$notracks = Array();
			$nocounts = Array();
			
			foreach ($songtracks as $songtrack)
			{
				$parts = explode(" - ",$songtrack);
				if (! isset($notracks[ $parts[ 0 ] ])) $notracks[ $parts[ 0 ] ] = Array();
				$notracks[ $parts[ 0 ] ][] = $songtrack;
			}
			
			foreach ($notracks as $key => $array)
			{
				$nocounts[ $key ] = rand(0,100); //count($array);
			}
			
			arsort($nocounts);
		}
		else
		{
			foreach ($nocounts as $key => $count)
			{
				$dosome = $notracks[ $key ];
				unset($notracks[ $key ]);
				array_shift($nocounts);
				break;
			}
			
			search_library($notracksdir,$dosome,0,0,true,2);
		}
	}
	
?>
