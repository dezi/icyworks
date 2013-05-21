<?php

include "json.php";
include "util.php";

function save_package(&$packkey,&$package)
{
	if ($packkey === null) return;
	
	$packfile = "../lib/releases/" . $packkey . "xx.json";
	
	$json = json_encdat($package);
	file_put_contents($packfile,$json . "\n");
	
	echo "SAVED: $packfile\n";
}

function load_package(&$packkey,&$package)
{
	if ($packkey === null) return;
	
	$packfile = "../lib/releases/" . $packkey . "xx.json";
	if (! file_exists($packfile)) return;
	
	$json = file_get_contents($packfile);
	$package = json_decdat($json);
	
	echo "LOADED: $packfile\n";
}

function consume_release(&$packkey,&$package,$data,$lfd = null,$online = false)
{	
	json_nukeempty($data);
	
	if (isset($data[ "@attributes" ]))
	{
		if (! isset($data[ "@attributes" ][ "id" ])) return;
	
		$id = $data[ "@attributes" ][ "id" ];
	}
	else
	if (isset($data[ "id" ]))
	{
		$id = $data[ "id" ];
	}
	else
	{
		return;
	}
	
	$file = str_pad($id,10,"0",STR_PAD_LEFT);
	
	if (substr($file,0,-2) != $packkey) 
	{
		save_package($packkey,$package);
		
		$packkey = substr($file,0,-2);
		$package = Array();
		
		if ($online) load_package($packkey,$package);
	}
	
	$package[ $file ] = $data;
	
	$artist = "";
	
	if (isset($data[ "artists" ])) $artist = build_artist($data[ "artists" ],$artistids);
	
	if (isset($data[ "tracklist" ]))
	{
		if (isset($data[ "tracklist" ][ 0 ]))
		{
			//
			// From JSON download.
			//
		
			$tracklist = $data[ "tracklist" ];
		}
		else
		if (isset($data[ "tracklist" ][ "track" ]))
		{
			//
			// From XML download.
			//
			
			if (isset($data[ "tracklist" ][ "track" ][ 0 ]))
			{
				//
				// Multiple tracks.
				//
				
				$tracklist = $data[ "tracklist" ][ "track" ];
			}
			else
			{
				//
				// Single track.
				//
				
				$tracklist = Array($data[ "tracklist" ][ "track" ]);
			}
		}
			
		foreach ($tracklist as $track)
		{
			if (! isset($track[ "title" ])) continue;
			
			$title = trim($track[ "title" ]);
			if (! strlen($title)) continue;
			
			$thisartist    = $artist;
			$thisartistids = $artistids;
						
			if (isset($track[ "artists" ])) 
			{
				$thisartist = build_artist($track[ "artists" ],$thisartistids);
			}
			
			$ids = "[" . implode(",",$thisartistids) . "]";
			
			if ($lfd) 
			{
				fputs($lfd,"$file $ids $thisartist - $title\n");
			}

			if ($online)
			{
				echo "$file $ids $thisartist - $title\n";
			}
		}
	}
}

function unpack_releases($dumpfile)
{
	if (file_exists("../lib/dumps/$dumpfile.log")) return;
	
	$pfd = popen("gunzip < ../lib/dumps/$dumpfile.xml.gz","r");
	$lfd = fopen("../lib/dumps/$dumpfile.log","w");
	
	fgets($pfd);
	
	$packkey = null;
	$package = Array();
	
	$release = "";
	
	while (($line = fgets($pfd)) != null)
	{
		if (substr($line,0,12) == "<release id=")
		{
			if (strlen($release) != 0)
			{
				$data = simplexml_load_string($release);
				consume_release($packkey,$package,$data,$lfd);
			}
			
			$release = "";
		}
		
		$release .= $line;
	}
	
	if (strlen($release) != 0)
	{
		$data = @simplexml_load_string($release);
		consume_release($packkey,$package,$data,$lfd);
	}
			
	save_package($packkey,$package);
	
	pclose($pfd);
	fclose($lfd);
}

function sort_callback($str1,$str2)
{
	$sort1 = substr($str1,11);
	$sort2 = substr($str2,11);
	
    if ($sort1 == $sort2) return 0;

    return ($sort1 < $sort2) ? -1 : 1;
}

function flush_liveart($liveart)
{
	foreach ($liveart as $artkey => $contents)
	{
		$livepath = "../var/indices.new/liveart.$artkey.json";
		
		file_put_contents($livepath,json_encdat($contents));
	}
}

function index_tracks($dumpfile)
{
	$lfd = popen("cat ../lib/dumps/$dumpfile.log ../lib/dumps/$dumpfile.*.log","r");
	
	system("rm -rf ../var/indices.new");
	mkdir("../var/indices.new",0775);
	
	$indices = Array();
	$liveart = Array();
	
	for ($inx = 0; $inx <= 99; $inx++)
	{
		$tag = str_pad("$inx",2,"0",STR_PAD_LEFT);
		$indices[ $tag ] = "../var/indices.new/tracks.temp.$tag.txt";
	}
	
	$filedes = Array();
	$origdes = Array();
	
	foreach ($indices as $tag => $path)
	{
		$filedes[ $tag ] = fopen($path,"w");
		$orig = str_replace(".temp.",".orig.",$path);
		$origdes[ $tag ] = fopen($orig,"w");
	}

	while (($line = fgets($lfd)) != null)
	{
		$line  = trim($line);
		if (! strlen($line)) continue;

		$idnum = substr($line,0,10);
		$track = substr($line,11);
		
		$title = strpos($track,"] ");
		$idart = substr($track,1,$title - 1);
		if (! strlen($idart)) continue;
		$idart = explode(",",$idart);
		$title = substr($track,$title + 2);
		
		$title = preg_replace("/\\([0-9]*\\) - /","",$title);
				
		$parts = explode(" - ",$title);
		if (count($parts) != 2) continue;
		
		$parts[ 0 ] = comp_artist($parts[ 0 ]);
		$parts[ 1 ] = comp_title ($parts[ 1 ]);
		
		foreach ($idart as $idartist)
		{
			$idartist = str_pad($idartist,10,"0",STR_PAD_LEFT);
			$tag  = substr($idartist,8,2);
			if (! isset($filedes[ $tag ])) continue;
			
			$artinx = substr($idartist,-3);			
			if (! isset($liveart[ $artinx ])) $liveart[ $artinx ] = Array();
			if (! isset($liveart[ $artinx ][ $idartist ])) $liveart[ $artinx ][ $idartist ] = 0;
			$liveart[ $artinx ][ $idartist ]++;
			
			$origpos = str_pad(ftell($origdes[ $tag ]),10,"0",STR_PAD_LEFT);
			
			$line = $idartist . " " . $idnum . " " . $origpos . " " . $parts[ 0 ] . " - " .$parts[ 1 ];
			$orig = $idartist . " " . $idnum . " " . $title;
		
			fputs($filedes[ $tag ],$line . "\n");
			fputs($origdes[ $tag ],$orig . "\n");
		}
	}
	
	foreach ($indices as $tag => $path)
	{
		fclose($filedes[ $tag ]);
		fclose($origdes[ $tag ]);
	}
	
	pclose($lfd);
	
	flush_liveart($liveart);
	
	for ($inx = 0; $inx <= 99; $inx++)
	{
		$tag  = str_pad("$inx",2,"0",STR_PAD_LEFT);
		$temp = "../var/indices.new/tracks.temp.$tag.txt";
		$sort = "../var/indices.new/tracks.sort.$tag.txt";
		
		echo "Sorting $sort...\n";
		
		system("sort < $temp > $sort.tmp");
		
		@unlink($temp);
		@unlink($sort);
		
		rename("$sort.tmp",$sort);
	}
	
	system("mv ../var/indices.new/* ../var/indices");
	system("rmdir ../var/indices.new");
}

function download_releases($dumpfile)
{
	$options_api = array
	(
  		'http' => array
  		(
    		'method' => "GET",
    		'header' => "User-Agent: KappaRadioPlaylistTracker/0.1 (dezi@kappa-mm.de) +http://www.kappa-mm.de/\r\n",
			'end' => true  	
    	)
	);
	
	$context_api = stream_context_create($options_api);

	$pfd = popen("tail ../lib/dumps/$dumpfile.log ../lib/dumps/$dumpfile.*.log","r");
	
	$lastid = 0;
	
	while (($line = fgets($pfd)) != null)
	{
		$id = 0 + substr($line,0,10);
		if ($id > $lastid) $lastid = $id;
	}
	
	pclose($pfd);
	
	$lastid = floor(($lastid / 100)) * 100;

	echo "Lastid=$lastid\n";
		
	$packkey = null;
	$package = Array();
	
	$missed = 0;
	
	$lfdname = "../lib/dumps/$dumpfile." . gmdate("Ymd") . ".log";
	$lfd = fopen($lfdname,"a");
	
	$max = 999999;
	
	while (true)
	{
		$url = "http://api.discogs.com/releases/" . $lastid;
				
		$cont = @file_get_contents($url,false,$context_api);
		
		if ($cont === false)
		{
			if (count($http_response_header))
			{
				if (($http_response_header[ 0 ] == "HTTP/1.1 404 Not Found") ||
					($http_response_header[ 0 ] == "HTTP/1.1 500 Internal Server Error"))
				{
					echo "MISS: $url\n";
					$lastid += 1;

					if (++$missed > 100) break;

					continue;
				}
			
				echo "QUOTA: $url\n";
				echo "QUOTA:" . $http_response_header[ 0 ] . "\n";
			}
			else
			{
				echo "NO INTERNET\n";
			}
			
			sleep(1);
			continue;
		}	

		echo "LOAD: $url\n";
		$lastid += 1;
		
		$data = json_decdat($cont);
		if (! isset($data[ "id" ])) continue;
		
		consume_release($packkey,$package,$data,$lfd,true);
		
		fflush($lfd);

		if ((--$max < 0) && (($lastid % 100) == 0)) break;
		
		$missed = 0;
	}
	
	save_package($packkey,$package);

	fclose($lfd);
}

	$dumpfile = "discogs_20130401_releases";
	
	//unpack_releases($dumpfile);
	
	download_releases($dumpfile);
	
	index_tracks($dumpfile);
	
?>