<?php

function json_defuck($file)
{
	$fd = fopen($file,"r");

	while (($line = fgets($fd)) != null)
	{
		$line = trim($line);
	
		for ($inx = 0; $inx < strlen($line); $inx++)
		{
			if (ord($line[ $inx ]) < 0x20) 
			{
				$spad = str_pad("^",$inx - 1," ",STR_PAD_LEFT);
			
				echo "===> $line\n";
				echo "---> $spad\n";
			
				echo ord($line[ $inx ]) . "\n";
			}
		}
	}
	
	exit(0);
}

json_defuck("xxxx.txt")
?>
