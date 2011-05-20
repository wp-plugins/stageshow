<?php
/* 
Description: Code for Managing Configuration Settings
 
Copyright 2011 Malcolm Shergold

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

	define('STAGESHOW_ADMINID_TEXTLEN',110);
	define('STAGESHOW_ADMINMAIL_TEXTLEN',127);
	define('STAGESHOW_FILEPATH_TEXTLEN',255);
	define('STAGESHOW_URL_TEXTLEN',110);
	define('STAGESHOW_ORGANISATIONID_TEXTLEN',60);
		
	define('STAGESHOW_ADMINMAIL_EDITLEN', 60);
	define('STAGESHOW_ADMINID_EDITLEN', 60);
	define('STAGESHOW_FILEPATH_EDITLEN', 95);
	define('STAGESHOW_URL_EDITLEN', 95);
	
		function OutputContent_Settings()
		{
			global $myShowObj;
			global $myDBaseObj;
			global $myPayPalAPITestObj;
			global $myPayPalAPILiveObj;
			
			echo '<div class="wrap">';
		
			$SettingsUpdateMsg = '';
			$hiddenTags = '';
				
			/*
			 * PAYPAL SETTINGS
			 */
			$PayPalAPITestChanged = false;

			$results = $myDBaseObj->GetAllShowsList();		
			$showsConfigured = (count($results) > 0);
			
			if (isset($_POST['savebutton']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				if ($myShowObj->IsOptionChanged('PayPalAPITestUser','PayPalAPITestPwd','PayPalAPITestSig') || isset($_POST['errormsgtest']))
				{
					// Block changes to PayPal Login Parameters if there are shows configured				
					if ($showsConfigured)
					{
						// Put back original settings
						$_POST['PayPalAPITestUser']   = $myDBaseObj->adminOptions['PayPalAPITestUser'];
						$_POST['PayPalAPITestPwd']    = $myDBaseObj->adminOptions['PayPalAPITestPwd'];
						$_POST['PayPalAPITestSig']    = $myDBaseObj->adminOptions['PayPalAPITestSig'];
						
						$SettingsUpdateMsg = __('Show already created - Paypal Login details cannot be changed.', STAGESHOW_DOMAIN_NAME);
					}
					else if ($myPayPalAPITestObj->VerifyPayPalLogin(stripslashes($_POST['PayPalAPITestUser']), stripslashes($_POST['PayPalAPITestPwd']), stripslashes($_POST['PayPalAPITestSig'])))
					{
						// New PayPal API Settings are valid						
						$PayPalAPITestChanged = true;
					}
					else
					{
						$SettingsUpdateMsg = __('PayPal TEST Login FAILED', STAGESHOW_DOMAIN_NAME);
						$hiddenTags .= '<input type="hidden" name="errormsgtest" value="'.$SettingsUpdateMsg.'"/>'."\n";
					}
				}				
				
				$PayPalAPILiveChanged = false;
				if ($myShowObj->IsOptionChanged('PayPalAPILiveUser','PayPalAPILivePwd','PayPalAPILiveSig') || isset($_POST['errormsglive']))
				{
					// Block changes to PayPal Login Parameters if there are shows configured				
					$results = $myDBaseObj->GetAllShowsList();		
					if (count($results) > 0)
					{
						// Put back original settings
						$_POST['PayPalAPILiveUser'] = $myDBaseObj->adminOptions['PayPalAPILiveUser'];
						$_POST['PayPalAPILivePwd'] = $myDBaseObj->adminOptions['PayPalAPILivePwd'];
						$_POST['PayPalAPILiveSig'] = $myDBaseObj->adminOptions['PayPalAPILiveSig'];
						
						$SettingsUpdateMsg = __('Show already created - Paypal Login details cannot be changed.', STAGESHOW_DOMAIN_NAME);
					}
					else if ($myPayPalAPILiveObj->VerifyPayPalLogin(stripslashes($_POST['PayPalAPILiveUser']), stripslashes($_POST['PayPalAPILivePwd']), stripslashes($_POST['PayPalAPILiveSig'])))
					{
						// New PayPal API Settings are valid			
						$PayPalAPILiveChanged = true;
					}
					else
					{
						$SettingsUpdateMsg = __('PayPal LIVE Login FAILED', STAGESHOW_DOMAIN_NAME);
						$hiddenTags .= '<input type="hidden" name="errormsglive" value="'.$SettingsUpdateMsg.'"/>'."\n";
					}
				}
				        
				if ($myShowObj->IsOptionChanged('AdminEMail'))
				{
					if (!$myShowObj->ValidateEmail($_POST('AdminEMail')))
					{
						$SettingsUpdateMsg = __('Invalid Admin EMail', STAGESHOW_DOMAIN_NAME);
					}
				}
        
				if ($myShowObj->IsOptionChanged('BookingsEMail'))
				{
					if (!$myShowObj->ValidateEmail($_POST('BookingsEMail')))
					{
						$SettingsUpdateMsg = __('Invalid Bookings EMail', STAGESHOW_DOMAIN_NAME);
					}
				}
        
				if ($myShowObj->IsOptionChanged('LogsFolderPath'))
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
					$myDBaseObj->adminOptions['PayPalEnv'] = trim($myShowObj->GetArrayElement($_POST,"PayPalEnv"));
					$myDBaseObj->adminOptions['PayPalCurrency'] = $myShowObj->GetArrayElement($_POST,"PayPalCurrency");
					
					if ($PayPalAPITestChanged)
					{
						$myDBaseObj->adminOptions['PayPalAPITestUser'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPITestUser'));
						$myDBaseObj->adminOptions['PayPalAPITestPwd'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPITestPwd'));
						$myDBaseObj->adminOptions['PayPalAPITestSig'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPITestSig'));
					}
					$myDBaseObj->adminOptions['PayPalAPITestEMail'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPITestEMail'));
					
					if ($PayPalAPILiveChanged)
					{
						$myDBaseObj->adminOptions['PayPalAPILiveUser'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPILiveUser'));
						$myDBaseObj->adminOptions['PayPalAPILivePwd'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPILivePwd'));
						$myDBaseObj->adminOptions['PayPalAPILiveSig'] = trim($myShowObj->GetArrayElement($_POST,'PayPalAPILiveSig'));
						$myDBaseObj->adminOptions['PayPalAPILiveEMail'] = $myPayPalAPILiveObj->APIemail;
					}
					
					$myDBaseObj->adminOptions['PayPalLogoImageURL'] = trim($myShowObj->GetArrayElement($_POST,'PayPalLogoImageURL'));
					$myDBaseObj->adminOptions['PayPalHeaderImageURL'] = $myShowObj->GetArrayElement($_POST,'PayPalHeaderImageURL');
					
					$myDBaseObj->SaveSettings();
					
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved.', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else
				{
					echo '<div id="message" class="updated"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="updated"><p>'.__('Paypal settings have NOT been saved.', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
			}
			
			if ($SettingsUpdateMsg === '')
			{
				// Get values from database
				$PayPalEnv = $myDBaseObj->adminOptions['PayPalEnv'];
				$PayPalCurrency = $myDBaseObj->adminOptions['PayPalCurrency'];
					
				$PayPalAPITestUser = $myDBaseObj->adminOptions['PayPalAPITestUser'];
				$PayPalAPITestPwd = $myDBaseObj->adminOptions['PayPalAPITestPwd'];
				$PayPalAPITestSig = $myDBaseObj->adminOptions['PayPalAPITestSig'];
				$PayPalAPITestEMail = $myDBaseObj->adminOptions['PayPalAPITestEMail'];
						
				$PayPalAPILiveUser = $myDBaseObj->adminOptions['PayPalAPILiveUser'];
				$PayPalAPILivePwd = $myDBaseObj->adminOptions['PayPalAPILivePwd'];
				$PayPalAPILiveSig = $myDBaseObj->adminOptions['PayPalAPILiveSig'];
				$PayPalAPILiveEMail = $myDBaseObj->adminOptions['PayPalAPILiveEMail'];
						
				$PayPalLogoImageURL = $myDBaseObj->adminOptions['PayPalLogoImageURL'];
				$PayPalHeaderImageURL = $myDBaseObj->adminOptions['PayPalHeaderImageURL'];				
			}
			else
			{
				// Use values from submitted form so user can try again
				$PayPalEnv = $_POST['PayPalEnv'];
				$PayPalCurrency = $_POST['PayPalCurrency'];
					
				$PayPalAPITestUser = stripslashes($_POST['PayPalAPITestUser']);
				$PayPalAPITestPwd = stripslashes($_POST['PayPalAPITestPwd']);
				$PayPalAPITestSig = stripslashes($_POST['PayPalAPITestSig']);
				$PayPalAPITestEMail = stripslashes($_POST['PayPalAPITestEMail']);
						
				$PayPalAPILiveUser = stripslashes($_POST['PayPalAPILiveUser']);
				$PayPalAPILivePwd = stripslashes($_POST['PayPalAPILivePwd']);
				$PayPalAPILiveSig = stripslashes($_POST['PayPalAPILiveSig']);
				$PayPalAPILiveEMail = $myDBaseObj->adminOptions['PayPalAPILiveEMail'];
						
				$PayPalLogoImageURL = stripslashes($_POST['PayPalLogoImageURL']);
				$PayPalHeaderImageURL = stripslashes($_POST['PayPalHeaderImageURL']);
			}
      
			$ppReadOnly = ($showsConfigured ? ' readonly="readonly"' : '') ;
			
			// PayPal Settings HTML Output - Start 
?>
<div class="settings_page">
<div id="icon-stageshow" class="icon32"></div>
<h2><?php echo $myShowObj->pluginName.' - '.__('Settings', STAGESHOW_DOMAIN_NAME); ?></h2>
<form method="post" action="admin.php?page=sshow_settings">
<?php 
	if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
	echo $hiddenTags; 
?>
	<h3><?php _e('Paypal Settings', STAGESHOW_DOMAIN_NAME); ?></h3>
	<table class="form-table">			
		<tr valign="top" id="tags">
      <td width="220"><?php _e('Environment', STAGESHOW_DOMAIN_NAME) ?>:</td>
      <td width="780">
				<select name="PayPalEnv"/>
					<option value="live" <?php echo ($PayPalEnv == 'live' ? 'selected' : ''); ?> >Live</option>
					<option value="sandbox" <?php echo ($PayPalEnv != 'live' ? 'selected' : ''); ?> >Sandbox (for testing)</option>
				</select>
			</td>
		</tr>
		<tr valign="top">
      <td><?php _e('Sandbox API User', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_USER_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPITestUser" value="<?php echo $PayPalAPITestUser; ?>" <?php echo $ppReadOnly; ?>"  />
		</td>
		</tr>
		<tr valign="top">
      <td><?php _e('Sandbox API Password', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_PWD_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPITestPwd" value="<?php echo $PayPalAPITestPwd; ?>" <?php echo $ppReadOnly; ?>"  />
		</td>
		</tr>
		<tr valign="top">
      <td><?php _e('Sandbox API Signature', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_SIG_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPITestSig" value="<?php echo $PayPalAPITestSig; ?>" <?php echo $ppReadOnly; ?>"  />
		</td>
		</tr>
		<tr valign="top">
      <td><?php _e('Sandbox EMail', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_EMAIL_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPITestEMail" value="<?php echo $PayPalAPITestEMail; ?>" <?php echo $ppReadOnly; ?>"  />
			</td>
		</tr>
		<tr valign="top">
      <td><?php _e('API User', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_USER_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPILiveUser" value="<?php echo $PayPalAPILiveUser; ?>" <?php echo $ppReadOnly; ?>"  />
		</td>
		</tr>
		<tr valign="top">
      <td><?php _e('API Password', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_PWD_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPILivePwd" value="<?php echo $PayPalAPILivePwd; ?>" <?php echo $ppReadOnly; ?>"  />
		</td>
		</tr>
		<tr valign="top">
      <td><?php _e('API Signature', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_PPLOGIN_SIG_TEXTLEN; ?>" size="<?php echo STAGESHOW_PPLOGIN_EDITLEN; ?>" name="PayPalAPILiveSig" value="<?php echo $PayPalAPILiveSig; ?>" <?php echo $ppReadOnly; ?>"  />
			</td>
		</tr>
		<tr valign="top">&nbsp;
			<td><?php _e('EMail', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>&nbsp;<?php echo $PayPalAPILiveEMail; ?></td>
		</tr>
		<tr valign="top">
      <td><?php _e('Currency', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<select name="PayPalCurrency">
					<option value="AUD" <?php echo ($PayPalCurrency === 'AUD' ? ' selected' :'' ); ?> >Australian Dollars</option>
					<option value="CAD" <?php echo ($PayPalCurrency === 'CAD' ? ' selected' :'' ); ?> >Canadian Dollars</option>
					<option value="EUR" <?php echo ($PayPalCurrency === 'EUR' ? ' selected' :'' ); ?> >Euros</option>
					<option value="GBP" <?php echo ($PayPalCurrency === 'GBP' ? ' selected' :'' ); ?> >Pounds Sterling</option>
					<option value="JYP" <?php echo ($PayPalCurrency === 'JYP' ? ' selected' :'' ); ?> >Yen</option>
					<option value="USD" <?php echo ($PayPalCurrency === 'USD' ? ' selected' :'' ); ?> >U.S. Dollars</option>
					<option value="NZD" <?php echo ($PayPalCurrency === 'NZD' ? ' selected' :'' ); ?> >New Zealand Dollar</option>
					<option value="CHF" <?php echo ($PayPalCurrency === 'CHF' ? ' selected' :'' ); ?> >Swiss Franc</option>
					<option value="HKD" <?php echo ($PayPalCurrency === 'HKD' ? ' selected' :'' ); ?> >Hong Kong Dollar</option>
					<option value="SGD" <?php echo ($PayPalCurrency === 'SGD' ? ' selected' :'' ); ?> >Singapore Dollar</option>
					<option value="SEK" <?php echo ($PayPalCurrency === 'SEK' ? ' selected' :'' ); ?> >Swedish Krona</option>
					<option value="DKK" <?php echo ($PayPalCurrency === 'DKK' ? ' selected' :'' ); ?> >Danish Krone</option>
					<option value="PLN" <?php echo ($PayPalCurrency === 'PLN' ? ' selected' :'' ); ?> >Polish Zloty</option>
					<option value="NOK" <?php echo ($PayPalCurrency === 'NOK' ? ' selected' :'' ); ?> >Norwegian Krone</option>
					<option value="HUF" <?php echo ($PayPalCurrency === 'HUF' ? ' selected' :'' ); ?> >Hungarian Forint</option>
					<option value="CZK" <?php echo ($PayPalCurrency === 'CZK' ? ' selected' :'' ); ?> >Czech Koruna</option>
					<option value="ILS" <?php echo ($PayPalCurrency === 'ILS' ? ' selected' :'' ); ?> >Israeli Shekel</option>
					<option value="MXN" <?php echo ($PayPalCurrency === 'MXN' ? ' selected' :'' ); ?> >Mexican Peso</option>
					<option value="BRL" <?php echo ($PayPalCurrency === 'BRL' ? ' selected' :'' ); ?> >Brazilian Real</option>
					<option value="MYR" <?php echo ($PayPalCurrency === 'MYR' ? ' selected' :'' ); ?> >Malaysian Ringgits</option>
					<option value="PHP" <?php echo ($PayPalCurrency === 'PHP' ? ' selected' :'' ); ?> >Philippine Pesos</option>
					<option value="TWD" <?php echo ($PayPalCurrency === 'TWD' ? ' selected' :'' ); ?> >Taiwan New Dollars</option>
					<option value="THB" <?php echo ($PayPalCurrency === 'THB' ? ' selected' :'' ); ?> >Thai Baht</option>
				</select>
			</td>
		</tr>
		<tr valign="top">
      <td><?php _e('PayPal Checkout Logo Image URL', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_URL_TEXTLEN; ?>" size="<?php echo STAGESHOW_URL_EDITLEN; ?>" name="PayPalLogoImageURL" value="<?php echo $PayPalLogoImageURL; ?>" />
       </td>
		</tr>
		<tr valign="top">
      <td><?php _e('PayPal Header Image URL', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td>
				<input type="text" maxlength="<?php echo STAGESHOW_URL_TEXTLEN; ?>" size="<?php echo STAGESHOW_URL_EDITLEN; ?>" name="PayPalHeaderImageURL" value="<?php echo $PayPalHeaderImageURL; ?>" />
       </td>
		</tr>
	</table>

	<h3><?php _e('General Settings', STAGESHOW_DOMAIN_NAME); ?></h3>
	<table class="form-table">
	<?php $myDBaseObj->ShowSettings($SettingsUpdateMsg === ''); ?>
	</table>
	<br></br>
	<input class="button-primary" type="submit" name="savebutton" value="<?php _e('Save Settings', STAGESHOW_DOMAIN_NAME) ?>"/>
	<br></br>

</form>
</div>
</div>
<?php
			// PayPal Settings HTML Output - End
		}	
		
		OutputContent_Settings();
?>