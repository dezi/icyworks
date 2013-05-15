<?php

include "../php/json.php";
include "../php/util.php";

$releases  = Array("00045263xx.json");

$releases = get_directory("../lib/releases");

$allgenres  = Array();
$allstyles  = Array();
$allformats = Array();

foreach ($releases as $release)
{
	if ($release < "00044663xx.json") continue;
	
	$file = "../lib/releases/$release";
	$exer = "../lib/excerpts/$release";
	
	//if (file_exists($exer)) continue;
	
	$cont = file_get_contents($file);
	$json = json_decdat($cont);
	
	if ($json === null) json_defuck($file);
	
	echo "$file\n";
	
	$excerpts = Array();

	foreach ($json as $key => $data)
	{
		$excerpt = Array();

		if (isset($data[ "title" ]))
		{
			$excerpt[ "name" ] = $data[ "title" ];
		}
		
		$excerpt[ "compilation" ] = false;
		
		if (isset($data[ "artists" ]))
		{
			$artist = build_artist($data[ "artists" ],$dummy);
			$excerpt[ "artist" ] = $artist;
			
			$excerpt[ "compilation" ] = ($artist == "Various");
		}
	
		if (isset($data[ "images" ]))
		{			
			if (isset($data[ "images" ][ "image" ]))
			{
				//
				// From XML download.
				//
				
				$images = $data[ "images" ][ "image" ];
			}
			else
			if (isset($data[ "images" ][ 0 ]))
			{
				//
				// From JSON download.
				//
				
				$images = $data[ "images" ];
			}
			
			if (isset($images[ "@attributes" ])) $images = Array($images);

			$image = null;
			
			if (! $image)
			{
				foreach ($images as $target)
				{
					if (isset($target[ "@attributes" ]))
					{
						//
						// From XML download.
						//
						
						if (! isset($target[ "@attributes" ][ "type" ])) continue;
						if ($target[ "@attributes" ][ "type" ] != "primary") continue;
					}
					else
					{
						//
						// From JSON download.
						//
						
						if (! isset($target[ "type" ])) continue;
						if ($target[ "type" ] != "primary") continue;
					}
					
					$image = $target; 
			
					break;
				}
			}
			
			if (! $image)
			{
				foreach ($images as $target)
				{
					$image = $target; 
			
					break;
				}
			}
			
			if ($image)
			{
				if (isset($image[ "@attributes" ][ "uri150" ]))
				{
					$uri150 = $image[ "@attributes" ][ "uri150" ];
				}
				else
				{
					$uri150 = $image[ "uri150" ];
				}
				
				$uri150 = explode("/",$uri150);
				$excerpt[ "coverload" ] = array_pop($uri150);
			}
		}
		
		if (isset($data[ "formats" ]))
		{
			if (isset($data[ "formats" ][ "format" ]))
			{
				$formats = $data[ "formats" ][ "format" ];
			}
			else
			{
				$formats = $data[ "formats" ];
			}
			
			if (! isset($formats[ 0 ])) $formats = Array($formats);
			
			$names = Array();
			
			foreach ($formats as $format) 
			{
				if (isset($format[ "@attributes" ][ "name" ]))
				{
					$name = $format[ "@attributes" ][ "name" ];
				}
				else
				{
					$name = $format[ "name" ];
				}
				
				if (isset($allformats[ $name ]))
					$allformats[ $name ]++;
				else
					$allformats[ $name ] = 1;
					
				array_push($names,$name);
			}
			
			$excerpt[ "format" ] = implode("|",$names);
		}
		
		if (isset($data[ "genres" ]))
		{
			if (isset($data[ "genres" ][ "genre" ]))
			{
				$genres = $data[ "genres" ][ "genre" ];
			}
			else
			{
				$genres = $data[ "genres" ];
			}
			
			if (! is_array($genres)) $genres = Array($genres);
			
			foreach ($genres as $genre) 
			{
				if (isset($allgenres[ $genre ]))
					$allgenres[ $genre ]++;
				else
					$allgenres[ $genre ] = 1;
			}
			
			$excerpt[ "genres" ] = implode("|",$genres);
		}
		
		if (isset($data[ "styles" ]))
		{
			if (isset($data[ "styles" ][ "style" ]))
			{
				$styles = $data[ "styles" ][ "style" ];
			}
			else
			{
				$styles = $data[ "styles" ];
			}
			
			if (! is_array($styles)) $styles = Array($styles);
			
			foreach ($styles as $style)
			{
				if (isset($allstyles[ $style ]))
					$allstyles[ $style ]++;
				else
					$allstyles[ $style ] = 1;
			}
			
			$excerpt[ "styles" ] = implode("|",$styles);
		}
		
		$excerpts[ $key ] = $excerpt;
	}
	
	file_put_contents($exer,json_encdat($excerpts) . "\n");
}

file_put_contents("genres.json" ,json_encdat($allgenres ));
file_put_contents("styles.json" ,json_encdat($allstyles ));
file_put_contents("formats.json",json_encdat($allformats));

?>