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

include dirname(dirname(__FILE__)).'/include/stageshowlib_debug.php';

if (!class_exists('StageShowLibTestSettingsClass')) 
{
	class StageShowLibTestSettingsClass extends StageShowLibDebugSettingsClass // Define class
	{
		function __construct($env) //constructor	
		{	
			// Call base constructor
			parent::__construct($env);
			
		}
		
		function Output_MainPage($updateFailed)
		{
			parent::Output_MainPage($updateFailed);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$this->submitButtonID = $myDBaseObj->get_name()."_testsettings";
			
			echo '<form method="post">'."\n";
			$this->WPNonceField();
			
			$this->Test_SetPayPalTestSettings();
			
			echo '</form>'."\n";
			// Stage Show TEST HTML Output - End
		}
		
		function GetOptionsDefs()
		{
			$testOptionDefs1 = array();
			if (!defined('CORONDECK_RUNASDEMO'))
			{
				$testOptionDefs1 = array(
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Display IPNs',      StageShowLibTableClass::TABLEPARAM_NAME => 'cbDisplayIPNs',      StageShowLibTableClass::TABLEPARAM_ID => 'Dev_IPNDisplay', ),
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Local IPN Server',  StageShowLibTableClass::TABLEPARAM_NAME => 'cbLocalIPNServer',   StageShowLibTableClass::TABLEPARAM_ID => 'Dev_IPNLocalServer', ),
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Skip IPN Server',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbSkipIPNServer',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_IPNSkipServer', ),
					array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Log IPN Requests',  StageShowLibTableClass::TABLEPARAM_NAME => 'cbLogIPNRequests',   StageShowLibTableClass::TABLEPARAM_ID => 'Dev_IPNLogRequests', ),
				);
			}
					
			$testOptionDefs2 = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Call Stack',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowCallStack',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowCallStack', StageShowLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowDBOutput', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Call Stack Params', StageShowLibTableClass::TABLEPARAM_NAME => 'cbCallStackParams',  StageShowLibTableClass::TABLEPARAM_ID => 'Dev_CallStackParams', StageShowLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowCallStack', ),
					
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Gateway API',  StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowPayPalAPI',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowGatewayAPI', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Log Gateway API',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbLogPayPalAPI',     StageShowLibTableClass::TABLEPARAM_ID => 'Dev_LogGatewayAPI', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show Misc Debug',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowMiscDebug',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowMiscDebug', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Show WPOnce',       StageShowLibTableClass::TABLEPARAM_NAME => 'cbShowWPOnce',       StageShowLibTableClass::TABLEPARAM_ID => 'Dev_ShowWPOnce', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'No Sample Sales',   StageShowLibTableClass::TABLEPARAM_NAME => 'cbNoSampleSales',    StageShowLibTableClass::TABLEPARAM_ID => 'Dev_NoSampleSales', ),
					
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Disable Test Menus',StageShowLibTableClass::TABLEPARAM_NAME => 'cbDisableTestMenus', StageShowLibTableClass::TABLEPARAM_ID => 'Dev_DisableTestMenus', ),
			);
			
			
			$testOptionDefs = StageShowLibAdminListClass::MergeSettings($testOptionDefs1, parent::GetOptionsDefs());
			$testOptionDefs = StageShowLibAdminListClass::MergeSettings($testOptionDefs, $testOptionDefs2);

			return $testOptionDefs;
		}
		
		function GetOptionsDescription($optionName)
		{
			switch ($optionName)
			{
				case 'Display IPNs': return 'Dumping URLParamsArray to screen';
				case 'Local IPN Server': return 'Use local PHP script to verify IPN (+ no PayPal redirect)';
				case 'Skip IPN Server': return 'Skip HTTP call to PayPal to verify IPN message';
				case 'Log IPN Requests':return 'Log IPN Requests and Responses to a File';
				case 'Show Call Stack': return 'Dump Call Stack with "Show SQL"';
				case 'Call Stack Params': return 'Output Call Stack Params with "Show Call Stack"';
				case 'Show WPOnce': return 'Show calls that set/test WPOnce values';
				case 'No Sample Sales': return 'Disable adding sales to Sample';
				//case 'Show Misc Debug': return '';
				case 'Disable Test Menus': return 'Remove Test & Debug Admin Pages';
				//case 'DisablePayPal Lock': return '';
				
				default:	
					return parent::GetOptionsDescription($optionName);
					
			}
		}
		
		function Test_SetPayPalTestSettings() 
		{
			$testSettingsPath = dirname(__FILE__).'/gateway_testsettings.php';
			if (!file_exists($testSettingsPath))
			{
				echo "<br>Gateway Defaults Disabled: $testSettingsPath Not Installed <br>";
				return;				
			}
			include $testSettingsPath;
							
			$gatewaySettingPresets = array();
			
			$gatewayList = StageShowLibGatewayBaseClass::GetGatewaysList();
			foreach ($gatewayList as $gatewayItem)
			{
				$gatewayType = $gatewayItem->Type;
				$gatewaySettingPresets = array_merge($gatewaySettingPresets, GatewayDefaultsClass::GetPresets($gatewayType));
			}
			if (count($gatewaySettingPresets) < 1) return;
			
			$gatewaySettingPresets = array_merge(array('' => 'Clear'), $gatewaySettingPresets);				

			// FUNCTIONALITY: Test - Set PayPal settings to defaults
			echo '<br><br><h3>Set PayPal Test Settings</h3>';
			
			$myDBaseObj = $this->myDBaseObj;
			
			$canEditSettings = true;
			
			$selectedPayPalMode = '';	
			if (isset($_POST['PayPalTestSettings_Mode'])) 
			{
				$selectedPayPalMode = $_POST['PayPalTestSettings_Mode'];
				$testDefaults = GatewayDefaultsClass::GetDefaults($selectedPayPalMode);	
				$selectedPayPalID = GatewayDefaultsClass::GetSettingsID($selectedPayPalMode);
			}			
			
			if (isset($_POST['testbutton_SetPayPalTestSettings'])) 
			{
				$this->CheckAdminReferer();
				
				$myDBaseObj->SetTestSettings($testDefaults);							
				echo '<div id="message" class="updated"><p>Settings initialised to '.$selectedPayPalID.'</p></div>';
			}		
							
			if (isset($_POST['testbutton_ShowPayPalTestSettings'])) 
			{
				$this->CheckAdminReferer();
				
				echo "Settings for PayPal Mode: <strong>$selectedPayPalID </strong><br>\n";
				StageShowLibUtilsClass::print_r($testDefaults);
			}
			
?>
	<table class="stageshow-form-table">
<?php 
			if ($canEditSettings && (count($gatewaySettingPresets) > 0))
			{
				$content = "<select name=PayPalTestSettings_Mode>\n";
				foreach ($gatewaySettingPresets as $index => $option)
				{
					$selected = ($index == $selectedPayPalMode) ? ' selected=""' : '';
					$content .= '<option value="'.$index.'"'.$selected.'>'.$option.'&nbsp;&nbsp;</option>'."\n";
				}
				$content .= "</select>"."\n";
?>
		<tr valign="top">
			<td>PayPal Mode:&nbsp;<?php echo $content; ?></td>
		</tr>
<?php
			}
?>
		<tr valign="top">
			<td>
				<input class="button-primary" type="submit" name="testbutton_SetPayPalTestSettings" value="Use PayPal Defaults"/>
				<input class="button-primary" type="submit" name="testbutton_ShowPayPalTestSettings" value="Show PayPal Defaults"/>
			</td>
		</tr>
	</table>
	<p>
	<br></br>
		
<?php		
		}
		
	}
}

?>