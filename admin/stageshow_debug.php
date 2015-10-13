<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
include STAGESHOW_INCLUDE_PATH.'stageshowlib_debug.php';      

if (!class_exists('StageShowWPOrgDebugAdminClass')) 
{
	class StageShowWPOrgDebugAdminClass extends StageShowLibDebugSettingsClass // Define class
	{
		static function GetOptionsDefs($inherit = true)
		{
			$ourOptions = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Trolley',            StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowTrolley',        StageShowLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowDBOutput'),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Diagnostic Alert EMails', StageShowLibTableClass::TABLEPARAM_ID => 'Dev_GatewayEMailAlerts', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT),
			);
			
			if ($inherit)
			{			
				$childOptions = parent::GetOptionsDefs();			
				$ourOptions = StageShowLibAdminListClass::MergeSettings($childOptions, $ourOptions);
			}
			
			return $ourOptions;
		}
		
		function GetOptionsDescription($optionName)
		{
			switch ($optionName)
			{
				case 'Show Trolley':	return 'Dump Trolley Variables to Screen';				
				default:				return parent::GetOptionsDescription($optionName);					
			}
		}
		
	}
}
		
?>