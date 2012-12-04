<?php
/* 
Description: Code for Admin Tools
 
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
include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';

if ( file_exists(STAGESHOW_ADMIN_PATH.'stageshow_test_emailsale.php') ) 
	include STAGESHOW_ADMIN_PATH.'stageshow_test_emailsale.php'; 
 
if (!class_exists('StageShowToolsAdminClass')) 
{
	class StageShowToolsAdminClass extends MJSLibAdminClass // Define class
	{
		var $actionURL;
		
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Tools';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{			
			$myPluginObj = $this->myPluginObj;
			
			$this->actionURL = STAGESHOW_ADMIN_URL.'stageshow_export.php';
				
?>
<div class="wrap">
	<div class="stageshow-admin-form">
<?php
			$this->Tools_Validate();
			$this->Tools_Export();
			if (current_user_can(STAGESHOW_CAPABILITY_ADMINUSER)) $this->Tools_FlushSalesRecords();				
			if (class_exists('StageShowTestEMailClass') && current_user_can(STAGESHOW_CAPABILITY_DEVUSER)) new StageShowTestEMailClass($this);
?>
	</div>
</div>
<?php
		}

		function Tools_Validate()
		{
												
	  		// FUNCTIONALITY: Tools - Online Sale Validator
			$myDBaseObj = $this->myDBaseObj;

			$TxnId = '';
				
			MJSLibUtilsClass::Output_Javascript_SetFocus("TxnId");
							
?>
<script type="text/javascript">
	function onSelectDownload(obj)
	{
	selectControl = document.getElementById("sshow_ex_type");
	newDownloadType = selectControl.value;
	isSummaryDownload = newDownloadType == "summary";

	downloadButton = document.getElementById("downloadvalidator");
	if (newDownloadType == "summary")
	downloadButton.style.visibility = 'visible';
	else
	downloadButton.style.visibility = 'hidden';
	}
</script>
<h3><?php _e('Validate Sale', $this->myDomain); ?></h3>
<form method="post">
		<?php $this->WPNonceField(); ?>
		<table class="form-table">
			<?php
			if ($myDBaseObj->GetLocation() !== '')
			{
				$TerminalLocation = $myDBaseObj->GetLocation();
				echo "<tr>
			<td>".__('Location / Computer ID', $myDBaseObj->get_domain())."</td>
			<td>$TerminalLocation</td>
		</tr>
		";
		}
?>
			<tr>
		<th><label for="sshow_ex_type"><?php _e('Transaction ID', $this->myDomain); ?></label></th>
				<td>
			<input type="text" maxlength="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN; ?>" size="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN; ?>" name="TxnId" id="TxnId" value="<?php echo $TxnId; ?>" autocomplete="off" />
						</td>
			</tr>
			<?php
			if(isset($_POST['validatesalebutton']))
			{
				$env = MJSLibAdminBaseClass::getEnv($this);					
				$this->ValidateSale($env);
			}
?>
		</table>
		<p>
			<p class="submit">
<input class="button-secondary" type="submit" name="validatesalebutton" value="<?php _e('Validate', $this->myDomain) ?>"/>
					</form>
<?php
		}

		function ValidateSale($env)
		{
			$saleID = 0;
				 
			$myDBaseObj = $this->myDBaseObj;
				 
			$this->CheckAdminReferer();

			$TxnId = stripslashes($_POST['TxnId']);
			
			$validateMsg = 'Sale Validation (Transaction ID: '.$TxnId.') - ';
			
			if (strlen($TxnId) > 0)
			{
				$results = $myDBaseObj->GetAllSalesListBySaleTxnId($TxnId);
						
				if (count($results) > 0)
				{						
					$validateMsg .= __('Matching record found', $this->myDomain);
					echo '<tr><td colspan="2"><div id="message" class="updated"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
					
					$salesList = new StageShowSalesAdminDetailsListClass($env);		
							
					echo '<tr><td colspan="2">'."\n";
					$salesList->OutputList($results);	
					echo "</tr></td>\n";
							 
					$saleID = $results[0]->saleID;
				}
				else
				{
					$validateMsg .= __('No matching record', $this->myDomain);
					echo '<tr><td colspan="2"><div id="message" class="error"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				}
			}
			 
			return $saleID;
		}
		
		function Tools_Export()
		{
			$this->actionURL = STAGESHOW_ADMIN_URL.'stageshow_export.php';
				
?>

<h3><?php _e('Export Data', $this->myDomain); ?></h3>
<p><?php _e('Export Configuration and Ticket Sales to a "TAB Separated Text" format file on your computer.', $this->myDomain); ?></p>
<p><?php _e('This format can be imported to many applications including spreadsheets and databases.', $this->myDomain); ?></p>
<form action="<?php echo $this->actionURL; ?>" method="get">
<?php $this->WPNonceField(); ?>
<table class="form-table">
<tr>
<th><?php _e('Export', $this->myDomain); ?></th>
<td>
<select name="sshow_ex_type" id="sshow_ex_type" onchange=onSelectDownload(this)>
	<?php if (current_user_can(STAGESHOW_CAPABILITY_ADMINUSER)) { ?>
	<option value="settings"><?php _e('Settings', $this->myDomain); ?> </option>
	<?php } ?>
	<option value="tickets"><?php _e('Tickets', $this->myDomain); ?> </option>
	<option value="summary" selected="selected"><?php _e('Sales Summary', $this->myDomain); ?>&nbsp;&nbsp;</option>
</select>
</td>
</tr>
</table>
<p>
<p class="submit">
<input type="submit" name="downloadexport" class="button" value="<?php esc_attr_e('Download Export File', $this->myDomain); ?>" />
<input type="submit" name="downloadvalidator" id="downloadvalidator" class="button-secondary" value="<?php _e('Downlod Offline Validator', $this->myDomain); ?>" />
<input type="hidden" name="page" value="stageshow_tools" />
<input type="hidden" name="download" value="true" />
</p>
</form>
<?php
		}
		
		function Tools_FlushSalesRecords()
		{			
	  		// FUNCTIONALITY: Tools - Flush Sales DB 
			$myDBaseObj = $this->myDBaseObj;
			if(isset($_POST['flushsalesbutton']))
			{
				$this->CheckAdminReferer();
						
				$myDBaseObj->DeleteOrphanedSales();
			}
	
?>
<h3><?php _e('Sales Records', $this->myDomain); ?></h3>
<p><?php _e('Sales records are not deleted when shows or performances are deleted.', $this->myDomain); ?></p>
<p><?php _e('Individual sales records can be deleted on the sales page. All sales records for sales where the corresponding show or performance has been removed can be deleted by clicking the button below.', $this->myDomain); ?></p>
<form method="post">
	<?php $this->WPNonceField(); ?>
<p>
<p class="submit">
<input class="button-secondary" type="submit" name="flushsalesbutton" value="<?php _e('Flush Sales Records', $this->myDomain) ?>" onclick="javascript:return confirmDelete('Orphaned Sales Records')"/>
</p>
</form>

<?php
		}
		
	}
}

?>