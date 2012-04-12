<?php
/* 
Description: MJS Library Admin Page functions
 
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

include "mjslib_utils.php";

if (!class_exists('SettingsAdminClass'))
{
	class SettingsAdminClass // Define class
	{
		var $settingsOpts;
		var $incTableTags;
		var $reloadMode;
		var $autocompleteTag;
		var $settings;
		
		function __construct($settings)
		{
			$this->settings = $settings;
			
			$this->incTableTags = false;
			$this->reloadMode = false;
			
			$this->autocompleteTag = ' autocomplete="off"';
		}
		
		function MergeSettings($arr1, $arr2)
		{
			// Merge Arrays ... keeping all duplicate entries
			foreach ($arr1 as $key1 => $vals1)
			{
				if (isset($arr2[$key1]))
					$arrRslt[$key1] = array_merge($arr1[$key1], $arr2[$key1]);
				else
					$arrRslt[$key1] = $vals1;
			}
			
			foreach ($arr2 as $key2 => $vals2)
			{
				if (!isset($arr1[$key2]))
					$arrRslt[$key2] = $vals2;
			}
			
			return $arrRslt;
		}
		
		function Reload($reloadMode = true)
		{
			$this->reloadMode = $reloadMode;
		}
		
		function SaveSettings($dbObj)
		{
			// Save admin settings to database
			foreach ($this->settings as $section => $settingOpts)
			{
				foreach ($settingOpts as $settingOption)
				{		
					switch ($settingOption['Type'])
					{
						case 'value':
							break;
							
						case 'checkbox':
							$controlId = $settingOption['Id'];
							$dbObj->adminOptions[$controlId] = isset($_POST[$controlId]) ? true : false;
							break;
						
						default:
							$controlId = $settingOption['Id'];
							if (isset($_POST[$controlId]))
								$dbObj->adminOptions[$controlId] = stripslashes($_POST[$controlId]);
							break;
					}
				}	
			}
					
			$dbObj->saveOptions();			
		}
		
		static function GetSelectOptsArray($selectOpts)
		{
			foreach ($selectOpts as $selectOpt)
			{
				$selectAttrs = explode('|', $selectOpt);
				if (count($selectAttrs) == 1)
				{
					$selectOptValue = $selectOptText = $selectAttrs[0];
				}
				else
				{
					$selectOptValue = $selectAttrs[0];
					$selectOptText = __($selectAttrs[1]);
				}
				
				$selectOptsArray[$selectOptValue] = $selectOptText;
			}
			
			return $selectOptsArray;
		}
		
		function GetSettingHTMLTag($adminOptions, $settingOption)
		{
			$controlId = $settingOption['Id'];

			if ($this->reloadMode)
				$controlValue = stripslashes(MJSLibUtilsClass::GetArrayElement($_POST, $controlId));		// Reuse value from submitted form
			else
				$controlValue = MJSLibUtilsClass::GetArrayElement($adminOptions, $controlId);					// Get saved value from database

			return $this->GetHTMLTag($settingOption, $controlValue);
		}
		
		static function GetHTMLTag($settingOption, $controlValue)
		{
			$autocompleteTag = ' autocomplete="off"';
			$controlName = $settingOption['Id'];
			
			$editControl = '';
			
			switch ($settingOption['Type'])
			{
				case 'text':
					$editLen = $settingOption['Len'];
					$editSize = isset($settingOption['Size']) ? $settingOption['Size'] : $editLen+1;
					$editControl = '<input type="text"'.$autocompleteTag.' maxlength="'.$editLen.'" size="'.$editSize.'" name="'.$controlName.'" value="'.$controlValue.'" />'."\n";
					break;

				case 'textbox':
					$editRows = $settingOption['Rows'];
					$editCols = $settingOption['Cols'];
					$editControl = '<textarea rows="'.$editRows.'" cols="'.$editCols.'" name="'.$controlName.'">'.$controlValue."</textarea>\n";
					break;

				case 'select':
					$selectOpts = $settingOption['Items'];
					$editControl  = '<select name="'.$controlName.'">'."\n";
					$selectOptsArray = self::GetSelectOptsArray($selectOpts);
					foreach ($selectOptsArray as $selectOptValue => $selectOptText)
					{
						$selected = ($controlValue == $selectOptValue) ? ' selected=""' : '';
						$editControl .= '<option value="'.$selectOptValue.'"'.$selected.' >'.$selectOptText."&nbsp;</option>\n";
					}
					$editControl .= '</select>'."\n";
					break;

				case 'checkbox':
					$checked = ($controlValue === true) ? 'checked="yes"' : '';
					$editControl = '<input type="checkbox" name="'.$controlName.'" id="'.$controlName.'" value="1" '.$checked.'" />&nbsp;'.$settingOption['Text']."\n";
					break;

				case 'value':
					$editControl = $settingOption['Value'];
					break;

				default:
					break;
			}

			return $editControl;
		}
		
		function Output_Form($dbObj)
		{
			$adminOptions = $dbObj->adminOptions;
			$domainName = $dbObj->get_name();
			
			$output = '';
			
			foreach ($this->settings as $settingsName => $settingOpts)
			{
				$sectionOutput = '';
			
				foreach ($settingOpts as $settingOption)
				{			
					$settingLabel = $settingOption['Label'];
					$editControl = $this->GetSettingHTMLTag($adminOptions, $settingOption);
					if ($editControl != '')
					{
						$sectionOutput .= '<tr valign="top">'."\n";
						$sectionOutput .= '<td>'.__($settingLabel, $domainName).'</td>'."\n";
						$sectionOutput .= '<td>'.$editControl.'</td>'."\n";
						$sectionOutput .= '</tr>'."\n";
					}
				}

				if ($sectionOutput != '')
				{
					$header = "<h3>".__($settingsName, $domainName)."</h3>\n";
					$sectionOutput = "$header<table class=\"form-table\">\n$sectionOutput</table>\n";					
				}		// End ... foreach ($settings as $section => $settingOpts)

				$output .= $sectionOutput;
				
			} // End ... foreach ($settings as $section => $settingOpts)

			echo $output;

		} // End function Output_Form()

	}
}

if (!class_exists('MJSLibAdminClass')) 
{
  class MJSLibAdminClass // Define class
  {
		var $caller;				// File Path of descendent class
		var $pluginName;
		var $currentPage;		
		var $salesPerPage;
		var $myPluginObj;
		var $myDBaseObj;
      		
		function __construct($env)	 //constructor	
		{
			if (is_array($env))
			{
				$this->caller = $env['caller'];
				$this->myPluginObj = $env['PluginObj'];
				$this->myDBaseObj = $env['DBaseObj'];
			}
			else
				$this->caller = $env;
				
			$callerFolders = explode("/", plugin_basename($this->caller));
			$this->pluginName = $callerFolders[0];
		}
		
		static function ValidateEmail($ourEMail)
		{
			if (strpos($ourEMail, '@') === false)
				return false;
				
			return true;
		}

		static function IsOptionChanged($adminOptions, $optionID1, $optionID2 = '', $optionID3 = '', $optionID4 = '')
		{
			if (isset($_POST[$optionID1]) && (MJSLibUtilsClass::GetArrayElement($adminOptions, $optionID1) !== trim($_POST[$optionID1])))
				return true;
			
			if ($optionID2 === '') return false;			
			if (isset($_POST[$optionID2]) && (MJSLibUtilsClass::GetArrayElement($adminOptions, $optionID2) !== trim($_POST[$optionID2])))
				return true;
			
			if ($optionID3 === '') return false;			
			if (isset($_POST[$optionID3]) && (MJSLibUtilsClass::GetArrayElement($adminOptions, $optionID3) !== trim($_POST[$optionID3])))
				return true;
			
			return false;
		}
		
		function WPNonceField()	 // output nonce as hidden value tag
		{
			$referer = plugin_basename($this->caller);
			
			if ( function_exists('wp_nonce_field') ) 
			{
				if ($this->myDBaseObj->adminOptions['Dev_EnableDebug'])
					echo "<!-- wp_nonce_field($referer) -->\n";
				wp_nonce_field($referer);
			}
		}
		
		function CheckAdminReferer()	 // check nonce created by wp_nonce_field()
		{
			$referer = plugin_basename($this->caller);
			
			if ($this->myDBaseObj->adminOptions['Dev_EnableDebug'])
				echo "<!-- check_admin_referer($referer) -->\n";
			check_admin_referer($referer);
		}

  }
}

?>