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
		
		static function GetDefaults($gatewaySettingsFile) 
		{
			$defaultSettings = array();
			
			if ($gatewaySettingsFile == '')
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
			
			$className = str_replace('.php', '', $gatewaySettingsFile);
			$className = str_replace('-', '_', $className);
			
			include ABSPATH.$gatewaySettingsFile;
			$gatewaySettingsObj = new $className();
			$gatewayDefaults = $gatewaySettingsObj->GetDefaults();
			
			foreach ($gatewayDefaults as $defaultKey => $defaultValue)
			{
				$defaultSettings[$defaultKey] = $defaultValue;
			}

			return $defaultSettings;
		}
	}
}