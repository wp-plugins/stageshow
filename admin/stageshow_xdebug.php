<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
		{	
			global $stageShowObj;
			global $stageShowDBaseObj;
			global $myPayPalAPITestObj;
					
			// Stage Show TEST HTML Output - Start 
?>
<div class="wrap">
<div id="icon-stageshow" class="icon32"></div>
<form method="post" action="admin.php?page=sshow_debug">
<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__)); ?>
	<h2><?php echo $stageShowObj->pluginName.' - TEST' ?></h2>
	<?php 
		Test_DebugSettings($stageShowDBaseObj->adminOptions);
		Test_EMailSale($stageShowDBaseObj->adminOptions);
	?>
	</div>
	</div>
</form>
<?php
			// Stage Show TEST HTML Output - End
		}				 
		
		function Test_DebugSettings( $adminOptions ) {
			global $stageShowObj;
			global $stageShowDBaseObj;
			
			if (isset($_POST['testbutton_SaveDebugSettings'])) {
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
					
				$stageShowDBaseObj->adminOptions['Dev_EnableDebug'] = trim($stageShowObj->GetArrayElement($_POST,'cbEnableDebug'));
				$stageShowDBaseObj->adminOptions['Dev_ShowSQL'] = trim($stageShowObj->GetArrayElement($_POST,'cbShowSQL'));
				$stageShowDBaseObj->adminOptions['Dev_ShowPayPalIO'] = trim($stageShowObj->GetArrayElement($_POST,'cbShowPayPalIO'));
				$stageShowDBaseObj->adminOptions['Dev_ShowEMailMsgs'] = trim($stageShowObj->GetArrayElement($_POST,'cbShowEMailMsgs'));
				$stageShowDBaseObj->adminOptions['Dev_ShowDBIds'] = trim($stageShowObj->GetArrayElement($_POST,'cbShowDBIds'));
					
				$stageShowDBaseObj->saveOptions();
			}
?>
		<h3>Debug Settings</h3>
		<table class="form-table">			
			<tr valign="top">
				<td align="left" width="25%">Show SQL&nbsp<input name="cbShowSQL" type="checkbox" value="1" <?php echo $stageShowObj->GetArrayElement($stageShowDBaseObj->adminOptions,'Dev_ShowSQL') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="25%">Show PayPal IO&nbsp<input name="cbShowPayPalIO" type="checkbox" value="1" <?php echo $stageShowObj->GetArrayElement($stageShowDBaseObj->adminOptions,'Dev_ShowPayPalIO') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="25%">Show EMail Msgs&nbsp<input name="cbShowEMailMsgs" type="checkbox" value="1" <?php echo $stageShowObj->GetArrayElement($stageShowDBaseObj->adminOptions,'Dev_ShowEMailMsgs') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="25%">Show DB Ids&nbsp<input name="cbShowDBIds" type="checkbox" value="1" <?php echo $stageShowObj->GetArrayElement($stageShowDBaseObj->adminOptions,'Dev_ShowDBIds') == 1 ? 'checked="yes" ' : ''  ?> /></td>
			</tr>
			<tr valign="top" colspan="4">
				<td>
					<input class="button-primary" type="submit" name="testbutton_SaveDebugSettings" value="Save Debug Settings"/>
				</td>
			</tr>
		</table>
	<br>
<?php
		}

		function Test_EMailSale($adminOptions) {
			global $stageShowDBaseObj;

			echo '<br><br><h3>EMail Sale Test</h3>';
			
			if (isset($_POST['DivertEMailTo']))
				$DivertEMailTo = $_POST['DivertEMailTo'];
			else if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$DivertEMailTo = STAGESHOW_SAMPLE_EMAIL;
			else
				$DivertEMailTo = 'malcolm@corondeck.co.uk';

			$results = $stageShowDBaseObj->GetAllSalesList();		// Get list of sales (one row per sale)
			
			if (isset($_POST['testbutton_EMailSale'])) {
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
					
				// Run EMail Test		
				$saleID = $_POST['TestSaleID'];
				$DivertEMailTo = stripslashes($_POST['DivertEMailTo']);
				$saleResults = $stageShowDBaseObj->GetSalesListBySaleID($saleID);
				if(count($saleResults) == 0) {
					echo '<div id="message" class="updated"><p>'.__('No Sales', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else {
					$stageShowDBaseObj->EMailSale($saleResults[0]->saleID, $DivertEMailTo);
				}	
			}
			
			$TBD = 'TBD';
?>
	<table class="form-table">			
		<tr valign="top">
      <td>Divert EMail To:</td>
			<td>
				<input name="DivertEMailTo" type="text" maxlength="110" size="50" value="<?php echo $DivertEMailTo; ?>" />
			</td>
		</tr>
		<tr valign="top">
      <td>Selected Sale:</td>
			<td>
				<select name="TestSaleID">
<?php		
foreach($results as $result) {
			echo '<option value="',$result->saleID.'">'.$result->saleEMail.' - '.$result->saleDateTime.'&nbsp;&nbsp;</option>'."\n";
}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<td>
				<input class="button-primary" type="submit" name="testbutton_EMailSale" value="EMail Sale Test"/>
			</td>
			<td>&nbsp</td>
		</tr>
	</table>			
		
<?php		
		}
		

function output_action_javascript() 
{
?>
	<script type="text/javascript" >
	function onclick_Response(response)
	{
		alert('Got this from the server: ' + response);
	}
	
	function onclick_TestButton()
	{
		jQuery(document).ready
		(
			function($) 
			{
				var data = 
				{
					action: 'my_special_action',
					whatever: 1234
				};

				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				jQuery.post(ajaxurl, data, onclick_Response);
			}
		);
	}
	</script>
	<?php
}

/*
echo "<br>\n";
echo "Adding Callback!<br>\n";
add_action('wp_ajax_my_special_action', 'my_action_callback');
echo "Callback Done!<br>\n";

function my_action_callback() 
{
	global $wpdb; // this is how you get access to the database

	$whatever = $_POST['whatever'];

	$whatever += 10;

	echo $whatever;

	die();
}
*/
?>