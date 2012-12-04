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

if (!class_exists('MJSLibAdminBaseClass')) 
{
	class MJSLibAdminBaseClass // Define class
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
  	}
}

if (!class_exists('MJSLibAdminClass')) 
{
  class MJSLibAdminClass extends MJSLibAdminBaseClass// Define class
  {
		var $env;
		
		var $pluginName;
		var $currentPage;		
		var $salesPerPage;
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
 			
			echo '<div class="wrap">';

			$this->ProcessActionButtons();
			
			echo '
				<div id="icon-stageshow" class="icon32"></div>
				<h2>'.$myDBaseObj->get_name().' - '.__($this->pageTitle, $this->myDomain).'</h2>'."\n";
				
			echo "
<script>

function HideElement(obj)
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
				$settingId = $setting[MJSLibTableClass::TABLEPARAM_ID];
				
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
		
		static function ActionButtonHTML($buttonText, $caller, $domainId, $buttonClass, $elementId = 0)
		{
			$buttonAction = strtolower(str_replace(" ", "", $buttonText));
			$buttonText = __($buttonText, $domainId);
			$page = $_GET['page'];
				
			$editLink = 'admin.php?page='.$page.'&action='.$buttonAction;
			if ($elementId !== 0) $editLink .= '&id='.$elementId;
			$editLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($editLink, plugin_basename($caller)) : $editLink;
			$editControl = '<div class='.$buttonClass.'><a class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a></div>'."\n";  
			return $editControl;    
		}
		
		function GetAdminListClass()
		{
			return '';			
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