<?php

include "../php/json.php";
include "../php/util.php";

	$auth = base64_encode('pupso:4pupso');
	$user = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko)";
	$prox = "max-age=0, must-revalidate, proxy-revalidate";
	$prox = "no-cache";
	
	$options_pix = array
	(
  		'http' => array
  		(
    		'method' => "GET",
    		'header' => "User-Agent: $user\r\n",
        	'header' => "Referrer: http://www.discogs.com/\r\n",  
			'request_fulluri' => true,
			'end' => true  	
    	)
	);
	
	$options_prx = array
	(
  		'http' => array
  		(
    		'method' => "GET",
    		'header' => "User-Agent: $user\r\n",
        	'header' => "Referrer: http://www.discogs.com/\r\n",  
        	'header' => "Proxy-Authorization: Basic $auth\r\n", 
        	'proxy'  => "tcp://dezi-office.no-ip.org:9128",
			'request_fulluri' => true,
			'end' => true  	
    	)
	);
	
	$options_adv = array
	(
  		'http' => array
  		(
    		'method' => "GET",
    		'header' => "User-Agent: $user\r\n",
        	'header' => "Referrer: http://www.discogs.com/\r\n",  
        	'proxy'  => "tcp://10.1.1.1:8080",
			'request_fulluri' => true,
			'end' => true  	
    	)
	);
	
	$options_api = array
	(
  		'http' => array
  		(
    		'method' => "GET",
    		'header' => "User-Agent: KappaRadioPlaylistTracker/0.1 (dezi@kappa-mm.de) +http://www.kappa-mm.de/\r\n",
        	'header' => "Proxy-Authorization: Basic $auth\r\n", 
        	'proxy'  => "tcp://dezi-office.no-ip.org:9128",
			'request_fulluri' => true,
			'end' => true  	
    	)
	);
	
	$context_pix = stream_context_create($options_pix);
	$context_prx = stream_context_create($options_prx);
	$context_adv = stream_context_create($options_adv);
	$context_api = stream_context_create($options_api);
	
	$workdir  = "../var/cached"; 
	$doimages = "../var/doimages"; 
	
	/*
	$dd = opendir("../lib/images");
	
	while (($file = readdir($dd)) !== false)
	{
		if ($file == ".") continue;
		if ($file == "..") continue;
		if ($file[ 0 ] != "R") continue;
		
		find_image($file,$dummy);
		echo "$dummy\n";
	}
	
	closedir($dd);
	exit(0);
	*/
	
	while (true)
	{
		sleep(1);
		
		$library = get_directory($doimages);
	
		$mincovr = 2;
		$quotaex = false;
	
		foreach ($library as $track)
		{
			$trackfile = "$workdir/$track";
			$linksfile = "$doimages/$track";
		
			$cont = title_get_contents($trackfile);
			$json = json_decdat($cont);
		
			if (($json === false) || ! is_array($json))
			{
				echo "Corrupt: $trackfile\n";
				exit(0); 
			}
		
			if (! isset($json[ "discogs" ])) 
			{
				@unlink($linksfile);
				
				continue;
			}
			
			$coversloaded  = 0;
			$coversmissing = 0;
		
			foreach ($json[ "discogs" ] as $index => $item)
			{
				if (! isset($item[ "cover" ])) continue;
			
				if (! find_image($item[ "cover" ],$imagefile))
				{ 
					$coversmissing++;
		 		}
		 		
				$coversloaded++;
			}
		
			if (($coversmissing == 0) && ($coversloaded >= $mincovr)) 
			{
				@unlink($linksfile);
				
				continue;
			}
		
			$dirty = false;
			$check = Array();
			
			foreach ($json[ "discogs" ] as $index => $item)
			{
				if (isset($item[ "cover" ])) 
				{
					if (find_image($item[ "cover" ],$imagefile))
					{
						continue;
					}
				}
			
				if (isset($item[ "covertest" ])) continue;
			
				if (isset($item[ "coverload" ]))
				{
					$loadme = Array();
			
					$loadme[ "index"   ] = $index;
					$loadme[ "uri150"  ] = $item[ "coverload" ];
					$loadme[ "release" ] = $release;
			
					array_push($check,$loadme);
				}
			
				if (! isset($item[ "title" ])) continue;
				if (! isset($item[ "release" ])) continue;
		
				$release = $item[ "release" ];	
				$archive = "../lib/releases/" . substr($release,0,8) . "xx.json";

				if (! file_exists($archive)) 
				{
					echo "$release => Archive missing....\n";
					exit();
				}
			
				$packcont = file_get_contents($archive);
				$packjson = json_decdat($packcont);
			
				if (! $packjson) 
				{
					echo "$release => $archive Archive does not load....\n";
					continue;
				}
			
				if (! isset($packjson[ $release ]))
				{
					echo "$release => Archive no release....\n";
					var_dump($packjson);
					exit();
				}
			
				if (! isset($packjson[ $release ][ "images" ]))
				{
					//echo "$release (NOCO) " . $item[ "title" ] . "\n";
				
					$json[ "discogs" ][ $index ][ "covertest" ] = time();
					$dirty = true;
				
					continue;
				}

				if (isset($packjson[ $release ][ "images" ][ "image" ]))
				{
					//
					// From XML download.
					//
					
					$images = $packjson[ $release ][ "images" ][ "image" ];
				}
				else
				if (isset($packjson[ $release ][ "images" ][ 0 ]))
				{
					//
					// From JSON download.
					//
					
					$images = $packjson[ $release ][ "images" ];
				}
				else
				{
					echo "$release => Archive no image....\n";
					exit();
				}
			
				if (isset($images[ "@attributes" ]))
				{
					$dummy = Array();
					array_push($dummy,$images);
					$images = $dummy;
				}
			
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
			
				if (! $image) continue;
			
				if (isset($image[ "@attributes" ][ "uri150" ]))
				{
					$uri150 = $image[ "@attributes" ][ "uri150" ];
				}
				else
				if (isset($image[ "uri150" ]))
				{
					$uri150 = $image[ "uri150" ];
				}
				else
				{
					echo "$release => Images no uri150....\n";
					exit();
				}
			
				$loadme = Array();
			
				$loadme[ "index"   ] = $index;
				$loadme[ "uri150"  ] = $uri150;
				$loadme[ "release" ] = $release;
			
				array_push($check,$loadme);

				$json[ "discogs" ][ $index ][ "coverload" ] = $uri150;
				$dirty = true;
			}
		
			//
			// Phase one: check already present images.
			//
		
			$maxload = $mincovr;
		
			$pixload = Array();
			$apiload = Array();
		
			foreach ($check as $loadme)
			{
				$index   = $loadme[ "index"   ];
				$uri150  = $loadme[ "uri150"  ];
				$release = $loadme[ "release" ];
			
				//echo "$release (CHCK) " . $json[ "discogs" ][ $index ][ "title" ] . "\n";
			
				$imagename = explode("/",$uri150);
				$imagename = array_pop($imagename);
			
				if (find_image($imagename,$imagefile))
				{				
					unset($json[ "discogs" ][ $index ][ "coverload" ]);				
					$json[ "discogs" ][ $index ][ "cover" ] = $imagename;
					$dirty = true;
					$maxload--;

					echo "+$uri150\n";
				}
				else
				{
					array_push($pixload,$loadme);
				}
			}
		
			//
			// Phase two: load some images from edge server.
			//	
		
			$ogsload = 4;
		
			while (($ogsload > 0) && count($pixload))
			{
				$loadme = array_shift($pixload);

				$index   = $loadme[ "index"   ];
				$uri150  = $loadme[ "uri150"  ];
				$release = $loadme[ "release" ];
			
				echo "$release (PIXO) " . $json[ "discogs" ][ $index ][ "title" ] . "\n";

				$uri150 = str_replace("api.discogs.com","s.pixogs.com",$uri150);
			
				$imagename = explode("/",$uri150);
				$imagename = array_pop($imagename);

				find_image($imagename,$imagefile);
				
				$jpeg = @file_get_contents($uri150,false,$GLOBALS[ "context_pix" ]);
			
				foreach ($http_response_header as $one)
				{
					if (substr($one,0,7) == "Server:")
					{
						$ok = (($jpeg === false) || (strlen($jpeg) < 1000)) ? "-" : "+";
					
						echo "==================================================> $ok $one\n";
					}
				}
			
				if (($jpeg === false) || (strlen($jpeg) < 1000))
				{
					$jpeg = @file_get_contents($uri150,false,$GLOBALS[ "context_prx" ]);
			
					foreach ($http_response_header as $one)
					{
						if (substr($one,0,7) == "Server:")
						{
							$ok = (($jpeg === false) || (strlen($jpeg) < 1000)) ? "-" : "+";
					
							echo "==================================================> $ok $one\n";
						}
					}
				}
	
				if (($jpeg === false) || (strlen($jpeg) < 1000))
				{
					array_push($apiload,$loadme);
				
					continue;
				}
			
				file_put_contents($imagefile,$jpeg);

				unset($json[ "discogs" ][ $index ][ "coverload" ]);
				$json[ "discogs" ][ $index ][ "cover" ] = $imagename;
				$dirty = true;
				$maxload--;
				$ogsload--;

				echo "*$uri150\n";
			}
		
			//
			// Phase three: load from api server.
			//	
		
			while (($maxload > 0) && count($apiload) && ! $quotaex)
			{
				$loadme = array_shift($apiload);

				$index   = $loadme[ "index"   ];
				$uri150  = $loadme[ "uri150"  ];
				$release = $loadme[ "release" ];
			
				echo chr(7) . "$release (LOAD) " . $json[ "discogs" ][ $index ][ "title" ] . "\n";
			
				$imagename = explode("/",$uri150);
				$imagename = array_pop($imagename);
				
				find_image($imagename,$imagefile);

				$jpeg = @file_get_contents($uri150,false,$GLOBALS[ "context_api" ]);
			
				if (($jpeg === false) || (strlen($jpeg) < 1000)) 
				{
					if ($http_response_header[ 0 ] == "HTTP/1.0 404 Not Found")
					{
						unset($json[ "discogs" ][ $index ][ "cover" ]);
						$json[ "discogs" ][ $index ][ "covertest" ] = time();
						$dirty = true;

						continue;
					}
				
					$quotaex = true;
					echo "$release => Quota exceeded....\n";
					break;
				
					var_dump($http_response_header);
					exit(0);
				}
				
				$remain = 0;
				$reset  = 0;
				
				foreach ($http_response_header as $header)
				{
					if (substr($header,0,22) == "X-RateLimit-Remaining:")
					{
						$remain = intval(substr($header,22));
					}
					if (substr($header,0,18) == "X-RateLimit-Reset:")
					{
						$reset = intval(substr($header,22));
					}
				}
				
				echo "Remaining=$remain Reset=$reset\n";
				
				file_put_contents($imagefile,$jpeg);
			
				unset($json[ "discogs" ][ $index ][ "coverload" ]);				
				$json[ "discogs" ][ $index ][ "cover" ] = $imagename;
				$dirty = true;
				$maxload--;

				echo "-$uri150\n";
			}
		
			if ($dirty)
			{
				title_put_contents($trackfile,json_encdat($json) . "\n");
				echo "Wrote => $trackfile\n";
			}
			
			if (! $quotaex)
			{
				@unlink($linksfile);
			}
		}
	}
?>