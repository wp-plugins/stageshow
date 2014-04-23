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

include STAGESHOW_INCLUDE_PATH.'stageshowlib_admin.php';      
include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';

if ( file_exists(STAGESHOW_INCLUDE_PATH.'stageshowlib_test_emailsale.php') ) 
	include STAGESHOW_INCLUDE_PATH.'stageshowlib_test_emailsale.php'; 
 
if (!class_exists('StageShowToolsAdminClass')) 
{
	class StageShowToolsAdminClass extends StageShowLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Tools';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function CreateToolsAdminDetailsListObject($env, $editMode = false)
		{
			return new StageShowSalesAdminDetailsListClass($env, $editMode);	
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{			
?>
<div class="wrap">
	<div class="stageshow-admin-form">
<?php
			$this->Tools_Validate();
			$this->Tools_Export();
			if (class_exists('StageShowLibTableTestEMailClass') && current_user_can(STAGESHOW_CAPABILITY_DEVUSER)) new StageShowLibTableTestEMailClass($this);
?>
	</div>
</div>
<?php
		}

		function GetValidatePerformanceSelect($perfID = 0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if ($perfID > 0)
			{
				$perfsList = $myDBaseObj->GetPerformancesListByPerfID($perfID);
				$perfRecord = $perfsList[0];
				$perfDateTime = StageShowDBaseClass::FormatDateForAdminDisplay($perfRecord->perfDateTime).'&nbsp;&nbsp;';
				$perfName = $perfRecord->showName.' - '.$perfDateTime;
				$hiddenTags  = '<input type="hidden" name="perfID" id="perfID" value="'.$perfID.'"/>'."\n";
				$html = $perfName.$hiddenTags."\n";
			}
			else
			{
				// Get performances list for all shows
				$perfsList = $myDBaseObj->GetActivePerformancesList();
			
				$selected = ' selected="" ';
				
				$html = '<select name="perfID" id="perfID">'."\n";
				
				foreach ($perfsList as $perfRecord)
				{
					$perfDateTime = StageShowDBaseClass::FormatDateForAdminDisplay($perfRecord->perfDateTime).'&nbsp;&nbsp;';
					$perfName = $perfRecord->showName.' - '.$perfDateTime;
					//$selected = ($perfID == $perfRecord->perfID) ? ' selected=""' : '';
					$html .= '<option value="'.$perfRecord->perfID.'"'.$selected.' >'.$perfName.'</option>'."\n";
					$selected = '';
				}
				
				$perfName = __("All Performances", $myDBaseObj->get_domain() );
				//$selected = ($perfID == 0) ? ' selected=""' : '';
				$html .= '<option value="0"'.$selected.' >'.$perfName.'</option>'."\n";
				
				$html .= '</select>'."\n";
			}
						
			return $html;			
		}
		
		function Tools_Validate()
		{
												
	  		// FUNCTIONALITY: Tools - Online Sale Validator
			$myDBaseObj = $this->myDBaseObj;

			$TxnId = '';
				
			StageShowLibUtilsClass::Output_Javascript_SetFocus("TxnId");
				
			$perfID = isset($_POST['perfID']) ? $_POST['perfID'] : 0;
	
?>
<script type="text/javascript">
	function stageshow_onSelectExportType(obj)
	{
		SelectControl = document.getElementById("export_format");
		newExportFormat = SelectControl.value;
		isOFXDownload = newExportFormat == "ofx";
		
		exportTypeRow = document.getElementById("stageshow-export_type-row");
		if (isOFXDownload)
		{
			exportTypeRow.style.display = 'none';
			downloadButton = document.getElementById("downloadvalidator");
			downloadButton.style.visibility = 'hidden';
		}
		else
		{
			exportTypeRow.style.display = '';
			stageshow_SetDownloadButtonStyle(obj);
		}
		stageshow_updateExportOptions();		
	}
	
	function stageshow_onSelectDownload(obj)
	{
		stageshow_SetDownloadButtonStyle();
		stageshow_updateExportOptions();		
	}
	
	function stageshow_SetDownloadButtonStyle(obj)
	{
		SelectControl = document.getElementById("export_type");
		newDownloadType = SelectControl.value;
		downloadValidatorEnabled = newDownloadType == "summary";

		downloadButton = document.getElementById("downloadvalidator");
		if (downloadValidatorEnabled)
			downloadButton.style.visibility = 'visible';
		else
			downloadButton.style.visibility = 'hidden';
	}

	function stageshow_TxnIdValid()
	{
		/* Block Validation requests if TxnId is blank */
		txnidElem = document.getElementById("TxnId");
		txnid = txnidElem.value;
		return (txnid.length > 0);
	}
</script>
<h3><?php _e('Validate Sale', $this->myDomain); ?></h3>
<form method="post">
		<?php $this->WPNonceField(); ?>
		<table class="stageshow-form-table">
			<?php
			if ($myDBaseObj->GetLocation() !== '')
			{
				$TerminalLocation = $myDBaseObj->GetLocation();
				echo "
					<tr>
						<td>".__('Location / Computer ID', $myDBaseObj->get_domain())."&nbsp;</td>
						<td>$TerminalLocation</td>
					</tr>
					<tr>
						<td>".__('Performance', $myDBaseObj->get_domain())."</td>
						<td>".$this->GetValidatePerformanceSelect($perfID)."</td>
					</tr>
					";				
			}
?>
			<tr>
		<td><?php _e('Transaction ID', $this->myDomain); ?></td>
				<td>
			<input type="text" maxlength="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN; ?>" size="<?php echo PAYPAL_APILIB_PPSALETXNID_TEXTLEN+2; ?>" name="TxnId" id="TxnId" value="<?php echo $TxnId; ?>" autocomplete="off" />
						</td>
			</tr>
			<?php
			if(isset($_POST['validatesalebutton']))
			{
				$env = StageShowLibAdminBaseClass::getEnv($this);					
				$this->ValidateSale($env, $perfID);
			}
?>
		</table>
		<p>
			<p class="submit">
<input class="button-secondary" onclick="return stageshow_TxnIdValid()" type="submit" name="validatesalebutton" value="<?php _e('Validate', $this->myDomain) ?>"/>
					</form>
<?php
		}

		function LogValidation($env, $saleID, $perfID = 0)
		{
			return true;
		}

		function ValidateSale($env, $perfID)
		{
			$saleID = 0;
				 
			$myDBaseObj = $this->myDBaseObj;
				 
			$this->CheckAdminReferer();

			$TxnId = trim(stripslashes($_POST['TxnId']));
			
			$validateMsg = 'Sale Validation (Transaction ID: '.$TxnId.') - ';
			
			if (strlen($TxnId) == 0) return 0;
			
			$results = $myDBaseObj->GetAllSalesListBySaleTxnId($TxnId);
				
			$entryCount = count($results);
			if ($entryCount == 0)
			{
				$validateMsg .= __('No matching record', $this->myDomain);
				echo '<tr><td colspan="2"><div id="message" class="error stageshow-validate-notfound"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				return 0;
			}
				
			$saleID = $results[0]->saleID;
		 
			// Check that it is for selected performance
			if ($perfID != 0)
			{
				$matchingSales = 0;
				for ($index = 0; $index<$entryCount; $index++)	
				{
					if ($results[$index]->perfID != $perfID)
					{
						unset($results[$index]);
					}
					else
					{
						$matchingSales++;
						if ($matchingSales == 1)
						{
							$salerecord = $results[$index];							
						}								
					}
				}						
			}
			else
			{
				$matchingSales = $entryCount;								
				$salerecord = $results[0];
			}
			
			if ($matchingSales == 0)
			{
				$validateMsg .= __('Wrong Performance', $this->myDomain);
				$msgClass = 'stageshow-validate-wrongperf error alert';
				echo '<tr><td colspan="2"><div id="message" class="'.$msgClass.'"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				
				$results = $myDBaseObj->GetAllSalesListBySaleTxnId($TxnId);
				$salesList = $this->CreateToolsAdminDetailsListObject($env);		
							
				echo '<tr><td colspan="2">'."\n";
				$salesList->OutputList($results);	
				echo "</td></tr>\n";
						 
				return 0;	
			}		
				
			if (!$this->LogValidation($env, $saleID, $perfID))
			{
				$validateMsg .= __('Already Verified', $this->myDomain);
				echo '<tr><td colspan="2"><div id="message" class="error stageshow-validate-duplicated"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				return 0;	
			}
			
			$validateMsg .= __('Matching record found', $this->myDomain);
			switch($salerecord->saleStatus)
			{
				case PAYPAL_APILIB_SALESTATUS_COMPLETED:
					$msgClass = 'stageshow-validate-ok updated ok';
					break;
						
				case STAGESHOW_SALESTATUS_RESERVED:
					$msgClass = 'stageshow-validate-reserved error alert';
					$validateMsg .= ' - '.__('Sale Status', $this->myDomain).' '.__($salerecord->saleStatus, $this->myDomain);
					break;
						
				default:
					$msgClass = 'stageshow-validate-unknown error';
					$validateMsg .= ' - '.__('Sale Status', $this->myDomain).' '.__($salerecord->saleStatus, $this->myDomain);
					break;
						
			}
				
			echo '<tr><td colspan="2"><div id="message" class="'.$msgClass.'"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				
			echo '<tr><td>'.__('Sale Status', $this->myDomain).':</td><td>'.__($salerecord->saleStatus, $this->myDomain).'</td></tr>'."\n";
			if ($salerecord->saleStatus == STAGESHOW_SALESTATUS_RESERVED)
			{
				echo '<tr><td>'.__('Total Due', $this->myDomain).':</td><td>'.$salerecord->salePaid.'</td></tr>'."\n";
			}
			else
			{
				if ($salerecord->saleTransactionFee > 0)
				{
					echo '<tr><td>'.__('Booking Fee', $this->myDomain).':</td><td>'.$salerecord->saleTransactionFee.'</td></tr>'."\n";
				}
				echo '<tr><td>'.__('Total Paid', $this->myDomain).':</td><td>'.$salerecord->salePaid.'</td></tr>'."\n";
			}
			
			$salesList = $this->CreateToolsAdminDetailsListObject($env);		
						
			echo '<tr><td colspan="2">'."\n";
			$salesList->OutputList($results);	
			echo "</td></tr>\n";
						 
			return $saleID;
		}
		
		function OutputExportFormatOptions()
		{
?>	
	<option value="tdt" selected="selected"><?php _e('Tab Delimited Text', $this->myDomain); ?> </option>
<?php
		}
		
		function OutputExportOptions()
		{
?>
<script type="text/javascript">
	function stageshow_updateExportOptions(obj)
	{
	}
</script>
<?php
		}
		
		function Tools_Export()
		{
			$actionURL = STAGESHOW_ADMIN_URL.STAGESHOW_FOLDER.'_export.php';
				
?>
<h3><?php _e('Export', $this->myDomain); ?></h3>
<p><?php _e('Export to a "TAB Separated Values" format file on your computer.', $this->myDomain); ?></p>
<p><?php _e('This format can be imported to many applications including spreadsheets and databases.', $this->myDomain); ?></p>
<form action="<?php echo $actionURL; ?>" method="get">
<?php $this->WPNonceField(); ?>
<table class="stageshow-form-table stageshow-export-table">
<tr>
<th><?php _e('Format', $this->myDomain); ?></th>
<td>
<select name="export_format" id="export_format" onchange=stageshow_onSelectExportType(this)>
<?php
	$this->OutputExportFormatOptions();
?>	
</select>
</td>
</tr>
<tr id="stageshow-export_type-row">
<th><?php _e('Type', $this->myDomain); ?></th>
<td>
<select name="export_type" id="export_type" onchange=stageshow_onSelectDownload(this)>
	<?php if (current_user_can(STAGESHOW_CAPABILITY_SETUPUSER)) { ?>
	<option value="settings"><?php _e('Settings', $this->myDomain); ?> </option>
	<?php } ?>
	<option value="tickets"><?php _e('Tickets', $this->myDomain); ?> </option>
	<option value="summary" selected="selected"><?php _e('Sales Summary', $this->myDomain); ?>&nbsp;&nbsp;</option>
</select>
</td>
</tr>

<?php
	$this->OutputExportOptions();
?>	
</table>
<p>
<p class="submit">
<input type="submit" name="downloadexport" class="button" value="<?php esc_attr_e('Download Export File', $this->myDomain); ?>" />
<input type="submit" name="downloadvalidator" id="downloadvalidator" class="button-secondary" value="<?php _e('Download Offline Validator', $this->myDomain); ?>" />
<input type="hidden" name="page" value="stageshow_tools" />
<input type="hidden" name="download" value="true" />
</p>
</form>
<?php
		}
		
	}
}

?>