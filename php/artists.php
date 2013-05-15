<?php

include "../php/json.php";
include "../php/util.php";

function save_package(&$packkey,&$package)
{
	if ($packkey === null) return;
	
	$packfile = "../lib/artists/" . $packkey . "xx.json";
	
	$json = json_encdat($package);
	file_put_contents($packfile,$json . "\n");
	
	echo "SAVED: $packfile\n";
}

function load_package(&$packkey,&$package)
{
	if ($packkey === null) return;
	
	$packfile = "../lib/artists/" . $packkey . "xx.json";
	if (! file_exists($packfile)) return;
	
	$json = file_get_contents($packfile);
	$package = json_decdat($json);
	
	echo "LOADED: $packfile\n";
}

function consume_artists(&$packkey,&$package,$data,$lfd = null,$online = false)
{
	json_nukeempty($data);
	
	if (! isset($data[ "id"   ])) return;
	if (! isset($data[ "name" ])) return;
	
	$id   = $data[ "id" ];
	$file = str_pad($id,10,"0",STR_PAD_LEFT);
	
	if (substr($file,0,-2) != $packkey) 
	{
		save_package($packkey,$package);
		
		$packkey = substr($file,0,-2);
		$package = Array();
		
		if ($online) load_package($packkey,$package);
	}
	
	$package[ $file ] = $data;
	
	$thisartist = nice_artist($data[ "name" ]);
	
	if ($lfd) fputs($lfd,"$file $thisartist\n");
	if ($online)    echo "$file $thisartist\n";
	
	if (isset($data[ "namevariations" ]))
	{
		if (isset($data[ "namevariations" ][ "name" ]))
		{
			//
			// From XML download.
			//
			
			if (is_array($data[ "namevariations" ][ "name" ]))
			{
				foreach ($data[ "namevariations" ][ "name" ] as $variation)
				{
					$variation = nice_artist($variation);
				
					if ($lfd) fputs($lfd,"$file $variation ~ $thisartist\n");
					if ($online)    echo "$file $variation ~ $thisartist\n";
				}
			}
			else
			{
				$variation = nice_artist($data[ "namevariations" ][ "name" ]);
			
				if ($lfd) fputs($lfd,"$file $variation ~ $thisartist\n");
				if ($online)    echo "$file $variation ~ $thisartist\n";
			}
		}
		else
		if (isset($data[ "namevariations" ][ 0 ]))
		{
			//
			// From JSON download.
			//
			
			foreach ($data[ "namevariations" ] as $variation)
			{
				$variation = nice_artist($variation);
		
				if ($lfd) fputs($lfd,"$file $variation ~ $thisartist\n");
				if ($online)    echo "$file $variation ~ $thisartist\n";
			}
		}

	}
}

function sort_callback($str1,$str2)
{
	$sort1 = substr($str1,11);
	$sort2 = substr($str2,11);
	
    if ($sort1 == $sort2) return 0;

    return ($sort1 < $sort2) ? -1 : 1;
}

function index_artists($dumpfile)
{	
	$lfd = popen("cat ../lib/dumps/$dumpfile.log ../lib/dumps/$dumpfile.*.log","r");
	
	$indices = Array();
	
	$indices[ "#" ] = "../var/indices/artists.temp.#.txt";
	$indices[ "~" ] = "../var/indices/artists.temp.~.txt";
	
	for ($inx = 0; $inx <= 9; $inx++)
	{
		$indices[ "$inx" ] = "../var/indices/artists.temp.$inx.txt";
	}
	
	for ($inx = 0; $inx < 26; $inx++)
	{
		$tag = chr($inx + ord("a"));
		$indices[ "$tag" ] = "../var/indices/artists.temp.$tag.txt";
	}
	
	$filedes = Array();
	
	foreach ($indices as $tag => $path)
	{
		$filedes[ $tag ] = fopen($path,"w");
	}
	
	while (($linein = fgets($lfd)) != null)
	{
		$line = trim($linein);
		$alias = strpos($line," ~ ");
		if ($alias !== false) $line = trim(substr($line,0,$alias));
		
		$artid  = substr($line,0,10);
		$artist = substr($line,11);
		$artist = comp_artist($artist);
		
		for ($run = 0; $run <= 8; $run++)
		{
			$index = $artist;
			
			if ($run == 1) $index = str_replace("'"," ",$index);
			if ($run == 2) $index = str_replace("'","" ,$index);
			if ($run == 3) $index = str_replace(":"," ",$index);
			if ($run == 4) $index = str_replace(":","" ,$index);
			if ($run == 5) $index = str_replace("-","/",$index);
			if ($run == 6) $index = str_replace("/"," ",$index);
			if ($run == 7) $index = str_replace("-"," ",$index);
			if ($run == 8) $index = str_replace("-","" ,$index);
			
			$index = trim(str_replace("  "," ",$index));
			
			if ($run && ($index == $artist)) continue;
			
			$tag = substr($index,0,1);
			
			$outline = "$artid $index\n";
			
			if (! isset($indices[ $tag ]))
			{
				if (ord($tag) < 128)
				{
					fputs($filedes[ '#' ],$outline);
				}
				else
				{
					fputs($filedes[ '~' ],$outline);
				}
			}
			else
			{
				fputs($filedes[ $tag ],$outline);
			}
		}
	}
	
	foreach ($indices as $tag => $path)
	{
		fclose($filedes[ $tag ]);
	}
	
	pclose($lfd);
	
	foreach ($indices as $tag => $temp)
	{
		echo "Sorting $temp\n";
		$sort = str_replace(".temp.",".sort.",$temp);
		
		system("sort < $temp > $sort.tmp");
		
		@unlink($temp);
		@unlink($sort);
		
		rename("$sort.tmp",$sort);
	}
}

function unpack_artists($dumpfile)
{
	if (file_exists("../lib/dumps/$dumpfile.log")) return;
	
	$pfd = popen("gunzip < ../lib/dumps/$dumpfile.xml.gz","r");
	$lfd = fopen("../lib/dumps/$dumpfile.log","w");
	
	fgets($pfd);
	
	$packkey = null;
	$package = Array();
	
	$artist = "";
	
	while (($line = fgets($pfd)) != null)
	{
		if (substr($line,0,8) == "<artist>")
		{
			if (strlen($artist) < 48) continue;
		
			$data = @simplexml_load_string($artist);
			consume_artists($packkey,$package,$data,$lfd);
			$artist = "";
		}
		
		$artist .= $line;
	}
	
	consume_artists($packkey,$package,$artist,$lfd);
	save_package($packkey,$package);
	
	pclose($pfd);
	fclose($lfd);
}


function download_artists($dumpfile)
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
		$url = "http://api.discogs.com/artists/" . $lastid;
				
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
		
		consume_artists($packkey,$package,$data,$lfd,true);
		
		fflush($lfd);

		if ((--$max < 0) && (($lastid % 100) == 0)) break;
		
		$missed = 0;
	}
	
	save_package($packkey,$package);

	fclose($lfd);
}

	$dumpfile = "discogs_20130401_artists";

	//unpack_artists($dumpfile);
	
	download_artists($dumpfile);

	index_artists($dumpfile);
	
?>