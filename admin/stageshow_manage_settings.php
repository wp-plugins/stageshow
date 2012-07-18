<?php
/* 
Description: Code for Managing Configuration Settings
 
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

if (!class_exists('StageShowManageSettingsClass')) 
{
	define('STAGESHOW_ADMINID_TEXTLEN',110);
	define('STAGESHOW_ADMINMAIL_TEXTLEN',127);
	define('STAGESHOW_ORGANISATIONID_TEXTLEN',60);
		
	define('STAGESHOW_ADMINMAIL_EDITLEN', 60);
	define('STAGESHOW_ADMINID_EDITLEN', 60);
	define('STAGESHOW_URL_EDITLEN', 95);
	
	class StageShowManageSettingsClass extends MJSLibAdminClass // Define class
	{
		function __construct($env)
		{
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$payPalAPIObj = $myDBaseObj->payPalAPIObj;
			
			$genSettingsObj = $myDBaseObj->GetSettingsObj();
			
			echo '<div class="wrap">';
		
			$SettingsUpdateMsg = '';
			$hiddenTags = '';
				
			// PAYPAL SETTINGS
			$PayPalAPITestChanged = false;

			$results = $myDBaseObj->GetAllPerformancesList();		
			$perfsConfigured = (count($results) > 0);
			
			if (isset($_POST['savesettingsbutton']))
			{
				check_admin_referer(plugin_basename($this->caller)); // check nonce created by wp_nonce_field()
				
				$PayPalAPIChanged = false;
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'PayPalAPIUser','PayPalAPIPwd','PayPalAPISig') || isset($_POST['errormsglive']))
				{
					// Block changes to PayPal Login Parameters if there are performances configured				
					if ($perfsConfigured)
					{
						// Put back original settings
						$_POST['PayPalAPIUser'] = $myDBaseObj->adminOptions['PayPalAPIUser'];
						$_POST['PayPalAPIPwd'] = $myDBaseObj->adminOptions['PayPalAPIPwd'];
						$_POST['PayPalAPISig'] = $myDBaseObj->adminOptions['PayPalAPISig'];
						
						$SettingsUpdateMsg = __('Perfoamances already created - Paypal Login details cannot be changed.', STAGESHOW_DOMAIN_NAME);
					}
					else if ($payPalAPIObj->VerifyPayPalLogin(stripslashes($_POST['PayPalEnv']), stripslashes($_POST['PayPalAPIUser']), stripslashes($_POST['PayPalAPIPwd']), stripslashes($_POST['PayPalAPISig'])))
					{
						// New PayPal API Settings are valid			
						$PayPalAPIChanged = true;
					}
					else
					{
						$SettingsUpdateMsg = __('PayPal Login FAILED', STAGESHOW_DOMAIN_NAME);
						$hiddenTags .= '<input type="hidden" name="errormsglive" value="'.$SettingsUpdateMsg.'"/>'."\n";
					}
				}
				        
				if ($this->IsOptionChanged($myDBaseObj->adminOptions, 'AdminEMail'))
				{
					if (!$this->ValidateEmail(stripslashes($_POST['AdminEMail'])))
					{
						$SettingsUpdateMsg = __('Invalid StageShow Sales EMail', STAGESHOW_DOMAIN_NAME);
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
						$SettingsUpdateMsg = __('Cannot Create Logs Folder', STAGESHOW_DOMAIN_NAME);
					}
				}
        
				if ($SettingsUpdateMsg === '')
				{
					$genSettingsObj->SaveSettings($myDBaseObj);					
					$myDBaseObj->saveOptions();
					
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved.', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else
				{
					$genSettingsObj->Reload();		
					
					echo '<div id="message" class="error"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="error"><p>'.__('Paypal settings have NOT been saved.', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
			}
			
			$genSettingsObj->ppReadOnly = $perfsConfigured;
			
			// PayPal Settings HTML Output - Start 
?>
<div class="settings_page">
<div id="icon-stageshow" class="icon32"></div>
<h2><?php echo $myPluginObj->pluginName.' - '.__('Settings', STAGESHOW_DOMAIN_NAME); ?></h2>
<form method="post" action="admin.php?page=<?php echo STAGESHOW_MENUPAGE_SETTINGS; ?>">
<?php 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename($this->caller));
	echo $hiddenTags; 
	$genSettingsObj->Output_Form($myDBaseObj);
?>
	<br></br>
<?php 
	$myDBaseObj->OutputButton("savesettingsbutton", "Save Settings", "button-primary");
?>
	<br></br>

</form>
</div>
</div>
<?php
			// PayPal Settings HTML Output - End
		}	
	}
}

?>