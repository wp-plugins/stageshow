<?php
/* 
Description: Generic HTTP Functions
 
Copyright 2014 Malcolm Shergold

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

if (!class_exists('StageShowLibHTTPIO')) 
{
	class StageShowLibHTTPIO // Define class
	{

		static function GetRequestedInt($paramId, $defaultVal = '', $exitOnError = true)
		{
			if (!isset($_REQUEST[$paramId]))
				return $defaultVal;
			
			$rtnVal = $_REQUEST[$paramId];	
			if (!is_numeric($rtnVal))
			{
				if ($exitOnError)
					die("Program Terminated - Invalid POST");
				else
					return $defaultVal;
			}
			
			return $rtnVal;
		}
		
		static function GetRequestedString($paramId, $defaultVal = '')
		{
			if (!isset($_REQUEST[$paramId]))
				return $defaultVal;
			
			return $_REQUEST[$paramId];
		}
		
	}
}

?>