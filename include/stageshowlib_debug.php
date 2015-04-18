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

include dirname(dirname(__FILE__)).'/include/stageshowlib_admin.php';
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
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show $_GET',        StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowGET',          StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowGET', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show $_POST',       StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowPOST',         StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowPOST', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show $_SESSION',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowSESSION',      StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowSESSION', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show EMail Msgs',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowEMailMsgs',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowEMailMsgs', ),				
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show SQL',          StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowSQL',          StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowSQL', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show DB Output',    StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowDBOutput',     StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowDBOutput', ),
			);
			
			return $testOptionDefs;
		}
		
		function GetOptionsDescription($optionName)
		{
			switch ($optionName)
			{
				case 'Show SQL':		return 'Show SQL Query Strings';
				case 'Show DB Output':	return 'Show SQL Query Output';
				case 'Show EMail Msgs': return 'Output EMail Message Content to Screen';
				
				default:	
					return "No Description Available for $optionName";					
			}
		}
		
		function Test_DebugSettings() 
		{
			$doneCheckboxes = false;
			
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
					$settingId = $optDef[StageShowLibTableClass::TABLEPARAM_ID];
					$settingValue = trim(StageShowLibUtilsClass::GetHTTPElement($_POST,$ctrlId));
					$myDBaseObj->dbgOptions[$settingId] = $settingValue;
				}
					
				$myDBaseObj->saveOptions();
				
				echo '<div id="message" class="updated"><p>Debug options updated</p></div>';
			}
			
			if (isset($_POST['testbutton_DescribeDebugSettings'])) 
			{
				$optDefs = $this->GetOptionsDefs();
				echo "<table>\n";
				foreach ($optDefs as $optDef)
				{
					$label = $optDef[StageShowLibTableClass::TABLEPARAM_LABEL];
					$ctrlId = $optDef[StageShowLibTableClass::TABLEPARAM_NAME];
					$ctrlDesc = $this->GetOptionsDescription($label);
					echo "<tr><td><strong>$label</strong></td><td>$ctrlDesc</td></tr>\n";
				}
				echo "</table>\n";
			}
?>
		<h3>Debug Settings</h3>
		<table class="stageshow-settings-table">
<?php	
		if ($myDBaseObj->ShowDebugModes())
			echo '<br>';
		
		$optDefs = $this->GetOptionsDefs();
		$count = 0;
		$checkboxesPerLine = 4;

		foreach ($optDefs as $optDef)
		{
			$label = $optDef[StageShowLibTableClass::TABLEPARAM_LABEL];
			
			if ($count == 0) echo '<tr valign="top">'."\n";
			if ($label !== '')
			{
				$ctrlId = $optDef[StageShowLibTableClass::TABLEPARAM_NAME];
				$settingId = $optDef[StageShowLibTableClass::TABLEPARAM_ID];
				$optValue = StageShowLibUtilsClass::GetArrayElement($myDBaseObj->dbgOptions, $settingId);
				if (!isset($optDef[StageShowLibTableClass::TABLEPARAM_TYPE]))
				{
					$optDef[StageShowLibTableClass::TABLEPARAM_TYPE] = StageShowLibTableClass::TABLEENTRY_CHECKBOX;
				}
				
				switch ($optDef[StageShowLibTableClass::TABLEPARAM_TYPE])
				{
					case 'TBD':
						$optText = ($optValue == 1) ? __('Enabled') : __('Disabled');
						$optEntry = $label. '&nbsp;('.$optText.')';
						break;
					
					case StageShowLibTableClass::TABLEENTRY_TEXT:
						$label .= '&nbsp';
						if ($count != 0) echo '</tr>';
						if (!$doneCheckboxes) echo '<tr><td>&nbsp</td></tr>';
						echo '<tr valign="top">'."\n";
						echo '<td align="left" valign="middle" width="25%">'.$label.'</td>'."\n";
						echo '<td align="left" colspan='.$checkboxesPerLine.'>';
						echo '<input name="'.$ctrlId.'" type="input" autocomplete="off" maxlength="127" size="60" value="'.$optValue.'" />'."\n";
						echo '</td>'."\n";
						$count = $checkboxesPerLine;
						break;
	
					case StageShowLibTableClass::TABLEENTRY_CHECKBOX:
						$optIsChecked = ($optValue == 1) ? 'checked="yes" ' : '';
						echo '<td align="left" width="25%">';
						echo '<input name="'.$ctrlId.'" type="checkbox" value="1" '.$optIsChecked.' />&nbsp;'.$label;
						echo '</td>'."\n";
						break;
				}
			}
			else
				echo '<td align="left">&nbsp;</td>'."\n";
			$count++;
			if ($count >= $checkboxesPerLine) 
			{
				echo '</tr>'."\n";
				$count = 0;
			}
		}

?>			
			<tr valign="top" colspan="4">
				<td>
				</td>
				<td>&nbsp;</td>
				<td>
				</td>
			</tr>
		</table>
		
		<input class="button-primary" type="submit" name="testbutton_SaveDebugSettings" value="Save Debug Settings"/>
		<input class="button-secondary" type="submit" name="testbutton_DescribeDebugSettings" value="Describe Debug Settings"/>
	<br>
<?php
		}
		
	}
}

?>