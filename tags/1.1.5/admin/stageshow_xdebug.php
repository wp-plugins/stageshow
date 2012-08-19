<?php
/* 
Description: Code for Managing Prices Configuration
 
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
	
include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowDebugAdminClass')) 
{
	class StageShowDebugAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{	
			// Call base constructor
			parent::__construct($env);
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			// Stage Show TEST HTML Output - Start 
?>
<div class="wrap">
<div id="icon-stageshow" class="icon32"></div>
<form method="post" action="admin.php?page=<?php echo STAGESHOW_MENUPAGE_DEBUG; ?>">
<?php $this->WPNonceField(); ?>
	<h2><?php echo $myPluginObj->pluginName.' - TEST' ?></h2>
	<?php 
		$this->Test_DebugSettings();
		$this->Test_EMailSale();
	?>
</form>
</div>
<?php
			// Stage Show TEST HTML Output - End
		}				 
		
		function Test_DebugSettings() {
			$myDBaseObj = $this->myDBaseObj;
			
			if (isset($_POST['testbutton_SaveDebugSettings'])) {
				$this->CheckAdminReferer();
					
				$myDBaseObj->adminOptions['Dev_EnableDebug'] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,'cbEnableDebug'));
				$myDBaseObj->adminOptions['Dev_ShowSQL'] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,'cbShowSQL'));
				$myDBaseObj->adminOptions['Dev_ShowPayPalIO'] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,'cbShowPayPalIO'));
				$myDBaseObj->adminOptions['Dev_ShowEMailMsgs'] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,'cbShowEMailMsgs'));
				$myDBaseObj->adminOptions['Dev_ShowDBIds'] = trim(MJSLibUtilsClass::GetHTTPElement($_POST,'cbShowDBIds'));
					
				$myDBaseObj->saveOptions();
			}
?>
		<h3>Debug Settings</h3>
		<table class="form-table">			
			<tr valign="top">
				<td align="left" width="20%">Enable Debug&nbsp;<input name="cbEnableDebug" type="checkbox" value="1" <?php echo MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,'Dev_EnableDebug') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="20%">Show SQL&nbsp;<input name="cbShowSQL" type="checkbox" value="1" <?php echo MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,'Dev_ShowSQL') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="20%">Show PayPal IO&nbsp;<input name="cbShowPayPalIO" type="checkbox" value="1" <?php echo MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,'Dev_ShowPayPalIO') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="20%">Show EMail Msgs&nbsp;<input name="cbShowEMailMsgs" type="checkbox" value="1" <?php echo MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,'Dev_ShowEMailMsgs') == 1 ? 'checked="yes" ' : ''  ?> /></td>
				<td align="left" width="20%">Show DB Ids&nbsp;<input name="cbShowDBIds" type="checkbox" value="1" <?php echo MJSLibUtilsClass::GetArrayElement($myDBaseObj->adminOptions,'Dev_ShowDBIds') == 1 ? 'checked="yes" ' : ''  ?> /></td>
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

		function Test_EMailSale() {
			$myDBaseObj = $this->myDBaseObj;

			echo '<br><br><h3>EMail Sale Test</h3>';
			
			if (isset($_POST['DivertEMailTo']))
				$DivertEMailTo = stripslashes($_POST['DivertEMailTo']);
			else if (defined('STAGESHOW_SAMPLE_EMAIL'))
				$DivertEMailTo = STAGESHOW_SAMPLE_EMAIL;
			else
				$DivertEMailTo = 'malcolm@corondeck.co.uk';

			$results = $myDBaseObj->GetSalesList(null);		// Get list of sales (one row per sale)
			
			if (isset($_POST['testbutton_EMailSale'])) {
				$this->CheckAdminReferer();
					
				// Run EMail Test		
				$saleID = stripslashes($_POST['TestSaleID']);
				$DivertEMailTo = stripslashes($_POST['DivertEMailTo']);
				$saleResults = $myDBaseObj->GetSale($saleID);
				if(count($saleResults) == 0) {
					echo '<div id="message" class="error"><p>'.__('No Sales', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else {
					$myDBaseObj->EMailSale($saleResults[0]->saleID, $DivertEMailTo);
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
			<td>&nbsp;</td>
		</tr>
	</table>			
		
<?php		
		}
	}
}
		
?>