<?php
/* 
Description: Code for Managing Prices Configuration
 
Copyright 2014 Malcolm Shergold

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
	
if (!class_exists('StageShowSaleValidateClass')) 
{
	if (!defined('STAGESHOWLIB_TESTSALES_LIMIT')) 
		define('STAGESHOWLIB_TESTSALES_LIMIT', 20);
	
	if (!defined('STAGESHOW_VERIFYLOG_DUPLICATEACTION')) 
		define('STAGESHOW_VERIFYLOG_DUPLICATEACTION', '');

	class StageShowSaleValidateClass extends StageShowLibAdminClass
	{
		function __construct($env, $inForm = false) //constructor	
		{	
			$this->pageTitle = '';	// Supress warning message
			
			parent::__construct($env);

			$this->myDBaseObj = $env['DBaseObj'];
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{		
			$this->Tools_Validate();			
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
	
			$actionURL = StageShowLibUtilsClass::GetPageURL();
			
			if (isset($_GET['auth']))
			{
				$authId = $_GET['auth'];
				$actionURL = str_replace("auth=$authId", '', $actionURL);
				$actionURL = str_replace(".php?&", ".php?", $actionURL);
				$actionURL = str_replace("&&", "&", $actionURL);
			}
			else if (isset($_POST['auth']))
				$authId = $_POST['auth'];
			else
				$authId = '';
				
?>
<script type="text/javascript">
	function stageshow_TxnIdValid()
	{
		/* Block Validation requests if TxnId is blank */
		txnidElem = document.getElementById("TxnId");
		txnid = txnidElem.value;
		return (txnid.length > 0);
	}
</script>
<h3><?php _e('Validate Sale', $this->myDomain); ?></h3>
<form method="post" target="_self" action="<?php echo $actionURL; ?>">
<?php 
			$this->WPNonceField(); 
			echo '<input type="hidden" name="auth" value="'.$authId.'"/>'."\n";
			echo '
<table class="stageshow-form-table">
';		
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
			
			$ticketsList = $results = $myDBaseObj->GetAllSalesListBySaleTxnId($TxnId);
			
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
				
				$results = $ticketsList;
				$saleID = 0;
				$salerecord = $results[0];
			}	
			else
			{
				$perfID = $salerecord->perfID;
				$alreadyValidated = !$this->LogValidation($env, $saleID, $perfID) && (STAGESHOW_VERIFYLOG_DUPLICATEACTION != 'ignore');
				if ( $alreadyValidated )
				{
					$validateMsg .= __('Already Verified', $this->myDomain);
					$msgClass = "error stageshow-validate-duplicated";
				}
				else
				{
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
				}
					
				echo '<tr><td colspan="2"><div id="message" class="'.$msgClass.'"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
				
				if ($alreadyValidated)
				{
					if (STAGESHOW_VERIFYLOG_DUPLICATEACTION == 'hide')
						return 0;					
				}	
				
			}
			echo '<tr><td>'.__('Name', $this->myDomain).':</td><td>'.$salerecord->saleFirstName.' '.$salerecord->saleLastName.'</td></tr>'."\n";
			echo '<tr><td>'.__('Sale Status', $this->myDomain).':</td><td>'.__($salerecord->saleStatus, $this->myDomain).'</td></tr>'."\n";
			if ($salerecord->saleStatus == STAGESHOW_SALESTATUS_RESERVED)
			{
				echo '<tr><td>'.__('Total Due', $this->myDomain).':</td><td>'.$salerecord->salePaid.'</td></tr>'."\n";
			}
			else
			{
/*
				if ($salerecord->saleTransactionFee > 0)
				{
					echo '<tr><td>'.__('Booking Fee', $this->myDomain).':</td><td>'.$salerecord->saleTransactionFee.'</td></tr>'."\n";
				}
				if ($salerecord->saleDonation > 0)
				{
					echo '<tr><td>'.__('Donation', $this->myDomain).':</td><td>'.$salerecord->saleDonation.'</td></tr>'."\n";
				}
				echo '<tr><td>'.__('Total Paid', $this->myDomain).':</td><td>'.$salerecord->salePaid.'</td></tr>'."\n";
*/
			}				

			$classPrefix = str_replace('DBaseClass', '', STAGESHOW_DBASE_CLASS);
			$classId = $classPrefix.'SalesAdminDetailsListClass';
			$salesList = new $classId($env);	
						
			echo '<tr><td colspan="2">'."\n";
			$salesList->OutputList($results);	
			echo "</td></tr>\n";
						 
			return $saleID;
		}
		
	}
}

?>