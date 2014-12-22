<?php
/* 
Description: Define Test Defaults for PayPal Settings
 
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

if (!class_exists('GatewayDefaultsClass')) 
{
	class GatewayDefaultsClass // Pre-configured PayPal login parameters
	{
		static function GetSettingsID($filePath)
		{
			$settingsID = basename($filePath);
			if (preg_match('/wp-([a-zA-z]*)api-(.*)\.php/', $filePath, $matches) == 0) 
				return '';
			$settingsID = str_replace('-', ' ', $matches[2]);
			$settingsID = str_replace('_', ' ', $settingsID);
			
			return $settingsID;
		}
		
		static function GetPresets($gatewayId)
		{
			$dir = ABSPATH;
			$dir .= 'wp-'.$gatewayId.'api-*.php';					

			$activeModes = array();
			
			// Now get the files list and convert paths to file names
			$filesList = glob($dir);
			foreach ($filesList as $filePath)
			{
				$settingsID = self::GetSettingsID($filePath);
				$filename = basename($filePath);
				
				$activeModes[$filename] = $settingsID;
			}

			return $activeModes;
		}
		
		static function IsDefineSet($defineID)
		{
			if (defined($defineID))
			{
				echo "<br><br><strong>Cannot set PayPal settings</strong><br>";
				echo "$defineID is already defined<br><br>\n";
				return true;
			}
			
			return false;
		}
		
		static function GetDefaults($paypalSettingsFile) 
		{
			$defaultSettings = array();
			
			if ($paypalSettingsFile == '')
			{
				$defaultSettings['PayPalMerchantID'] = '';
				$defaultSettings['PayPalAPIUser'] = '';
				$defaultSettings['PayPalAPIPwd'] = '';
				$defaultSettings['PayPalAPISig'] = '';
				$defaultSettings['PayPalAPIEMail'] = '';
				
				$defaultSettings['OrganisationID'] = '';
				$defaultSettings['AdminID'] = '';
				$defaultSettings['AdminEMail'] = '';
				$defaultSettings['AdminTxnEMail'] = '';			
				
				$defaultSettings['AuthTxnId'] = '';
				$defaultSettings['AdminID'] = '';
				
				$defaultSettings['SalesID'] = '';
				$defaultSettings['SalesEMail'] = '';
				$defaultSettings['EMailTemplatePath'] = '';
			
				return $defaultSettings;
			}
			
			$className = str_replace('.php', '', $paypalSettingsFile);
			$className = str_replace('-', '_', $className);
			
			include ABSPATH.$paypalSettingsFile;
			$paypalSettingsObj = new $className();

			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEMERCHANTID))
				$defaultSettings['PayPalMerchantID'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEMERCHANTID;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEUSER))
				$defaultSettings['PayPalAPIUser'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEUSER;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEPWD))
				$defaultSettings['PayPalAPIPwd'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEPWD;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVESIG))
				$defaultSettings['PayPalAPISig'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVESIG;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEEMAIL))
				$defaultSettings['PayPalAPIEMail'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_LIVEEMAIL;
	
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ORGANISATION_ID))
				$defaultSettings['OrganisationID'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ORGANISATION_ID;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ADMIN_ID))
				$defaultSettings['AdminID'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ADMIN_ID;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ADMIN_EMAIL))
				$defaultSettings['AdminEMail'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_ADMIN_EMAIL;

			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_AUTHTXNID))
				$defaultSettings['AuthTxnId'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_AUTHTXNID;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_AUTHTXNEMAIL))
				$defaultSettings['AdminTxnEMail'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_AUTHTXNEMAIL;
	
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_SALES_ID))
				$defaultSettings['SalesID'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_SALES_ID;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_SALES_EMAIL))
				$defaultSettings['SalesEMail'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_SALES_EMAIL;
			if (isset($paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_EMAIL_TEMPLATE_PATH))
				$defaultSettings['EMailTemplatePath'] = $paypalSettingsObj->TEST_PAYPALAPI_SETTINGS_EMAIL_TEMPLATE_PATH;
			
			return $defaultSettings;
		}
	}
}