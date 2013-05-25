<?php
/* 
Description: Code for Managing Show Configuration
 
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

include STAGESHOW_ADMIN_PATH.'stageshow_manage_shows.php';

if (!class_exists('StageShowPlusShowsAdminListClass')) 
{
	class StageShowPlusShowsAdminListClass extends StageShowShowsAdminListClass // Define class
	{				
		function __construct($env) //constructor
		{
			$this->hiddenRowsButtonId = 'TBD';		
				
			// Call base constructor
			parent::__construct($env);
			
			$this->hiddenRowsButtonId = __('Options', $env['Domain']);		
				
		}
		
		function GetDetailsRowsDefinition()
		{
			// FUNCTIONALITY: Shows - StageShow+ - Add Note to Show Options
			$ourOptions = array(
				array(self::TABLEPARAM_LABEL => 'Note',						self::TABLEPARAM_ID => 'showNote',  self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXTBOX, self::TABLEPARAM_ROWS => 4, self::TABLEPARAM_COLS => 60, ),
				//array(self::TABLEPARAM_LABEL => 'EMail Template',	self::TABLEPARAM_ID => 'showEMail', self::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,		self::TABLEPARAM_LEN => STAGESHOW_FILEPATH_TEXTLEN, self::TABLEPARAM_SIZE => STAGESHOW_FILEPATH_EDITLEN, ),
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function ExtendedSettingsDBOpts()
		{
			$dbOpts['Table'] = STAGESHOW_SHOWS_TABLE;
			$dbOpts['Index'] = 'showID';
			
			return $dbOpts;
		}
		
	}
}

if (!class_exists('StageShowPlusShowsAdminClass') && class_exists('StageShowShowsAdminClass')) 
{
	class StageShowPlusShowsAdminClass extends StageShowShowsAdminClass // Define class
	{
		function GetAdminListClass()
		{
			return 'StageShowPlusShowsAdminListClass';			
		}
		
	}
}

?>