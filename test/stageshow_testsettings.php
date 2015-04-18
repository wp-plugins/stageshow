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
	
include STAGESHOW_TEST_PATH.'stageshowlib_testsettings.php';  

if (!class_exists('StageShowWPOrgTestSettingsAdminClass')) 
{
	class StageShowWPOrgTestSettingsAdminClass extends StageShowLibTestSettingsClass // Define class
	{
		function __construct($env) //constructor	
		{	
			if (file_exists(STAGESHOW_TEST_PATH.'stageshow_test_devtests.php'))
			{
				include 'stageshow_test_devtests.php';
				$this->devtests = new StageShow_Test_devtests($env);							
			}

			// Call base constructor
			parent::__construct($env);
		}
		
		function Output_MainPage($updateFailed)
		{
			parent::Output_MainPage($updateFailed);
			
			if (!isset($this->devtests)) return;
			
			// Stage Show TEST Settings HTML Output - Start 			
			echo '<form method="post">'."\n";
			$this->WPNonceField();
			
			echo '<h3>Database Tests</h3>'."\n";
			$this->devtests->Test_PurgeDB();				
			$this->devtests->Test_NewSampleDatabase();

			echo '</form>'."\n";
		}
		
		function GetOptionsDefs()
		{
			$testOptionDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Trolley',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowTrolley',     StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowTrolley', ),
			);
			
			$childOptions = parent::GetOptionsDefs();
			
			$ourOptions = StageShowLibAdminListClass::MergeSettings($childOptions, $testOptionDefs);
			
			return $ourOptions;
		}
		
		function GetOptionsDescription($optionName)
		{
			switch ($optionName)
			{
				//case 'Show Trolley': return 'TBD';
				
				default:	
					return parent::GetOptionsDescription($optionName);					
			}
		}
		
	}
}

?>