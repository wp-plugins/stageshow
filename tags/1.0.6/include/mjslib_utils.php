<?php
/* 
Description: General Utilities Code
 
Copyright 2012 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if (!class_exists('MJSLibUtilsClass')) 
{
	class MJSLibUtilsClass // Define class
	{
    static function GetHTTPElement($reqArray, $elementId) 
		{
			// HTTP escapes ', " and / 
			// This function will return the array element with escape sequences removed
			return stripslashes(self::GetArrayElement($reqArray, $elementId));
    }
    
    static function GetArrayElement($reqArray, $elementId, $defaultValue = '') 
    {
	    // Get an element from the array ... if it exists
	    if (!is_array($reqArray)) 
				return $defaultValue;
	    if (!array_key_exists($elementId, $reqArray)) 
				return $defaultValue;	
	    return $reqArray[$elementId];
    }
    
		static function isNewVersion($ourVersion, $serverVersion, $debug=false) 
		{
			// Compare version numbers - format N1.N2.N3 .... etc.
			$ourVersionVals = split('\.', $ourVersion);
			$serverVersionVals = split('\.', $serverVersion);
					
			if ($debug) echo "Compare Versions ($ourVersion , $serverVersion)<br>\n";					
			
			for ($i=0; $i<max(count($ourVersionVals),count($serverVersionVals)); $i++)
			{
				$ourVersionVal = isset($ourVersionVals[$i]) ? (int)$ourVersionVals[$i] : 0;
				$serverVersionVal = isset($serverVersionVals[$i]) ? (int)$serverVersionVals[$i] : 0;
				
				if ($serverVersionVal > $ourVersionVal)
				{
					if ($debug) echo "serverVersionVal > ourVersionVal ($serverVersionVal > $ourVersionVal)- Exit TRUE<br>\n";					
					return true;
				}
				if ($serverVersionVal < $ourVersionVal)
				{
					if ($debug) echo "serverVersionVal < ourVersionVal ($serverVersionVal < $ourVersionVal) - Exit FALSE<br>\n";					
					return false;
				}
					
				if ($debug) echo "serverVersionVal = ourVersionVal ($serverVersionVal = $ourVersionVal) - Continue<br>\n";					
			}
			
			if ($debug) echo "serverVersionVal = ourVersionVal ($ourVersion = $serverVersion) - Exit FALSE<br>\n";					
			return false;
		}

		static function ShowCallStack($echoOut = true)
		{
			$lineBreak = $echoOut ? "<br>\n" : "\n";
			
			ob_start();		
			debug_print_backtrace();			
			$callStack = ob_get_contents();
			ob_end_clean();

			$callStack = explode('#', $callStack);
			$showEntry = false;
			
			ob_start();		
			echo $lineBreak."Callstack:".$lineBreak;
			
			foreach ($callStack as $fncall)
			{
				$fields = explode(' called at ', $fncall);

				// Separate Function Parameters
				$params = explode('(', $fields[0]);
				
				if (!$showEntry)
				{
					if (strpos($fncall,'::ShowCallStack('))
						$showEntry = true;
					continue;
				}
					
				echo $params[0].'()';
				
				if (count($fields) > 1)
				{
					$fileName = basename(str_replace('[', '', str_replace(']', '', $fields[1])));
					echo ' - '.$fileName;
				}
								
				//echo $lineBreak;
			}
			
			$rtnVal = ob_get_contents();
			ob_end_clean();
			
			$rtnVal = str_replace("\n","<br>\n",$rtnVal);
			
			if ($echoOut) echo $rtnVal;
			
			return $rtnVal;
		}		
		
		static function print_r($obj, $name='', $return = false)
		{
			$rtnVal = "<br>";
			if ($name !== '') $rtnVal .= "$name<br>\n";
			$rtnVal .= print_r($obj, true);
			$rtnVal .= "<br>\n";
			
			if (!$return) echo $rtnVal;
			
			return $rtnVal;
		}
		
		static function print_r_nocontent($obj, $name='')
		{
			echo "<br>";
			if ($name !== '') echo "$name<br>\n";
			foreach ($obj as $key => $value)
			{
				echo "object->$key";
				self::print_element($value);
				echo "<br>\n";
			}
			echo "<br>\n";
		}
		
		static function print_element($obj, $spaces = '')
		{
			$spaces .= '..';
			if (!is_array($obj))
			{
				echo "=$obj";
				return;
			}
			echo " <strong>(Array)</strong>";
			
			foreach ($obj as $key => $value)
			{
				echo "\n<br>".$spaces.'['.$key.'] => '."\n";
				//self::print_element($value, $spaces.'..');
			}
		}
		
		static function DeleteFile($filePath)
		{
			if (!file_exists($filePath))
				return;
				
			try 
			{
				//throw exception if can't move the file
        chmod($filePath, 0666);
        unlink($filePath);
			} 
			catch (Exception $e) 
			{
			}
		}
	}
}

?>