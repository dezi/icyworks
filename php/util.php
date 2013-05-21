<?php

/*
			<User>admin</User>
			<Password>kappa</Password>
			
			<URLLgo>http://192.168.55.1/start.htm</URLLgo>
			<URLDis>http://192.168.55.1/setup.cgi?todo=disconnect</URLDis>
			<URLCon>http://192.168.55.1/setup.cgi?todo=connect</URLCon>
			<URLLgf>http://192.168.55.1/setup.cgi?todo=logout</URLLgf>
*/

function title_known(&$trackfile)
{
	$title = explode("/",$trackfile);
	$title = array_pop($title);
	$title = substr($title,0,-5);
	
	if (! make_final($title))
	{
		return false;
	}
	
	$artist = explode(" - ",$title);
	$artist = $artist[ 0 ];
	
	$trackfile = "../var/cached/$title.json";
	if (file_exists($trackfile)) return true;
	
	$trackfile = "../var/archive/$artist/$title.json";
	if (file_exists($trackfile)) return true;
	
	return false;
}

function title_exists(&$trackfile)
{
	if (file_exists($trackfile))
	{
		return true;
	}
	
	$title = explode("/",$trackfile);
	$title = array_pop($title);
	$title = substr($title,0,-5);
	
	if (! make_final($title))
	{
		return false;
	}
		
	$artist = explode(" - ",$title);
	$artist = $artist[ 0 ];
	
	$trackfile = "../var/archive/$artist/$title.json";

	return file_exists($trackfile);
}

function title_get_contents(&$trackfile)
{
	if (file_exists($trackfile))
	{
		return file_get_contents($trackfile);
	}
	
	$title = explode("/",$trackfile);
	$title = array_pop($title);
	$title = substr($title,0,-5);
	
	if (! make_final($title))
	{
		return null;
	}
	
	$artist = explode(" - ",$title);
	$artist = $artist[ 0 ];
	
	$trackfile = "../var/archive/$artist/$title.json";

	return file_get_contents($trackfile);
}

function title_put_contents(&$trackfile,$json)
{
	$title = explode("/",$trackfile);
	$title = array_pop($title);
	$title = substr($title,0,-5);
	
	if (! make_final($title))
	{
		echo "Pupsi $trackfile $title\n";
		return false;
	}
	
	$artist = explode(" - ",$title);
	$artist = $artist[ 0 ];
	
	$tracksdir = "../var/archive/$artist";
	$trackfile = "../var/archive/$artist/$title.json";
	if (! is_dir($tracksdir)) mkdir($tracksdir,0775);
	return file_put_contents($trackfile,$json);
}

function make_final(&$title,$keeputf8 = false)
{
	if (($isext = (substr($title,-5) == ".json")))
	{
		$title = substr($title,0,-5);
	}

	$title = utf8_nfd2nfc($title);
	
	$title = str_replace("&amp;","&",$title);
	$title = str_replace("&nbsp;"," ",$title);
	$title = str_replace("Jÿrgen","Jürgen",$title);
	$title = str_replace("\\","",$title);
	
	if (! $keeputf8)
	{
		$title = str_replace("Ä","Ae",$title);
		$title = str_replace("Ö","Oe",$title);
		$title = str_replace("Ü","Ue",$title);
		$title = str_replace("ä","ae",$title);
		$title = str_replace("ö","oe",$title);
		$title = str_replace("ü","ue",$title);
		$title = str_replace("ß","ss",$title);
		$title = str_replace("ÿ","y",$title);

		for ($inx = 0; $inx < strlen($title); $inx++)
		{
			if (ord($title[ $inx ]) >= 128) 
			{
				$title = make_plain_ascii($title);
			
				break;
			}
		}
	}
	
	$parts = explode(" - ",$title);
	
	if (count($parts) == 3)
	{
		if (substr($parts[ 2 ],0,6) == "Feat. ")
		{
			$parts[ 0 ] .= " " . array_pop($parts);
		}
		else
		if (substr($parts[ 2 ],0,4) == "Live")
		{
			$parts[ 1 ] .= " (" . array_pop($parts) . ")";
		}
	}
	
	while (strlen($parts[ 0 ]) && (strstr("%-. ",$parts[ 0 ][ 0 ]) !== false))
	{
		$parts[ 0 ] = substr($parts[ 0 ],1);
	}

	if (substr($parts[ 0 ],0,5) == "The ") $parts[ 0 ] = substr($parts[ 0 ],5);
	
	if (substr($parts[ 0 ],-4) ==  ",the") $parts[ 0 ] = substr($parts[ 0 ],0,-4);
	if (substr($parts[ 0 ],-5) == ", the") $parts[ 0 ] = substr($parts[ 0 ],0,-5);
	if (substr($parts[ 0 ],-4) ==  ",The") $parts[ 0 ] = substr($parts[ 0 ],0,-4);
	if (substr($parts[ 0 ],-5) == ", The") $parts[ 0 ] = substr($parts[ 0 ],0,-5);
	
	$parts[ 0 ] = str_replace(" Mit "," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Und "," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" And "," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" + "," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace("/"," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace("&"," & ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(";"," & ",$parts[ 0 ]);
	
	$parts[ 0 ] = str_replace(" -","-",$parts[ 0 ]);
	$parts[ 0 ] = str_replace("- ","-",$parts[ 0 ]);
	$parts[ 0 ] = str_replace("(","",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(")","",$parts[ 0 ]);

	$parts[ 0 ] = str_replace(" Featuring "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Pres. "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Feat. "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Feat- "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Feat "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Ft. "," feat. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Ft " ," feat. ",$parts[ 0 ]);
	
	$parts[ 0 ] = str_replace(" Vs " ," vs. ",$parts[ 0 ]);
	$parts[ 0 ] = str_replace(" Vs. "," vs. ",$parts[ 0 ]);	
	
	if (strpos($parts[ 0 ],","))
	{
		$commas = explode(",",$parts[ 0 ]);
		
		if (count($commas) == 2)
		{
			$parts[ 0 ] = trim($commas[ 1 ] . " " . $commas[ 0 ]);
		}
		else
		{
			$parts[ 0 ] = str_replace(","," & ",$parts[ 0 ]);
			$parts[ 0 ] = str_replace("  "," ",$parts[ 0 ]);
		}
	}
	
	$title = implode(" - ",$parts);
	
	$title = str_replace("/"," ",$title);
	$title = str_replace("   "," ",$title);
	$title = str_replace("  "," ",$title);
	
	$title = mb_convert_case(trim($title),MB_CASE_TITLE,"UTF-8");
	
	if ($isext) $title .= ".json";
	
	return (count($parts) == 2);
}

function find_image($imagename,&$imagepath)
{
	$path      = "../lib/images/releases";
	$parts     = explode("-",$imagename);
	$imagepath = $path . "/" . substr("00" . $parts[ 2 ],-3) . "/" . $imagename;
	
	return file_exists($imagepath);
}

function comp_levenshtein($str1,$str2,$maxdist = 0)
{
	if (! $maxdist) $maxdist = floor(min(strlen($str1),strlen($str2)) / 6);
	if (! $maxdist) $maxdist = 1;
	
	if (abs(strlen($str1) - strlen($str2)) > $maxdist) return false;
	
	$dist = levenshtein($str1,$str2);
	
	return ($dist <= $maxdist);
}

function build_artist($artists,&$idlist)
{
	$artist = "";
	$idlist = Array();
	
	if (isset($artists[ "artist" ]) &&
	    isset($artists[ "artist" ][ "name" ]))
	{
		//
		// From XML download...
		//

		$artist = nice_artist($artists[ "artist" ][ "name" ]);
		array_push($idlist,$artists[ "artist" ][ "id" ]);
	}
	else
	if (isset($artists[ "artist" ]))
	{
		//
		// From XML download...
		//
	
		foreach ($artists[ "artist" ] as $name)
		{
			$artist .= " " . nice_artist($name[ "name" ]);
			array_push($idlist,$name[ "id" ]);

			if (isset($name[ "join" ]) && ! is_array($name[ "join" ])) 
			{
				if ($name[ "join" ] == ",")
				{
					$artist .= $name[ "join" ];
				}
				else
				{
					$artist .= " " . $name[ "join" ];
				}
			}
		}
		
		$artist = trim($artist);
	}
	else
	{
		//
		// From JSON download...
		//
		
		foreach ($artists as $name)
		{
			if (! isset($name[ "name" ])) continue;
			
			$artist .= " " . nice_artist($name[ "name" ]);
			array_push($idlist,$name[ "id" ]);

			if (isset($name[ "join" ]) && ! is_array($name[ "join" ])) 
			{
				if ($name[ "join" ] == ",")
				{
					$artist .= $name[ "join" ];
				}
				else
				{
					$artist .= " " . $name[ "join" ];
				}
			}
		}
		
		$artist = trim($artist);
	}
	
	return $artist;
}


function nice_artist($artist)
{
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
	
	$artist = str_replace(";"," Feat. ",$artist);
	
	$artist = str_replace("  "," ",$artist);
	$artist = str_replace("  "," ",$artist);
	$artist = str_replace("  "," ",$artist);
	
	return trim($artist);
}
	
function utf8_nfd2nfc($str)
{
	// To keep stuff simple, we only check all decomposable
	// simple letter character candidates.
	// Sample: 0x41 0xcc 0x88 => 0xc3 0x84 (&adieresis; or &auml;)
	
	if (isset($GLOBALS[ "NFD_NFC_tupels" ]))
	{
		$NFD_NFC_tupels = $GLOBALS[ "NFD_NFC_tupels" ];
	}
	else
	{
		$NFD_NFC_tupels = Array
		(
		  0x41cc80, 0xc380, 0x45cc80, 0xc388, 0x49cc80, 0xc38c, 0x4ecc80, 0xc7b8,
		  0x4fcc80, 0xc392, 0x55cc80, 0xc399, 0x61cc80, 0xc3a0, 0x65cc80, 0xc3a8,
		  0x69cc80, 0xc3ac, 0x6ecc80, 0xc7b9, 0x6fcc80, 0xc3b2, 0x75cc80, 0xc3b9,
		  0x41cc81, 0xc381, 0x43cc81, 0xc486, 0x45cc81, 0xc389, 0x47cc81, 0xc7b4,
		  0x49cc81, 0xc38d, 0x4ccc81, 0xc4b9, 0x4ecc81, 0xc583, 0x4fcc81, 0xc393,
		  0x52cc81, 0xc594, 0x53cc81, 0xc59a, 0x55cc81, 0xc39a, 0x59cc81, 0xc39d,
		  0x5acc81, 0xc5b9, 0x61cc81, 0xc3a1, 0x63cc81, 0xc487, 0x65cc81, 0xc3a9,
		  0x67cc81, 0xc7b5, 0x69cc81, 0xc3ad, 0x6ccc81, 0xc4ba, 0x6ecc81, 0xc584,
		  0x6fcc81, 0xc3b3, 0x72cc81, 0xc595, 0x73cc81, 0xc59b, 0x75cc81, 0xc3ba,
		  0x79cc81, 0xc3bd, 0x7acc81, 0xc5ba, 0x41cc82, 0xc382, 0x43cc82, 0xc488,
		  0x45cc82, 0xc38a, 0x47cc82, 0xc49c, 0x48cc82, 0xc4a4, 0x49cc82, 0xc38e,
		  0x4acc82, 0xc4b4, 0x4fcc82, 0xc394, 0x53cc82, 0xc59c, 0x55cc82, 0xc39b,
		  0x57cc82, 0xc5b4, 0x59cc82, 0xc5b6, 0x61cc82, 0xc3a2, 0x63cc82, 0xc489,
		  0x65cc82, 0xc3aa, 0x67cc82, 0xc49d, 0x68cc82, 0xc4a5, 0x69cc82, 0xc3ae,
		  0x6acc82, 0xc4b5, 0x6fcc82, 0xc3b4, 0x73cc82, 0xc59d, 0x75cc82, 0xc3bb,
		  0x77cc82, 0xc5b5, 0x79cc82, 0xc5b7, 0x41cc83, 0xc383, 0x49cc83, 0xc4a8,
		  0x4ecc83, 0xc391, 0x4fcc83, 0xc395, 0x55cc83, 0xc5a8, 0x61cc83, 0xc3a3,
		  0x69cc83, 0xc4a9, 0x6ecc83, 0xc3b1, 0x6fcc83, 0xc3b5, 0x75cc83, 0xc5a9,
		  0x41cc84, 0xc480, 0x45cc84, 0xc492, 0x49cc84, 0xc4aa, 0x4fcc84, 0xc58c,
		  0x55cc84, 0xc5aa, 0x59cc84, 0xc8b2, 0x61cc84, 0xc481, 0x65cc84, 0xc493,
		  0x69cc84, 0xc4ab, 0x6fcc84, 0xc58d, 0x75cc84, 0xc5ab, 0x79cc84, 0xc8b3,
		  0x41cc86, 0xc482, 0x45cc86, 0xc494, 0x47cc86, 0xc49e, 0x49cc86, 0xc4ac,
		  0x4fcc86, 0xc58e, 0x55cc86, 0xc5ac, 0x61cc86, 0xc483, 0x65cc86, 0xc495,
		  0x67cc86, 0xc49f, 0x69cc86, 0xc4ad, 0x6fcc86, 0xc58f, 0x75cc86, 0xc5ad,
		  0x41cc87, 0xc8a6, 0x43cc87, 0xc48a, 0x45cc87, 0xc496, 0x47cc87, 0xc4a0,
		  0x49cc87, 0xc4b0, 0x4fcc87, 0xc8ae, 0x5acc87, 0xc5bb, 0x61cc87, 0xc8a7,
		  0x63cc87, 0xc48b, 0x65cc87, 0xc497, 0x67cc87, 0xc4a1, 0x6fcc87, 0xc8af,
		  0x7acc87, 0xc5bc, 0x41cc88, 0xc384, 0x45cc88, 0xc38b, 0x49cc88, 0xc38f,
		  0x4fcc88, 0xc396, 0x55cc88, 0xc39c, 0x59cc88, 0xc5b8, 0x61cc88, 0xc3a4,
		  0x65cc88, 0xc3ab, 0x69cc88, 0xc3af, 0x6fcc88, 0xc3b6, 0x75cc88, 0xc3bc,
		  0x79cc88, 0xc3bf, 0x41cc8a, 0xc385, 0x55cc8a, 0xc5ae, 0x61cc8a, 0xc3a5,
		  0x75cc8a, 0xc5af, 0x4fcc8b, 0xc590, 0x55cc8b, 0xc5b0, 0x6fcc8b, 0xc591,
		  0x75cc8b, 0xc5b1, 0x41cc8c, 0xc78d, 0x43cc8c, 0xc48c, 0x44cc8c, 0xc48e,
		  0x45cc8c, 0xc49a, 0x47cc8c, 0xc7a6, 0x48cc8c, 0xc89e, 0x49cc8c, 0xc78f,
		  0x4bcc8c, 0xc7a8, 0x4ccc8c, 0xc4bd, 0x4ecc8c, 0xc587, 0x4fcc8c, 0xc791,
		  0x52cc8c, 0xc598, 0x53cc8c, 0xc5a0, 0x54cc8c, 0xc5a4, 0x55cc8c, 0xc793,
		  0x5acc8c, 0xc5bd, 0x61cc8c, 0xc78e, 0x63cc8c, 0xc48d, 0x64cc8c, 0xc48f,
		  0x65cc8c, 0xc49b, 0x67cc8c, 0xc7a7, 0x68cc8c, 0xc89f, 0x69cc8c, 0xc790,
		  0x6acc8c, 0xc7b0, 0x6bcc8c, 0xc7a9, 0x6ccc8c, 0xc4be, 0x6ecc8c, 0xc588,
		  0x6fcc8c, 0xc792, 0x72cc8c, 0xc599, 0x73cc8c, 0xc5a1, 0x74cc8c, 0xc5a5,
		  0x75cc8c, 0xc794, 0x7acc8c, 0xc5be, 0x41cc8f, 0xc880, 0x45cc8f, 0xc884,
		  0x49cc8f, 0xc888, 0x4fcc8f, 0xc88c, 0x52cc8f, 0xc890, 0x55cc8f, 0xc894,
		  0x61cc8f, 0xc881, 0x65cc8f, 0xc885, 0x69cc8f, 0xc889, 0x6fcc8f, 0xc88d,
		  0x72cc8f, 0xc891, 0x75cc8f, 0xc895, 0x41cc91, 0xc882, 0x45cc91, 0xc886,
		  0x49cc91, 0xc88a, 0x4fcc91, 0xc88e, 0x52cc91, 0xc892, 0x55cc91, 0xc896,
		  0x61cc91, 0xc883, 0x65cc91, 0xc887, 0x69cc91, 0xc88b, 0x6fcc91, 0xc88f,
		  0x72cc91, 0xc893, 0x75cc91, 0xc897, 0x4fcc9b, 0xc6a0, 0x55cc9b, 0xc6af,
		  0x6fcc9b, 0xc6a1, 0x75cc9b, 0xc6b0, 0x53cca6, 0xc898, 0x54cca6, 0xc89a,
		  0x73cca6, 0xc899, 0x74cca6, 0xc89b, 0x43cca7, 0xc387, 0x45cca7, 0xc8a8,
		  0x47cca7, 0xc4a2, 0x4bcca7, 0xc4b6, 0x4ccca7, 0xc4bb, 0x4ecca7, 0xc585,
		  0x52cca7, 0xc596, 0x53cca7, 0xc59e, 0x54cca7, 0xc5a2, 0x63cca7, 0xc3a7,
		  0x65cca7, 0xc8a9, 0x67cca7, 0xc4a3, 0x6bcca7, 0xc4b7, 0x6ccca7, 0xc4bc,
		  0x6ecca7, 0xc586, 0x72cca7, 0xc597, 0x73cca7, 0xc59f, 0x74cca7, 0xc5a3,
		  0x41cca8, 0xc484, 0x45cca8, 0xc498, 0x49cca8, 0xc4ae, 0x4fcca8, 0xc7aa,
		  0x55cca8, 0xc5b2, 0x61cca8, 0xc485, 0x65cca8, 0xc499, 0x69cca8, 0xc4af,
		  0x6fcca8, 0xc7ab, 0x75cca8, 0xc5b3, 0x41cd80, 0xc380, 0x45cd80, 0xc388,
		  0x49cd80, 0xc38c, 0x4ecd80, 0xc7b8, 0x4fcd80, 0xc392, 0x55cd80, 0xc399,
		  0x61cd80, 0xc3a0, 0x65cd80, 0xc3a8, 0x69cd80, 0xc3ac, 0x6ecd80, 0xc7b9,
		  0x6fcd80, 0xc3b2, 0x75cd80, 0xc3b9, 0x41cd81, 0xc381, 0x43cd81, 0xc486,
		  0x45cd81, 0xc389, 0x47cd81, 0xc7b4, 0x49cd81, 0xc38d, 0x4ccd81, 0xc4b9,
		  0x4ecd81, 0xc583, 0x4fcd81, 0xc393, 0x52cd81, 0xc594, 0x53cd81, 0xc59a,
		  0x55cd81, 0xc39a, 0x59cd81, 0xc39d, 0x5acd81, 0xc5b9, 0x61cd81, 0xc3a1,
		  0x63cd81, 0xc487, 0x65cd81, 0xc3a9, 0x67cd81, 0xc7b5, 0x69cd81, 0xc3ad,
		  0x6ccd81, 0xc4ba, 0x6ecd81, 0xc584, 0x6fcd81, 0xc3b3, 0x72cd81, 0xc595,
		  0x73cd81, 0xc59b, 0x75cd81, 0xc3ba, 0x79cd81, 0xc3bd, 0x7acd81, 0xc5ba,
		  0x55cd84, 0xc797, 0x75cd84, 0xc798, 0
		);
		
		$GLOBALS[ "NFD_NFC_tupels" ] = $NFD_NFC_tupels;
	}
	
	$newstr = "";
	
	for ($inx = 0; $inx < strlen($str) - 2; $inx++)
	{
	  $kar  = ord($str[ $inx + 0 ]);
	  $kar1 = ord($str[ $inx + 1 ]);
	  $kar2 = ord($str[ $inx + 2 ]);

	  if ((($kar1 == 0xcc) || ($kar1 == 0xcd)) && ($kar2 >= 0x80))
	  {
	    $nfd  = ($kar << 16) | ($kar1 << 8) | $kar2;
	    $skip = false;

	    for ($fnz = 0; $NFD_NFC_tupels[ $fnz ]; $fnz += 2)
	    {
	      if ($NFD_NFC_tupels[ $fnz ] == $nfd)
	      {
	        $newstr .= chr($NFD_NFC_tupels[ $fnz + 1 ] >> 8);
	        $newstr .= chr($NFD_NFC_tupels[ $fnz + 1 ] & 0xff);
	        $skip = true;
	        $inx += 2;
	        break;
	      }
	    }
	    
	    if ($skip) continue;
	  }
	  
	  $newstr .= chr($kar);
	}
	
	for ($inx = $inx; $inx < strlen($str); $inx++) $newstr .= $str[ $inx ];
	
	return $newstr;
}

function make_plain_ascii($str)
{
	if (! isset($GLOBALS[ "asciipat" ]))
	{
		$asciipat[  0 ] = '/[á|â|à|å|ã]/u';
		$asciipat[  1 ] = '/[ð|é|ê|è|ë]/u';
		$asciipat[  2 ] = '/[í|î|ì|ï]/u';
		$asciipat[  3 ] = '/[ó|ô|ò|ø|õ]/u';
		$asciipat[  4 ] = '/[ú|û|ù]/u';
		$asciipat[  5 ] = '/æ/u';
		$asciipat[  6 ] = '/[ç|č]/u';
		$asciipat[  7 ] = '/ß/u';
		$asciipat[  8 ] = '/ä/u';
		$asciipat[  9 ] = '/ö/u';
		$asciipat[ 10 ] = '/ü/u';
		$asciipat[ 11 ] = '/[ÿ|ý]/u';

		$asciipat[ 12 ] = '/[Æ|Á|À|Â|Å]/u';
		$asciipat[ 13 ] = '/[É|È|Ë|Ê]/u';
		$asciipat[ 14 ] = '/[Œ|Ó|Ò|Ô]/u';
		$asciipat[ 15 ] = '/[Ú|Ù|Û]/u';
		$asciipat[ 16 ] = '/[Í|Ì|Ï|Î]/u';
		$asciipat[ 17 ] = '/[Ý|Ÿ]/u';
		$asciipat[ 18 ] = '/[Ç]/u';
		$asciipat[ 19 ] = '/[¿]/u';
		$asciipat[ 20 ] = '/[ñ]/u';
		$asciipat[ 21 ] = '/[·]/u';
		
		$asciirep[  0 ] = 'a';
		$asciirep[  1 ] = 'e';
		$asciirep[  2 ] = 'i';
		$asciirep[  3 ] = 'o';
		$asciirep[  4 ] = 'u';
		$asciirep[  5 ] = 'ae';
		$asciirep[  6 ] = 'c';
		$asciirep[  7 ] = 'ss';
		$asciirep[  8 ] = 'ae';
		$asciirep[  9 ] = 'oe';
		$asciirep[ 10 ] = 'ue';
		$asciirep[ 11 ] = 'y';

		$asciirep[ 12 ] = 'A';
		$asciirep[ 13 ] = 'E';
		$asciirep[ 14 ] = 'O';
		$asciirep[ 15 ] = 'U';
		$asciirep[ 16 ] = 'I';
		$asciirep[ 17 ] = 'Y';
		$asciirep[ 18 ] = 'C';
		$asciirep[ 19 ] = '?';
		$asciirep[ 20 ] = 'n';
		$asciirep[ 21 ] = '-';

		//$pattern = array("éèëêÉÈËÊáàäâåÁÀÄÂÅóòöôÓÒÖÔíìïîÍÌÏÎúùüûÚÙÜÛýÿÝøØœŒÆçÇ");
		//$replace = array("eeeeEEEEaaaaaAAAAAooooOOOOiiiIIIIIuuuuUUUUyyYoOaAAcC"); 

		$GLOBALS[ "asciipat" ] = $asciipat;
		$GLOBALS[ "asciirep" ] = $asciirep;
	}
	
	$str = preg_replace($GLOBALS[ "asciipat" ],$GLOBALS[ "asciirep" ],$str);
	
	//
	// Look for single characters from other codes.
	//
	
	for ($inx = 0; $inx < strlen($str); $inx++)
	{
		if (ord($str[ $inx ]) == 146) $str[ $inx ] = "'";
		if (ord($str[ $inx ]) == 189) $str[ $inx ] = "e";
		
		if (ord($str[ $inx ]) >= 128) 
		{
			$str = substr($str,0,$inx) . substr($str,$inx + 1);
			$inx--;
		}
	}
	
	return $str;
}

function comp_artist($artist)
{
	if (! isset($GLOBALS[ "patterns" ]))
	{
		$patterns[  0 ] = '/[á|â|à|å]/u';
		$patterns[  1 ] = '/[ð|é|ê|è|ë]/u';
		$patterns[  2 ] = '/[í|î|ì|ï]/u';
		$patterns[  3 ] = '/[ó|ô|ò|ø|õ]/u';
		$patterns[  4 ] = '/[ú|û|ù]/u';
		$patterns[  5 ] = '/æ/u';
		$patterns[  6 ] = '/ç/u';
		$patterns[  7 ] = '/ß/u';
		$patterns[  8 ] = '/ä/u';
		$patterns[  9 ] = '/ö/u';
		$patterns[ 10 ] = '/ü/u';
		$patterns[ 11 ] = '/ÿ/u';
		
		$replaces[  0 ] = 'a';
		$replaces[  1 ] = 'e';
		$replaces[  2 ] = 'i';
		$replaces[  3 ] = 'o';
		$replaces[  4 ] = 'u';
		$replaces[  5 ] = 'ae';
		$replaces[  6 ] = 'c';
		$replaces[  7 ] = 'ss';
		$replaces[  8 ] = 'ae';
		$replaces[  9 ] = 'oe';
		$replaces[ 10 ] = 'ue';
		$replaces[ 11 ] = 'y';

		$GLOBALS[ "patterns" ] = $patterns;
		$GLOBALS[ "replaces" ] = $replaces;
	}
	
	$artist = utf8_nfd2nfc($artist);
	$artist = mb_convert_case(trim($artist),MB_CASE_LOWER,"UTF-8");
	
	if (substr($artist,0,4) == "the " ) $artist = substr($artist,4);
	if (substr($artist,-4)  == ",the" ) $artist = substr($artist,0,-4);
	if (substr($artist,-5)  == ", the") $artist = substr($artist,0,-5);
	
	$artist = preg_replace($GLOBALS[ "patterns" ],$GLOBALS[ "replaces" ],$artist);
	$artist = preg_replace("/\([0-9]*\)/u","",$artist);
	$artist = str_replace("\"","",$artist);
	$artist = str_replace("`","'",$artist);
		
	$artist = str_replace("+"," and ",$artist);
	$artist = str_replace("&"," and ",$artist);
	$artist = str_replace(";"," and ",$artist);
	
	$artist = str_replace(" featuring "," and ",$artist);
	$artist = str_replace(" feat. "," and ",$artist);
	$artist = str_replace(" feat "," and ",$artist);
	$artist = str_replace(" und "," and ",$artist);
	$artist = str_replace(" mit "," and ",$artist);
	$artist = str_replace(" vs. "," and ",$artist);
	$artist = str_replace(" ft. "," and ",$artist);
	$artist = str_replace(" ft "," and ",$artist);
	$artist = str_replace(" vs "," and ",$artist);

	$artist = str_replace("   "," ",$artist);
	$artist = str_replace("  " ," ",$artist);
	$artist = str_replace("  " ," ",$artist);
	$artist = str_replace("  " ," ",$artist);

	$artist = trim($artist);
	
	return $artist;
}

function comp_title($title)
{
	$title = utf8_nfd2nfc($title);
	$title = mb_convert_case(trim($title),MB_CASE_LOWER,"UTF-8");

	$title = preg_replace($GLOBALS[ "patterns" ],$GLOBALS[ "replaces" ],$title);
	$title = str_replace("\"","",$title);
	$title = str_replace("'","",$title);

	$title = str_replace("   "," ",$title);
	$title = str_replace("  " ," ",$title);

	$title = trim($title);
	
	return $title;
}

function get_directory($what)
{
	$entries = Array();
	
	$dd = opendir($what);
	
	while (($file = readdir($dd)) !== false)
	{
		if ($file == ".") continue;
		if ($file == "..") continue;
		
		array_push($entries,$file);
	}
	
	closedir($dd);
	
	return $entries;
}

?>