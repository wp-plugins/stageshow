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

include dirname(dirname(__FILE__)).'\include\mjslib_table.php';

if (!class_exists('MJSLibDebugSettingsClass')) 
{
	class MJSLibDebugSettingsClass extends MJSLibAdminClass // Define class
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
			
			// Stage Show TEST Settings HTML Output - Start 			
			echo '<form method="post">'."\n";
			$this->WPNonceField();
			
			$this->Test_DebugSettings(); 

			echo '</form>'."\n";
			// Stage Show TEST HTML Output - End
		}
		
		function GetOptionsDefs()
		{
			$testOptionDefs = array(
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Show SQL',          MJSLibTableClass::TABLEPARAM_NAME => 'cbShowSQL',          MJSLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowSQL', ),
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Show DB Output',    MJSLibTableClass::TABLEPARAM_NAME => 'cbShowDBOutput',     MJSLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowDBOutput', ),
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Show PayPal IO',    MJSLibTableClass::TABLEPARAM_NAME => 'cbShowPayPalIO',     MJSLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowPayPalIO', ),
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Show EMail Msgs',   MJSLibTableClass::TABLEPARAM_NAME => 'cbShowEMailMsgs',    MJSLibTableClass::TABLEPARAM_OPTION => 'Dev_ShowEMailMsgs', ),
				
				array(MJSLibTableClass::TABLEPARAM_LABEL => 'Log IPN Requests',  MJSLibTableClass::TABLEPARAM_NAME => 'cbLogIPNRequests',   MJSLibTableClass::TABLEPARAM_OPTION => 'Dev_IPNLogRequests', ),
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
					$label = $optDef[MJSLibTableClass::TABLEPARAM_LABEL];
					if ($label === '') continue;
					
					$ctrlId = $optDef[MJSLibTableClass::TABLEPARAM_NAME];
					$settingId = $optDef[MJSLibTableClass::TABLEPARAM_OPTION];
					
					$myDBaseObj->adminOptions[$settingId] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,$ctrlId));
				}
					
				$myDBaseObj->saveOptions();
			}
?>
		<h3>Debug Settings</h3>
		<table class="form-table">
<?php	
		$optDefs = $this->GetOptionsDefs();
		$count = 0;

		foreach ($optDefs as $optDef)
		{
			$label = $optDef[MJSLibTableClass::TABLEPARAM_LABEL];
			
			if ($count == 0) echo '<tr valign="top">'."\n";
			if ($label !== '')
			{
				$ctrlId = $optDef[MJSLibTableClass::TABLEPARAM_NAME];
				$settingId = $optDef[MJSLibTableClass::TABLEPARAM_OPTION];
				$optIsChecked = MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,$settingId) == 1 ? 'checked="yes" ' : '';
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