<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
include STAGESHOW_INCLUDE_PATH.'stageshowlib_debug.php';      

if (!class_exists('StageShowDebugAdminClass')) 
{
	class StageShowDebugAdminClass extends StageShowLibDebugSettingsClass // Define class
	{
		function GetOptionsDefs()
		{
			$testOptionDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Trolley',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowTrolley',     StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowTrolley', ),
			);
			
			$childOptions = parent::GetOptionsDefs();
			
			$ourOptions = array_merge($childOptions, $testOptionDefs);
			
			return $ourOptions;
		}
		
	}
}
		
?>