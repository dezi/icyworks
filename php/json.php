<?php

function json_encrec($data,$level = 0) 
{
	$pad = str_pad("\n",$level * 2 + 1,' ');
	
	switch ($type = gettype($data)) 
	{
		case 'NULL':
			return 'null';
			
		case 'boolean':
			return ($data ? 'true' : 'false');
			
		case 'integer':
		case 'double':
		case 'float':
			return $data;
			
		case 'string':
			$str = addslashes($data);
			$str = str_replace("\r","\\r",$str);
			$str = str_replace("\n","\\n",$str);
			$str = str_replace("\t","\\t",$str);
			$str = str_replace("\\'","'" ,$str);
			return '"' . $str . '"';
			
		case 'object':
			$data = get_object_vars($data);
			
		case 'array':
			$output_isarray = true;
			
			foreach ($data as $key => $value) 
			{
				if (gettype($key) == 'integer') continue;
				
				$output_isarray = false;
				break;
			}
			
			if ($output_isarray) 
			{
				$output_intkeys = array();

				foreach ($data as $key => $value) 
				{
					$output_intkeys[] = json_encrec($value,$level + 1);
				}
				
				return "$pad" . "[" . "$pad  " . implode(",$pad  ",$output_intkeys) . "$pad]";
			}
			else
			{
				$output_txtkeys = array();
				
				foreach ($data as $key => $value) 
				{
					$output_txtkeys[] 
						= json_encrec($key,$level + 1) 
						. ':' 
						. json_encrec($value,$level + 1)
						;
				}
			
				return "$pad" . "{" . "$pad  " . implode(",$pad  ",$output_txtkeys) . "$pad}";
			}
			
			return '';
				
		default:
			return '';
	}
}

function json_encdat($data,$level = 0)
{
	return trim(json_encrec($data,$level));
} 

function json_decdat($data)
{
	$GLOBALS[ "json_wasfucked" ] = false;
	
	$data = str_replace("\\'","'",$data);
	$data = str_replace("\t" ," ",$data);

	$result = json_decode($data,true);
	
	if (! $result)
	{
		//
		// Work around for fucked up jsons.
		//
		
		$data = str_replace(chr( 1)," ",$data);
		$data = str_replace(chr( 2)," ",$data);
		$data = str_replace(chr( 3)," ",$data);
		$data = str_replace(chr( 4)," ",$data);
		$data = str_replace(chr( 5)," ",$data);
		$data = str_replace(chr( 6)," ",$data);
		$data = str_replace(chr( 7)," ",$data);
		$data = str_replace(chr( 8)," ",$data);
		$data = str_replace(chr(11)," ",$data);
		$data = str_replace(chr(15)," ",$data);
		$data = str_replace(chr(19)," ",$data);
		$data = str_replace(chr(21)," ",$data);
		$data = str_replace(chr(25)," ",$data);
		$data = str_replace(chr(26)," ",$data);
		$data = str_replace(chr(27)," ",$data);
		$data = str_replace(chr(29)," ",$data);
		
		$data = preg_replace('/^([ ]+)([0-9]+):$/m','${1}"${2}":',$data);		
		$result = json_decode($data,true);

		if ($result) $GLOBALS[ "json_wasfucked" ] = true;
	}
	
	return $result;
}

function json_nukeempty(&$data)
{
	if (gettype($data) == 'object')
	{
		$data = get_object_vars($data);
	}
	
	if (! is_array($data)) return;
	
	foreach ($data as $key => $dummy)
	{
		if (gettype($data[ $key ]) == 'string')
		{
			if ($dummy === "") unset($data[ $key ]);
			
			continue;
		}
		
		if (gettype($data[ $key ]) == 'object')
		{
			$data[ $key ] = get_object_vars($data[ $key ]);
		}
		
		if (! is_array($data[ $key ])) continue;
		
		if (count($data[ $key ])  > 0) json_nukeempty($data[ $key ]);		
		if (count($data[ $key ]) == 0) unset($data[ $key ]);
	}
} 

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

?>
