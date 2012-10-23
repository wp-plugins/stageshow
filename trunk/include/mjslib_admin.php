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
				{
					foreach ($arr2[$key1] as $val2)
					{
						if (isset($val2['Posn']))
						{
							// This entry must be positioned within earlier entries
							$insertPosn = $val2['Posn'];
							array_splice($vals1, $insertPosn, 0, array($val2));
						}
						else
							$vals1 = array_merge($vals1, array($val2));
					}
					$arrRslt[$key1] = $vals1;
				}
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
						case MJSLibTableClass::TABLEENTRY_VIEW:
							break;
							
						case MJSLibTableClass::TABLEENTRY_CHECKBOX:
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
		
		static function GetSelectOptsArray($settingOption)
		{
			if (isset($settingOption['Dir']))
			{
				// Folder is defined ... create the search path
				$dir = $settingOption['Dir'];
				if (substr($dir, strlen($dir)-1, 1) != '/')
					$dir .= '/';
				if (isset($settingOption['Extn']))
					$dir .= '*.'.$settingOption['Extn'];
				else
					$dir .= '*.*';

				// Now get the files list and convert paths to file names
				$selectOpts = glob($dir);
				foreach ($selectOpts as $key => $path)
					$selectOpts[$key] = basename($path);
			}
			else
				$selectOpts = $settingOption['Items'];
					
			$selectOptsArray = array();
			
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

			if ($settingOption['Type'] === MJSLibTableClass::TABLEENTRY_FUNCTION)
			{
				$functionId = $settingOption['Func'];
				$controlValue = $this->$functionId($settingOption);				
				return $controlValue;
			}
			
			return $this->GetHTMLTag($settingOption, $controlValue);
		}
		
		static function GetHTMLTag($settingOption, $controlValue, $editMode = true)
		{
			$autocompleteTag = ' autocomplete="off"';
			$controlName = $settingOption['Id'];
			
			$editControl = '';
			
			$settingType= $settingOption['Type'];
			
			if (!$editMode)
			{
				switch ($settingType)
				{
					case MJSLibTableClass::TABLEENTRY_TEXT:
					case MJSLibTableClass::TABLEENTRY_TEXTBOX:
					case MJSLibTableClass::TABLEENTRY_SELECT:
					case MJSLibTableClass::TABLEENTRY_CHECKBOX:
					case MJSLibTableClass::TABLEENTRY_COOKIE:
						$settingType = MJSLibTableClass::TABLEENTRY_VIEW;
						break;						
				}
			}
				
			switch ($settingType)
			{
				case MJSLibTableClass::TABLEENTRY_TEXT:
				case MJSLibTableClass::TABLEENTRY_COOKIE:
					$editLen = $settingOption['Len'];
					$editSize = isset($settingOption['Size']) ? $settingOption['Size'] : $editLen+1;
					$editControl = '<input type="text"'.$autocompleteTag.' maxlength="'.$editLen.'" size="'.$editSize.'" name="'.$controlName.'" value="'.$controlValue.'" />'."\n";
					break;

				case MJSLibTableClass::TABLEENTRY_TEXTBOX:
					$editRows = $settingOption['Rows'];
					$editCols = $settingOption['Cols'];
					$editControl = '<textarea rows="'.$editRows.'" cols="'.$editCols.'" name="'.$controlName.'">'.$controlValue."</textarea>\n";
					break;

				case MJSLibTableClass::TABLEENTRY_SELECT:
					$editControl  = '<select name="'.$controlName.'">'."\n";
					$selectOptsArray = self::GetSelectOptsArray($settingOption);
					foreach ($selectOptsArray as $selectOptValue => $selectOptText)
					{
						$selected = ($controlValue == $selectOptValue) ? ' selected=""' : '';
						$editControl .= '<option value="'.$selectOptValue.'"'.$selected.' >'.$selectOptText."&nbsp;</option>\n";
					}
					$editControl .= '</select>'."\n";
					break;

				case MJSLibTableClass::TABLEENTRY_CHECKBOX:
					$checked = ($controlValue === true) ? 'checked="yes"' : '';
					$editControl = '<input type="checkbox" name="'.$controlName.'" id="'.$controlName.'" value="1" '.$checked.' />&nbsp;'.$settingOption['Text']."\n";
					break;

				case MJSLibTableClass::TABLEENTRY_VIEW:
					$editControl = $controlValue;
					break;

				case MJSLibTableClass::TABLEENTRY_VALUE:
					$editControl = $settingOption['Value'];
					break;

				default:
					//echo "<string>Unrecognised Table Entry Type - $settingType </string><br>\n";
					//MJSLibUtilsClass::ShowCallStack();
					break;
			}

			return $editControl;
		}
		
		function JS_Top()
		{
			return "
<script language='JavaScript'>
<!-- Hide script from old browsers
// End of Hide script from old browsers -->

var tabIdsList  = [";
	
		}
		
		function JS_Tab($tabID)
		{
			return "'$tabID',";	
		}
		
		function JS_Bottom($defaultTab)
		{
			return "''];

function onSettingsLoad()
{
	var tabsRowElem, index, tabId, defaultTabId;
	
	tabsRowElem = document.getElementById('mjsadmin-settings-tab-table');
	tabsRowElem.style.display = '';
	
	defaultTabId = tabIdsList[".$defaultTab."];
	
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		setTab(tabId, defaultTabId);
	}
}

window.onload = onSettingsLoad;

function setTab(tabID, selectedTabID)
{
	var headerElem, tabElem, pageElem, tabWidth;
	
	// Get the header 'Tab' Element					
	headerElem = document.getElementById('page-header-' + tabID);
	headerElem.style.display = 'none';
	
	// Get the header 'Tab' Element					
	tabElem = document.getElementById('mjsadmin-settings-tab-' + tabID);
	
	// Get the Body Element					
	pageElem = document.getElementById('mjsadmin-settings-page-' + tabID);

	tabWidth = tabElem.style.width;
	if (tabID == selectedTabID)
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'bold';	
		//tabElem.backgroundColor = '#000000';
		tabElem.style.borderBottom = '0px red solid';
		
		// Hide the settings text
		pageElem.style.display = '';
	}
	else
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'normal';	
		//tabElem.backgroundColor = '#F9F9F9';
		tabElem.style.borderBottom = '1px black solid';
		
		// Hide the settings text
		pageElem.style.display = 'none';
	}	
	
	newStyle = tabElem.style.border;
	newStyle2 = tabElem.style.border;
}

function getTabID(obj)
{
	tabID = obj.innerHTML.replace(/ /g, '-');
	tabID = tabID.replace('+', '');
	tabID = tabID.toLowerCase();
	
	return tabID;
}

function clickHeader(obj, state)
{
	var headerID, selectedTabID, index, tabId;
	
	headerID = obj.innerHTML;
			
	//alert('Clicked Header: ' + headerID);
	
	selectedTabID = getTabID(obj);
	
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		setTab(tabId, selectedTabID);
	}
}

</script>
			";
		}
		
		function GetDefaultSettingsTab($dbObj)
		{
			return 0;
		}
		
		function Output_Form($dbObj)
		{
			$selectedTab =  $this->GetDefaultSettingsTab($dbObj);
			
			$adminOptions = $dbObj->adminOptions;
			$domainName = $dbObj->get_name();
			
			$tabbedMenu = '';
			$output = '';
			$nextInline = false;
			
			$tabClassID = "mjsadmin-settings-tab";
			$pageClassID = "mjsadmin-settings-page";
			
			$javascript = $this->JS_Top();
			
			$numOfTabs = count($this->settings);
			$tabWidth = intval(100/$numOfTabs)."%";
			
			foreach ($this->settings as $settingsName => $settingOpts)
			{
				$setingsPageID = strtolower(str_replace(" ", "-", str_replace("+", "", $settingsName)));
				
				$tabElemID  = $tabClassID.'-'.$setingsPageID;
				$pageElemID = $pageClassID.'-'.$setingsPageID;
			
				$sectionOutput = '';
				
				$tabTableClass = $tabClassID.'-table';
				$tabbedMenu .= "<th id=$tabElemID class=$tabClassID width=\"$tabWidth\" onclick=clickHeader(this)>$settingsName</th>\n";
					
				foreach ($settingOpts as $settingOption)
				{			
					$settingLabel = $settingOption['Label'];
					$editControl = $this->GetSettingHTMLTag($adminOptions, $settingOption);
					if ($editControl === '')
						continue;

					if (!$nextInline)
					{
						$sectionOutput .= '<tr valign="middle">'."\n";
						$sectionOutput .= '<td>'."\n";
					}
					$sectionOutput .= __($settingLabel, $domainName)."\n";
					if (!$nextInline)
					{
						$sectionOutput .= '</td>'."\n";
						$sectionOutput .= '<td>'."\n";
					}
					$sectionOutput .= $editControl."\n";
					$nextInline = isset($settingOption['Next-Inline']);
					if (!$nextInline)
					{						
						$sectionOutput .= '</td>'."\n";
						$sectionOutput .= '</tr>'."\n";
					}
				}

				if ($sectionOutput != '')
				{
					$header = "\n";
					$header = '<div id="page-header-'.$setingsPageID.'">'."<h3>".__($settingsName, $domainName)."</h3></div>\n";
					$sectionOutput = "$header<table class=\"$pageClassID\" id=\"$pageElemID\">\n$sectionOutput</table>\n";					
					
					$javascript .= $this->JS_Tab($setingsPageID);					
				}		// End ... if ($sectionOutput != '')
				$output .= $sectionOutput;
				
			} // End ... foreach ($settings as $section => $settingOpts)

			$tabbedMenu = "<table class=$tabTableClass id=$tabTableClass style=\"display: none;\"><tr>$tabbedMenu</tr></table>\n";			
			
			$javascript .= $this->JS_Bottom($selectedTab);
			echo $javascript;
			
			echo $tabbedMenu.$output;

		} // End function Output_Form()

	}
}

if (!class_exists('MJSLibAdminClass')) 
{
  class MJSLibAdminClass // Define class
  {
		var $env;
		
		var $caller;				// File Path of descendent class
		var $pluginName;
		var $currentPage;		
		var $salesPerPage;
		var $myPluginObj;
		var $myDBaseObj;
		var $adminOptions;

		var $editingRecord;
      		
		function __construct($env)	 //constructor	
		{
			if (is_array($env))
			{
				$this->caller = $env['caller'];
				$this->myPluginObj = $env['PluginObj'];
				$this->myDBaseObj = $env['DBaseObj'];
				$this->adminOptions = $this->myDBaseObj->adminOptions;
			}
			else
			{
				MJSLibUtilsClass::ShowCallStack();
				die("Env must be an array in ".get_class()." constructor. ".__FILE__." at line ".__LINE__."\n");
			}
				
			$this->env = $env;
			
			$this->editingRecord = false;
			
			$callerFolders = explode("/", plugin_basename($this->caller));
			$this->pluginName = $callerFolders[0];
			
			if ( isset( $_POST['action'] ) && (-1 != $_POST['action']) )
				$bulkAction = $_POST['action'];
			else if ( isset( $_POST['action2'] ) && (-1 != $_POST['action2']) )
				$bulkAction =  $_POST['action2'];
			else
				$bulkAction = '';
				
 			if (($bulkAction !== '') && isset($_POST['rowSelect']))
 			{
				// Bulk Action Apply button actions
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$actionError = false;
				foreach($_POST['rowSelect'] as $recordId)
				{
					$actionError |= $this->DoBulkPreAction($bulkAction, $recordId);
				}
						
				$actionCount = 0;
				if (!$actionError)
				{
					foreach($_POST['rowSelect'] as $recordId)
					{
						if ($this->DoBulkAction($bulkAction, $recordId))
							$actionCount++;
					}
					if ($actionCount > 0)
					{
						$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
						echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
					}
				}
				else
				{										
					$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
					echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
				}
				
 			}
 			
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
				if ($this->myDBaseObj->getOption('Dev_EnableDebug'))
					echo "<!-- wp_nonce_field($referer) -->\n";
				wp_nonce_field($referer);
			}
		}
		
		function CheckAdminReferer()	 // check nonce created by wp_nonce_field()
		{
			$referer = plugin_basename($this->caller);
			
			if ($this->myDBaseObj->getOption('Dev_EnableDebug'))
			{
				echo "<!-- check_admin_referer($referer) -->\n";
				if (!wp_verify_nonce($_REQUEST['_wpnonce'], $referer))
					echo "<br><strong>check_admin_referer FAILED - verifyResult: $verifyResult - Referer: $referer </strong></br>\n";
				return;
			}
			
			check_admin_referer($referer);
		}

		function UpdateHiddenRowValues($result, $index, $settings, $dbOpts)
		{
			// Save option extensions
			foreach ($settings as $setting)
			{
				$settingId = $setting['Id'];
				
				if (!isset($_POST[$settingId.$index])) 
					continue;
					
				$newVal = $_POST[$settingId.$index];
				if ($newVal != $result->$settingId)
				{
					$this->myDBaseObj->UpdateSettings($result, $dbOpts['Table'], $settingId, $dbOpts['Index'], $index);					
				}
			}
		}
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			return false;
		}
				
		function DoBulkAction($bulkAction, $recordId)
		{
			echo "DoBulkAction() function not defined in ".get_class()."<br>\n";
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			echo "GetBulkActionMsg() function not defined in ".get_class()."<br>\n";
		}
		
		static function AddActionButton($caller, $buttonText, $buttonClass, $saleID = 0)
		{
				$buttonAction = strtolower(str_replace(" ", "", $buttonText));
				$page = $_GET['page'];
				
				$editLink = 'admin.php?page='.$page.'&action='.$buttonAction;
				if ($saleID !== 0) $editLink .= '&id='.$saleID;
				$editLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($editLink, plugin_basename($caller)) : $editLink;
				$editControl = '<div class='.$buttonClass.'><a class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a></div>'."\n";  
				return $editControl;    
		}
		
		function AdminUpgradeNotice() 
		{ 
/*
	Function to add notification to admin page
			add_action( 'admin_notices', 'AdminUpgradeNotice' );
*/
				
?>
	<div id="message" class="updated fade">
		<p><?php echo '<strong>Stageshow is ready</strong>'; ?></p>
	</div>
<?php
		}

  }
}

?>