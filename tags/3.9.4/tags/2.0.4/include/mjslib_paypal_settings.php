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

include 'mjslib_table.php';

if (!class_exists('PayPalSettingsAdminListClass')) 
{
	define('STAGESHOW_ADMINID_TEXTLEN',110);
	define('STAGESHOW_MAIL_TEXTLEN',127);
	define('STAGESHOW_ORGANISATIONID_TEXTLEN',60);
		
	define('STAGESHOW_MAIL_EDITLEN', 60);
	define('STAGESHOW_ADMINID_EDITLEN', 60);
	define('STAGESHOW_URL_EDITLEN', 95);
	
	class PayPalSettingsAdminListClass extends MJSLibAdminListClass // Define class
	{	
		const TABLEPARAM_PAYPALLOCK = 'PayPalLock';
		
		function __construct($env) //constructor
		{			
			$this->blockPayPalEdit = $env['BlockPayPalEdit'];
			
			// Call base constructor
			parent::__construct($env, true);
			
			$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_TOP;
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
				array(self::TABLEPARAM_LABEL => 'PayPal Settings',       self::TABLEPARAM_ID => 'paypal-settings-tab', ),
			);
			
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = basename(dirname(dirname(__FILE__)));	// Library files should be in 'include' folder			
			$paypalUploadImagesPath = WP_CONTENT_DIR . '/uploads/'.$pluginID.'/images';
			
			$rowDefs = array(
				array(self::TABLEPARAM_LABEL => 'Environment',               self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalEnv',      self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_ITEMS => array('live|Live', 'sandbox|Sandbox'), ),
				array(self::TABLEPARAM_LABEL => 'API User',                  self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIUser',  self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_USER_TEXTLEN,  self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'API Password',              self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIPwd',   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_PWD_TEXTLEN,   self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'API Signature',             self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPISig',   self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_SIG_TEXTLEN,   self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN,  ),
				array(self::TABLEPARAM_LABEL => 'Account EMail',             self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalAPIEMail', self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,   self::TABLEPARAM_PAYPALLOCK => true, self::TABLEPARAM_LEN => PAYPAL_APILIB_PPLOGIN_EMAIL_TEXTLEN, self::TABLEPARAM_SIZE => PAYPAL_APILIB_PPLOGIN_EDITLEN, ),
				array(self::TABLEPARAM_LABEL => 'Currency',                  self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalCurrency', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_PAYPALLOCK => true, 
					self::TABLEPARAM_ITEMS => array
					(
						'AUD|Australian Dollars (&#36;)',
						'BRL|Brazilian Real (R&#36;)',
						'CAD|Canadian Dollars (&#36;)',
						'CZK|Czech Koruna (&#75;&#269;)',
						'DKK|Danish Krone (kr)',
						'EUR|Euros (&#8364;)',
						'HKD|Hong Kong Dollar (&#36;)',
						'HUF|Hungarian Forint (Ft)',
						'ILS|Israeli Shekel (&#x20aa;)',
						'MXN|Mexican Peso (&#36;)',
						'NZD|New Zealand Dollar (&#36;)',
						'NOK|Norwegian Krone (kr)',
						'PHP|Philippine Pesos (P)',
						'PLN|Polish Zloty (&#122;&#322;)',
						'GBP|Pounds Sterling (&#x20a4;)',
						'SGD|Singapore Dollar (&#36;)',
						'SEK|Swedish Krona (kr)',
						'CHF|Swiss Franc (CHF)',
						'TWD|Taiwan New Dollars (NT&#36;)',
						'THB|Thai Baht (&#xe3f;)',
						'USD|U.S. Dollars (&#36;)',
						'JYP|Yen (&#xa5;)',
					)
				),
				array(self::TABLEPARAM_LABEL => 'PayPal Checkout Logo Image File', self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalLogoImageFile',   self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_DIR => $paypalUploadImagesPath, self::TABLEPARAM_EXTN => 'jpg', ),
				array(self::TABLEPARAM_LABEL => 'PayPal Header Image File',        self::TABLEPARAM_TAB => 'paypal-settings-tab', self::TABLEPARAM_ID => 'PayPalHeaderImageFile', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT, self::TABLEPARAM_DIR => $paypalUploadImagesPath, self::TABLEPARAM_EXTN => 'gif', ),

			);
			
			$rowDefs = $this->MergeSettings(parent::GetDetailsRowsDefinition(), $rowDefs);
			
			// FUNCTIONALITY: Settings - PayPal settings Read-only once Prices configured
			if (isset($this->blockPayPalEdit) && $this->blockPayPalEdit)
			{				
				foreach ($rowDefs as $rowKey => $rowDef)
				{
					if (!isset($rowDef[self::TABLEPARAM_PAYPALLOCK]))
						continue;
						
					switch ($rowDef[MJSLibTableClass::TABLEPARAM_TYPE])
					{
						case MJSLibTableClass::TABLEENTRY_TEXT:
						case MJSLibTableClass::TABLEENTRY_TEXTBOX:
						case MJSLibTableClass::TABLEENTRY_SELECT:
							//Block Editing if PayPal entries are locked .....
							$rowDefs[$rowKey][MJSLibTableClass::TABLEPARAM_TYPE] =  MJSLibTableClass::TABLEENTRY_VIEW;
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
			if ($myDBaseObj->payPalAPIObj->IsConfigured() && (count($this->columnDefs)>0))
				$selectedTab++;
			
			parent::OutputJavascript($selectedTab);
		}
		
	}
}

if (!class_exists('MJSLibSettingsAdminClass')) 
{
	class MJSLibSettingsAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Settings';
			
			$classId = $this->GetAdminListClass();
			$this->adminListObj = new $classId($env);			
			
			// Call base constructor
			parent::__construct($env);	
		}
		
		function SaveSettings($dbObj)
		{
			$settingOpts = $this->adminListObj->GetDetailsRowsDefinition();
			
			// Save admin settings to database
			foreach ($settingOpts as $settingOption)
				{		
					switch ($settingOption[MJSLibTableClass::TABLEPARAM_TYPE])
					{
						case MJSLibTableClass::TABLEENTRY_VIEW:
							break;
						
						case MJSLibTableClass::TABLEENTRY_CHECKBOX:
							$controlId = $settingOption[MJSLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = isset($_POST[$controlId]) ? true : false;
							break;
						
						default:
							$controlId = $settingOption[MJSLibTableClass::TABLEPARAM_ID];
							if (isset($_POST[$controlId]))
								$dbObj->adminOptions[$controlId] = stripslashes($_POST[$controlId]);
							break;
					}
				}	
			
			$dbObj->saveOptions();			
		}
		
	}
}

if (!class_exists('PayPalSettingsAdminClass')) 
{
	class PayPalSettingsAdminClass extends MJSLibSettingsAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->myDBaseObj = $env['DBaseObj'];	// Copy here because CanEditPayPalSettings() may uses it ...
			$this->blockPayPalEdit = !$this->myDBaseObj->CanEditPayPalSettings();
			$env['BlockPayPalEdit'] = $this->blockPayPalEdit;
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$payPalAPIObj = $myDBaseObj->payPalAPIObj;
			
			$SettingsUpdateMsg = '';
			$this->hiddenTags = '';
				
			// PAYPAL SETTINGS
			$PayPalAPITestChanged = false;

			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
				$PayPalAPIChanged = false;
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'PayPalAPIUser','PayPalAPIPwd','PayPalAPISig') || isset($_POST['errormsglive']))
				{
					// Block changes to PayPal Login Parameters if there are performances configured				
					if ($this->blockPayPalEdit)
					{
						// Put back original settings
						$_POST['PayPalAPIUser'] = $myDBaseObj->adminOptions['PayPalAPIUser'];
						$_POST['PayPalAPIPwd'] = $myDBaseObj->adminOptions['PayPalAPIPwd'];
						$_POST['PayPalAPISig'] = $myDBaseObj->adminOptions['PayPalAPISig'];
						
						$SettingsUpdateMsg = __('Plugin Entries already created - Paypal Login details cannot be changed.', $this->myDomain);
					}
					else if ($payPalAPIObj->VerifyPayPalLogin(stripslashes($_POST['PayPalEnv']), stripslashes($_POST['PayPalAPIUser']), stripslashes($_POST['PayPalAPIPwd']), stripslashes($_POST['PayPalAPISig'])))
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
				        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'AdminEMail'))
				{
					if (!$this->ValidateEmail(stripslashes($_POST['AdminEMail'])))
					{
						$SettingsUpdateMsg = __('Invalid StageShow Sales EMail', $this->myDomain);
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
			$currency = $dbObj->adminOptions['PayPalCurrency'];
			
			parent::SaveSettings($dbObj);
			
			if (($currency !== $dbObj->adminOptions['PayPalCurrency']) || ($dbObj->adminOptions['CurrencySymbol'] === ''))
			{
				$dbObj->adminOptions['CurrencySymbol'] = $this->GetCurrencySymbol($dbObj->adminOptions['PayPalCurrency']);
				$dbObj->saveOptions();			
			}			
		}
		
		function GetCurrencySymbol($currency)
		{
			$currencySymbol = '';
			
			$settingOpts = $this->adminListObj->GetDetailsRowsDefinition();
			
			foreach ($settingOpts as $payPalSetting)
			{
				if ($payPalSetting[MJSLibTableClass::TABLEPARAM_ID] === 'PayPalCurrency')
				{					
					// Found the settings Opts for currentcy settings
					$selectOpts = $payPalSetting[MJSLibTableClass::TABLEPARAM_ITEMS];
					
					if (!isset($selectOpts[$currency]))
						break;
						
					$currencyName = $selectOpts[$currency];					
					$rtnArray = preg_split("/[\(\)]+/", $currencyName);
					
					if (count($rtnArray) >= 2)
						$currencySymbol = $rtnArray[1];
				}
			}
			
			return $currencySymbol;
		}
		
		function GetAdminListClass()
		{
			return 'PayPalSettingsAdminListClass';			
		}
		
		function Output_MainPage($updateFailed)
		{			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Stage Show Performances HTML Output - Start 
?>
	<div class="stageshow-admin-form">
	<form method="post">
<?php

			$this->WPNonceField();
			
			// Get setting as stdClass object
			$results = $myDBaseObj->GetAllSettingsList();
			if (count($results) == 0)
			{
				echo "<div class='noconfig'>" . __('No Settings Configured', $this->myDomain) . "</div>\n";
			}
			else
			{
				$this->adminListObj->OutputList($results, $updateFailed);
			}
			
			if (count($results) > 0)
				$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");
?>
	</form>
	</div>
<?php			
		}
	}
}

?>