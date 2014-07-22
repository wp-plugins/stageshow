<?php
/* 
Description: Core Library Admin Page functions
 
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

require_once "stageshowlib_utils.php";

if (!class_exists('StageShowLibAdminBaseClass')) 
{
	class StageShowLibAdminBaseClass // Define class
  	{
		var $caller;				// File Path of descendent class
		var $myPluginObj;
		var $myDBaseObj;
		var $myDomain;
		
		function __construct($env)	 //constructor	
		{
			$this->caller = $env['caller'];
			$this->myPluginObj = $env['PluginObj'];
			$this->myDBaseObj = $env['DBaseObj'];
			$this->myDomain = $env['Domain'];
		}
		
		static function getEnv($callerContext)
		{
			$env['caller'] = $callerContext->caller;
			$env['PluginObj'] = $callerContext->myPluginObj;
			$env['DBaseObj'] = $callerContext->myDBaseObj;
			$env['Domain'] = $callerContext->myDomain;
			return $env;
		}
		
		function WPNonceField($referer = '', $name = '_wpnonce', $echo = true)
		{
			$this->myDBaseObj->WPNonceField($referer, $name, $echo);
		}
		
		function AddParamAdminReferer($caller, $theLink)
		{
			return StageShowLibDBaseClass::AddParamAdminReferer($caller, $theLink);
		}
		
		function CheckAdminReferer($referer = '')
		{
			return $this->myDBaseObj->CheckAdminReferer($referer);
		}

		// Bespoke translation functions added to remove these translations from .po file
		function getTL8($text, $domain = 'default') 
		{ 
			return __($text, $domain);
		}
		
		function echoTL8($text, $domain = 'default') 
		{ 
			return _e($text, $domain);
		}
		
  	}
}

if (!class_exists('StageShowLibAdminClass')) 
{
  class StageShowLibAdminClass extends StageShowLibAdminBaseClass// Define class
  {
		var $env;
		
		var $currentPage;		
		var $adminOptions;

		var $editingRecord;
      		
		function __construct($env)	 //constructor	
		{
			parent::__construct($env);
			
			$this->adminOptions = $this->myDBaseObj->adminOptions;
				
			$this->env = $env;
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			$this->editingRecord = false;
			
			$callerFolders = explode("/", plugin_basename($this->caller));
			$this->pluginName = $callerFolders[0];
			
			if (!isset($this->pageTitle)) $this->pageTitle = "***  pageTitle Undefined ***";
			
			$this->adminMsg = '';			

			$bulkAction = '';
			if ( isset( $_POST['doaction_t'] ) )
			{
				if ( isset( $_POST['action_t'] ) && (-1 != $_POST['action_t']) )
				{
					$bulkAction = $_POST['action_t'];
				}
			}
			
			if ( isset( $_POST['doaction_b'] ) )
			{
				if ( isset( $_POST['action_b'] ) && (-1 != $_POST['action_b']) )
				{
					$bulkAction = $_POST['action_b'];
				}
			}
			
 			if (($bulkAction !== '') && isset($_POST['rowSelect']))
 			{
				// Bulk Action Apply button actions
				$this->CheckAdminReferer();
				
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
						{
							$actionCount++;
						}
					}
					// TODO - Entry Updated - Generate iCal file
				}
				
				if ($actionCount > 0)
				{
					$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
					echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
				}
				else
				{										
					$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
					echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
				}
				
 			}
 			
			$tableClass = $this->myDBaseObj->get_domain().'-admin';			
			echo '<div class="wrap '.$tableClass.'">';

			$this->ProcessActionButtons();
			
			$iconID = 'icon-'.$this->myDomain;
			echo '
				<div id="'.$iconID.'" class="icon32"></div>
				<h2>'.$myDBaseObj->get_name().' - '.__($this->pageTitle, $this->myDomain).'</h2>'."\n";
				
			echo "
<script>

function StageShowLib_HideElement(obj)
{
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(obj.id);
	
	// Hide the settings row
	tabElem.style.display = 'none';
}

</script>
			";
			
			$this->Output_MainPage($this->adminMsg !== '');
			
			echo '</div>';
		}
		
		static function ValidateEmail($ourEMail)
		{
			if (strpos($ourEMail, '@') === false)
				return false;
				
			return true;
		}

		static function IsOptionChanged($adminOptions, $optionID1, $optionID2 = '', $optionID3 = '')
		{
			if (isset($_POST[$optionID1]) && (trim(StageShowLibUtilsClass::GetArrayElement($adminOptions, $optionID1)) !== trim($_POST[$optionID1])))
			{
				return true;
			}
			
			if ($optionID2 === '') return false;			
			if (isset($_POST[$optionID2]) && (trim(StageShowLibUtilsClass::GetArrayElement($adminOptions, $optionID2)) !== trim($_POST[$optionID2])))
			{
				return true;
			}
			
			if ($optionID3 === '') return false;			
			if (isset($_POST[$optionID3]) && (trim(StageShowLibUtilsClass::GetArrayElement($adminOptions, $optionID3)) !== trim($_POST[$optionID3])))
			{
				return true;
			}
			
			return false;
		}
		
		function UpdateHiddenRowValues($result, $index, $settings, $dbOpts)
		{
			// Save option extensions
			foreach ($settings as $setting)
			{
				$settingId = $setting[StageShowLibTableClass::TABLEPARAM_ID];
				
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
		
		static function ActionButtonHTML($buttonText, $caller, $domainId, $buttonClass, $elementId, $buttonAction)
		{
			//if ($buttonAction == '') $buttonAction = strtolower(str_replace(" ", "", $buttonText));
			$buttonText = __($buttonText, $domainId);
			$page = $_GET['page'];
				
			$editLink = 'admin.php?page='.$page.'&action='.$buttonAction;
			if ($elementId !== 0) $editLink .= '&id='.$elementId;
			$editLink = StageShowLibDBaseClass::AddParamAdminReferer($caller, $editLink);
			$editControl = '<a class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a>'."\n";  
			if ($buttonClass != '')
			{
				$editControl = '<div class='.$buttonClass.'>'.$editControl.'</div>'."\n";  
			}
			return $editControl;    
		}
		
		function GetAdminListClass()
		{
			$className = get_class($this);
			$derivedClassName = str_replace('AdminClass', 'AdminListClass', $className);
			
			return $derivedClassName;
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary")
		{
			$buttonText = __($buttonText, $this->myDomain);
			
			echo "<input class=\"$buttonClass\" type=\"submit\" name=\"$buttonId\" value=\"$buttonText\" />\n";
		}
		
		function Output_MainPage($updateFailed)
		{
			echo "Output_MainPage() function not defined in ".get_class()."<br>\n";
		}
		
		function ProcessActionButtons()
		{
			echo "ProcessActionButtons() function not defined in ".get_class()."<br>\n";
		}
		
		function AdminUpgradeNotice() 
		{ 
			// Function to add notification to admin page
			// add_action( 'admin_notices', 'AdminUpgradeNotice' );
				
?>
	<div id="message" class="updated fade">
		<p><?php echo '<strong>Plugin is ready</strong>'; ?></p>
	</div>
<?php
		}
		
 	}
}

if (!class_exists('StageShowLibSettingsAdminClass')) 
{
	class StageShowLibSettingsAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Settings';
			
			$classId = $this->GetAdminListClass($this);
			$this->adminListObj = new $classId($env, true);			
			
			// Call base constructor
			parent::__construct($env);	
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$SettingsUpdateMsg = '';
				
			if (isset($_POST['savechanges']))
			{
				$this->CheckAdminReferer();
				
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
		
		function Output_MainPage($updateFailed)
		{			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// PayPal Settings HTML Output - Start 
			
			$formClass = $this->myDomain.'-admin-form';
			echo '<div class="'.$formClass.'">'."\n";
?>	
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
		
		function SaveSettings($dbObj)
		{
			$settingOpts = $this->adminListObj->GetDetailsRowsDefinition();
			
			// Save admin settings to database
			foreach ($settingOpts as $settingOption)
				{	
					if (isset($settingOption[StageShowLibTableClass::TABLEPARAM_READONLY]))
					{
						continue;
					}
					
					switch ($settingOption[StageShowLibTableClass::TABLEPARAM_TYPE])
					{
						case StageShowLibTableClass::TABLEENTRY_READONLY:
						case StageShowLibTableClass::TABLEENTRY_VIEW:
							break;
						
						case StageShowLibTableClass::TABLEENTRY_CHECKBOX:
							$controlId = $settingOption[StageShowLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = isset($_POST[$controlId]) ? true : false;
							break;
						
						case StageShowLibTableClass::TABLEENTRY_TEXT:
						case StageShowLibAdminListClass::TABLEENTRY_DATETIME:
							// Text Settings are "Trimmed"
							$controlId = $settingOption[StageShowLibTableClass::TABLEPARAM_ID];
							if (isset($_POST[$controlId]))
								$dbObj->adminOptions[$controlId] = trim(stripslashes($_POST[$controlId]));
							break;
						
						default:
							$controlId = $settingOption[StageShowLibTableClass::TABLEPARAM_ID];
							if (isset($_POST[$controlId]))
								$dbObj->adminOptions[$controlId] = stripslashes($_POST[$controlId]);
							break;
					}
				}	
			
			$dbObj->saveOptions();			
		}
		
	}
}

?>