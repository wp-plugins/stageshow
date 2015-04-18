<?php
/* 
Description: Code for Managing Payment Gateway Settings
 
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

include 'stageshowlib_admin.php';
include 'stageshowlib_table.php';

if (!class_exists('GatewaySettingsAdminListClass')) 
{
	class GatewaySettingsAdminListClass extends StageShowLibAdminListClass // Define class
	{	
		function __construct($env, $editMode = true) //constructor
		{			
			$myDBaseObj = $env['DBaseObj'];
			
			$this->gatewayName = $myDBaseObj->gatewayObj->GetName();
			
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->defaultTabId = 'gateway-settings-tab';
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "gateway-settings";
		}
		
		function GetRecordID($result)
		{
			return '';
		}
		
		function GetMainRowsDefinition()
		{
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Payment Gateway',  StageShowLibTableClass::TABLEPARAM_ID => 'gateway-settings-tab', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Advanced',         StageShowLibTableClass::TABLEPARAM_ID => 'advanced-settings-tab',  /* StageShowLibTableClass::TABLEPARAM_AFTER => 'general-settings-tab', */ ),				
			);
			
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$uploadImagesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/images';
			
			include 'stageshowlib_gatewaybase.php';
			$gatewayList = StageShowLibGatewayBaseClass::GetGatewaysList();
			$serverSelect = array();
			foreach ($gatewayList as $gatewayDef)
			{
				$serverSelect[] = $gatewayDef->Id.'|'.$gatewayDef->Name;
			}
						
			$gatewayDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Payment Gateway', StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'GatewaySelected', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_ITEMS => $serverSelect, ),
			);

			foreach ($gatewayList as $gatewayDef)
			{
				$gatewayDefs = self::MergeSettings($gatewayDefs, $gatewayDef->Obj->Gateway_SettingsRowsDefinition());
			}
			
			$rowDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'EMail Logo Image File',                 StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalLogoImageFile',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_DIR => $uploadImagesPath, StageShowLibTableClass::TABLEPARAM_EXTN => 'gif,jpeg,jpg,png', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Timeout',                      StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutTimeout',       StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_CHECKOUT_TIMEOUT_TEXTLEN,    StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_CHECKOUT_TIMEOUT_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Image URLs',                            StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalImagesUseSSL',    StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_CHECKBOX, StageShowLibTableClass::TABLEPARAM_TEXT => 'Enable SSL', StageShowLibTableClass::TABLEPARAM_DEFAULT => false ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Complete URL',                 StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutCompleteURL',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_URL_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_URL_EDITLEN,  ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Cancelled URL',                StageShowLibTableClass::TABLEPARAM_TAB => 'gateway-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutCancelledURL',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_URL_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_URL_EDITLEN,  ),

				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Log Files Folder Path',                 StageShowLibTableClass::TABLEPARAM_TAB => 'advanced-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'LogsFolderPath',       StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   StageShowLibTableClass::TABLEPARAM_LEN => PAYMENT_API_FILEPATH_TEXTLEN, StageShowLibTableClass::TABLEPARAM_SIZE => PAYMENT_API_FILEPATH_EDITLEN, ),				
			);
			
			$rowDefs = self::MergeSettings($gatewayDefs, $rowDefs);
			
			$rowDefs = self::MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			
			return $rowDefs;
		}		
				
		function JS_Bottom($defaultTab)
		{
			$jsCode  = parent::JS_Bottom($defaultTab);		
			$jsCode .= "

window.onload = stageshowlib_OnSettingsLoad;

</script>
			";
			
			return $jsCode;
		}
		
		function OutputJavascript($selectedTabIndex = 0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// FUNCTIONALITY: Settings - Default settings tab "incremented"" once Prices configured
			// Change default tab if Gateway settings have been set
			$selectedTab = 0;
			if ($myDBaseObj->SettingsConfigured() && (count($this->columnDefs)>0))
			{
				$selectedTab = $this->GetSettingsRowIndex($this->columnDefs, $this->defaultTabId);
			}
			
			parent::OutputJavascript($selectedTab);
		}
		
		function OutputList($results, $updateFailed = false)
		{
			ob_start();
			parent::OutputList($results, $updateFailed);
			$htmlout = ob_get_contents();
			ob_end_clean();
			
			$gatewaySelectIDDef = 'id="GatewaySelected"';
			$gatewaySelectOnClick = ' onchange="stageshowlib_ClickGateway(this)" ';
			
			$htmlout = str_replace($gatewaySelectIDDef, $gatewaySelectOnClick.$gatewaySelectIDDef, $htmlout);
			echo $htmlout;
		}
		
	}
}

if (!class_exists('GatewaySettingsAdminClass')) 
{
	class GatewaySettingsAdminClass extends StageShowLibSettingsAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->myDBaseObj = $env['DBaseObj'];
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$SettingsUpdateMsg = '';
			$this->hiddenTags = '';
				
			// Gateway SETTINGS
			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();				
				if (isset($_POST['errormsglive']) || $myDBaseObj->gatewayObj->IsLoginChanged($myDBaseObj->adminOptions))
				{
					if (defined('CORONDECK_RUNASDEMO'))
					{
						// NO Verification in DEMO mode
					}
					else
					{					
						$SettingsUpdateMsg = $myDBaseObj->gatewayObj->VerifyLogin();	
						if ($SettingsUpdateMsg != '')	
						{
							// FUNCTIONALITY: Settings - Reject Settings if cannot login successfully
							$this->hiddenTags .= '<input type="hidden" name="errormsglive" value="'.$SettingsUpdateMsg.'"/>'."\n";
						}
					}
				}
				        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'AdminEMail'))
				{
					if (!$this->ValidateEmail(stripslashes($_POST['AdminEMail'])))
					{
						$SettingsUpdateMsg = __('Invalid Sales EMail', $this->myDomain);
					}
				}
        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'LogsFolderPath'))
				{
					// Confrm that logs folder path is valid or create folder
					$LogsFolder = stripslashes($_POST['LogsFolderPath']);
					if (!strpos($LogsFolder, ':'))
						$LogsFolder = ABSPATH . '/' . $LogsFolder;
					
					$LogsFolderValid = is_dir($LogsFolder);
					if (!$LogsFolderValid)
					{
						mkdir($LogsFolder, 0600, TRUE);
						$LogsFolderValid = is_dir($LogsFolder);
					}
					
					if ($LogsFolderValid)
					{
						// New Logs Folder Settings are valid			
					}
					else
					{
						$SettingsUpdateMsg = __('Cannot Create Logs Folder', $this->myDomain);
					}
				}
        
				
				if ($SettingsUpdateMsg === '')
				{
					$this->SaveSettings($myDBaseObj);					
					$myDBaseObj->saveOptions();
					
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', $this->myDomain).'</p></div>';
				}
				else
				{
					$this->Reload();		
					
					$gatewayName = $this->myDBaseObj->gatewayObj->GetName();
					echo '<div id="message" class="error"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="error"><p>'.$gatewayName.' '.__('Settings have NOT been saved.', $this->myDomain).'</p></div>';
				}
			}
			
		}
		
		function Reload($reloadMode = true)
		{
			$this->reloadMode = $reloadMode;
		}
		
		function SaveSettings($dbObj)
		{			
			parent::SaveSettings($dbObj);
		}
		
	}
}

?>