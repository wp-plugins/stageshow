<?php
/* 
Description: Code for Managing PayPal Settings
 
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

include 'stageshowlib_admin.php';
include 'stageshowlib_table.php';

if (!class_exists('PayPalSettingsAdminListClass')) 
{
	define('PAYPAL_APILIB_URL_TEXTLEN',110);
	define('PAYPAL_APILIB_URL_EDITLEN',110);
		
	class PayPalSettingsAdminListClass extends StageShowLibAdminListClass // Define class
	{	
		const TABLEPARAM_PAYPALLOCK = 'PayPalLock';
		const TABLEPARAM_PAYPALEDIT = 'NoPayPalLock';	// This is a dummy entry when PAYPALLOCK is not required
		
		function __construct($env, $editMode = true) //constructor
		{			
			$this->blockPayPalEdit = $env['BlockPayPalEdit'];
			
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->defaultTabId = 'paypal-settings-tab';
			$this->HeadersPosn = StageShowLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "paypal-settings";
		}
		
		function GetRecordID($result)
		{
			return '';
		}
		
		function GetMainRowsDefinition()
		{
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'PayPal',       StageShowLibTableClass::TABLEPARAM_ID => 'paypal-settings-tab', ),
			);
			
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$paypalUploadImagesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/images';
			
			$CurrencyTable = StageShowLibSalesDBaseClass::GetCurrencyTable();			
			foreach ($CurrencyTable as $index => $currDef)
			{
				$currSelect[$index] = $currDef['Currency'];
				$currSelect[$index] .= '|';
				$currSelect[$index] .= $currDef['Name'];
				$currSelect[$index] .= ' ('.$currDef['Symbol'].') ';
			}
			
			$rowDefs = array(
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Environment',                     StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalEnv',             StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, StageShowLibTableClass::TABLEPARAM_ITEMS => array('live|Live', 'sandbox|Sandbox'), ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Merchant ID',                     StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalMerchantID',      StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_MERCHANTID_TEXTLEN,  StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API User',                        StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIUser',         StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN,        StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API Password',                    StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIPwd',          StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_PWD_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'API Signature',                   StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPISig',          StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_SIG_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN,  ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Account EMail',                   StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalAPIEMail',        StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_NOTFORDEMO => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_EMAIL_TEXTLEN,       StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Timeout',                StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutTimeout',       StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALEDIT => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_CHECKOUT_TIMEOUT_TEXTLEN,    StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_CHECKOUT_TIMEOUT_EDITLEN, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Currency',                        StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalCurrency',        StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALEDIT => true, StageShowLibTableClass::TABLEPARAM_ITEMS => $currSelect, ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'PayPal Header Image File',        StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalHeaderImageFile', StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_DIR => $paypalUploadImagesPath, StageShowLibTableClass::TABLEPARAM_EXTN => 'gif', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'EMail Logo Image File',           StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'PayPalLogoImageFile',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_SELECT, StageShowLibTableClass::TABLEPARAM_DIR => $paypalUploadImagesPath, StageShowLibTableClass::TABLEPARAM_EXTN => 'jpg', ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Complete URL',           StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutCompleteURL',   StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALEDIT => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_URL_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_URL_EDITLEN,  ),
				array(StageShowLibTableClass::TABLEPARAM_LABEL => 'Checkout Cancelled URL',          StageShowLibTableClass::TABLEPARAM_TAB => 'paypal-settings-tab', StageShowLibTableClass::TABLEPARAM_ID => 'CheckoutCancelledURL',  StageShowLibTableClass::TABLEPARAM_TYPE => StageShowLibTableClass::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALEDIT => true, StageShowLibTableClass::TABLEPARAM_LEN => PAYPAL_APILIB_URL_TEXTLEN,         StageShowLibTableClass::TABLEPARAM_SIZE => PAYPAL_APILIB_URL_EDITLEN,  ),
			);
			
			$rowDefs = self::MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			
			// FUNCTIONALITY: Settings - PayPal settings Read-only once Prices configured
			if (isset($this->blockPayPalEdit) && $this->blockPayPalEdit)
			{				
				foreach ($rowDefs as $rowKey => $rowDef)
				{
					if (!isset($rowDef[self::TABLEPARAM_PAYPALLOCK]))
						continue;
						
					switch ($rowDef[StageShowLibTableClass::TABLEPARAM_TYPE])
					{
						case StageShowLibTableClass::TABLEENTRY_TEXT:
						case StageShowLibTableClass::TABLEENTRY_TEXTBOX:
						case StageShowLibTableClass::TABLEENTRY_SELECT:
							//Block Editing if PayPal entries are locked .....
							$rowDefs[$rowKey][StageShowLibTableClass::TABLEPARAM_TYPE] =  StageShowLibTableClass::TABLEENTRY_READONLY;
							break;
					}
				}
			}
				
			return $rowDefs;
		}		
				
		function OutputJavascript($selectedTabIndex = 0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// FUNCTIONALITY: Settings - Default settings tab "incremented"" once Prices configured
			// Change default tab if PayPal settings have been set
			$selectedTab = 0;
			if ($myDBaseObj->SettingsConfigured() && (count($this->columnDefs)>0))
			{
				$selectedTab = $this->GetSettingsRowIndex($this->columnDefs, $this->defaultTabId);
			}
			
			parent::OutputJavascript($selectedTab);
		}
		
	}
}

if (!class_exists('PayPalSettingsAdminClass')) 
{
	class PayPalSettingsAdminClass extends StageShowLibSettingsAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->myDBaseObj = $env['DBaseObj'];	// Copy here because CanEditPayPalSettings() may uses it ...
			
			if (!$this->myDBaseObj->getDbgOption('Dev_DisablepayPalLock'))
				$this->blockPayPalEdit = !$this->myDBaseObj->CanEditPayPalSettings();
			else
				$this->blockPayPalEdit = false;
			
			$env['BlockPayPalEdit'] = $this->blockPayPalEdit;
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$SettingsUpdateMsg = '';
			$this->hiddenTags = '';
				
			// PAYPAL SETTINGS
			$PayPalAPITestChanged = false;

			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				$PayPalAPIChanged = false;

				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'PayPalEnv') || isset($_POST['errormsglive']))
				{
					if ($this->blockPayPalEdit)
					{
						// Put back original settings
						$_POST['PayPalEnv'] = $myDBaseObj->adminOptions['PayPalEnv'];
						
						$SettingsUpdateMsg = __('Plugin Entries already created - Paypal Login details cannot be changed.', $this->myDomain);
					}
				}

				if ($SettingsUpdateMsg === '')
				{
					if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'PayPalAPIUser','PayPalAPIPwd','PayPalAPISig') || isset($_POST['errormsglive']))
					{
						if (defined('STAGESHOWLIB_RUNASDEMO'))
						{
							// NO Verification in DEMO mode
							$PayPalAPIChanged = true;
						}
						else
						{							
							$payPalAPIObj = new PayPalButtonsAPIClass(__FILE__);
							if ($payPalAPIObj->VerifyPayPalLogin(
								stripslashes($_POST['PayPalEnv']), 
								stripslashes($_POST['PayPalAPIUser']), 
								stripslashes($_POST['PayPalAPIPwd']), 
								stripslashes($_POST['PayPalAPISig'])))
							{
								// New PayPal API Settings are valid			
								$PayPalAPIChanged = true;
							}
							else
							{
								// FUNCTIONALITY: Settings - Reject PayPal settings if cannot create hosted button 
								$APIStatus = $payPalAPIObj->APIStatus;
								$SettingsUpdateMsg = __('PayPal Login FAILED', $this->myDomain)." - $APIStatus";
								$this->hiddenTags .= '<input type="hidden" name="errormsglive" value="'.$SettingsUpdateMsg.'"/>'."\n";
							}
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
						mkdir($LogsFolder, 0644, TRUE);
						$LogsFolderValid = is_dir($LogsFolder);
					}
					
					if ($LogsFolderValid)
					{
						// New PayPal API Settings are valid			
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
					
					echo '<div id="message" class="error"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="error"><p>'.__('Paypal settings have NOT been saved.', $this->myDomain).'</p></div>';
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