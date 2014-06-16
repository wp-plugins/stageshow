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

include dirname(dirname(__FILE__)).'/include/stageshowlib_table.php';

if (!class_exists('StageShowLibDebugSettingsClass')) 
{
	class StageShowLibDebugSettingsClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'TEST Settings';
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$this->submitButtonID = $myDBaseObj->get_name()."_testsettings";
			
			// TEST Settings HTML Output - Start 			
			echo '<form method="post">'."\n";
			$this->WPNonceField();
			
			$this->Test_DebugSettings(); 

			echo '</form>'."\n";
			// TEST HTML Output - End
		}
		
		function GetOptionsDefs()
		{
			$testOptionDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show SQL',          StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowSQL',          StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowSQL', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show DB Output',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowDBOutput',     StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowDBOutput', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show PayPal IO',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowPayPalIO',     StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowPayPalIO', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show EMail Msgs',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowEMailMsgs',    StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowEMailMsgs', ),
				
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Log IPN Requests',  StageShowLibTableClass::TABLEPARAM_NAME => 'cbLogIPNRequests',   StageShowLibTableClass::TABLEPARAM_OPTION => 'Dev_IPNLogRequests', ),
			);
			
			return $testOptionDefs;
		}
		
		function Test_DebugSettings() 
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_POST['testbutton_SaveDebugSettings'])) 
			{
				$this->CheckAdminReferer();
					
				$optDefs = $this->GetOptionsDefs();
				foreach ($optDefs as $optDef)
				{
					$label = $optDef[StageShowLibTableClass::TABLEPARAM_LABEL];
					if ($label === '') continue;
					
					$ctrlId = $optDef[StageShowLibTableClass::TABLEPARAM_NAME];
					$settingId = $optDef[StageShowLibTableClass::TABLEPARAM_OPTION];
					
					$myDBaseObj->adminOptions[$settingId] = trim(StageShowLibUtilsClass::GetHTTPElement($_POST,$ctrlId));
				}
					
				$myDBaseObj->saveOptions();
				
				echo '<div id="message" class="updated"><p>Debug options updated</p></div>';
			}
?>
		<h3>Debug Settings</h3>
		<table class="form-table">
<?php	
		$optDefs = $this->GetOptionsDefs();
		$count = 0;

		foreach ($optDefs as $optDef)
		{
			$label = $optDef[StageShowLibTableClass::TABLEPARAM_LABEL];
			
			if ($count == 0) echo '<tr valign="top">'."\n";
			if ($label !== '')
			{
				$ctrlId = $optDef[StageShowLibTableClass::TABLEPARAM_NAME];
				$settingId = $optDef[StageShowLibTableClass::TABLEPARAM_OPTION];
				$optIsChecked = StageShowLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,$settingId) == 1 ? 'checked="yes" ' : '';
				echo '<td align="left" width="25%">'.$label.'&nbsp;<input name="'.$ctrlId.'" type="checkbox" value="1" '.$optIsChecked.' /></td>'."\n";
			}
			else
				echo '<td align="left">&nbsp;</td>'."\n";
			$count++;
			if ($count == 4) 
			{
				echo '</tr>'."\n";
				$count = 0;
			}
		}

?>			
			<tr valign="top" colspan="4">
				<td>
					<input class="button-primary" type="submit" name="testbutton_SaveDebugSettings" value="Save Debug Settings"/>
				</td>
			</tr>
		</table>
	<br>
<?php
		}
		
	}
}

?>