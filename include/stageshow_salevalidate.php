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

include STAGESHOW_INCLUDE_PATH.'stageshow_validate_api.php';
include STAGESHOW_INCLUDE_PATH.'stageshow_sales_table.php';
	
if (!class_exists('StageShowWPOrgSaleValidateClass')) 
{
	define('STAGESHOW_TICKETID_TEXTLEN', PAYMENT_API_SALETXNID_TEXTLEN+10);
	
	if (!defined('STAGESHOWLIB_TESTSALES_LIMIT')) 
		define('STAGESHOWLIB_TESTSALES_LIMIT', 20);
	
	if (!defined('STAGESHOW_VERIFYLOG_DUPLICATEACTION')) 
		define('STAGESHOW_VERIFYLOG_DUPLICATEACTION', '');

	define('STAGESHOW_SALEVALIDATE_TARGET', 'stageshow_jquery_validate.php');
	
	if (!defined('STAGESHOW_VALIDATERESULT_TIMEOUT')) 
		define('STAGESHOW_VALIDATERESULT_TIMEOUT', 1000);

	include STAGESHOW_INCLUDE_PATH.'stageshowlib_admin.php';
	
	class StageShowWPOrgSaleValidateClass extends StageShowLibAdminClass
	{
		var $TL8Strings = array();
		
		function __construct($env, $inForm = false) //constructor	
		{	
			$this->pageTitle = '';	// Supress warning message
			
			$myDomain = $env['Domain'];
			
			$this->StoreTranslatedText('All Performances', $myDomain);
			$this->StoreTranslatedText('Already Verified', $myDomain);
			$this->StoreTranslatedText('Matching record found', $myDomain);
			$this->StoreTranslatedText('No matching record', $myDomain);
			$this->StoreTranslatedText('Sale Status', $myDomain);
			$this->StoreTranslatedText('Sale Validation', $myDomain);
			$this->StoreTranslatedText('Sale Reference', $myDomain);
			$this->StoreTranslatedText('Wrong Performance', $myDomain);

			$valDBClass = STAGESHOW_PLUGIN_NAME.'ValidateDBaseClass';
			$this->validateDBObj = new $valDBClass();

			parent::__construct($env);
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
				$perfDateTime = StageShowLibGenericDBaseClass::FormatDateForAdminDisplay($perfRecord->perfDateTime).'&nbsp;&nbsp;';
				$perfName = $perfRecord->showName.' - '.$perfDateTime;
				$hiddenTags  = '<input type="hidden" name="perfID" id="perfID" value="'.$perfID.'"/>'."\n";
				$html = $perfName.$hiddenTags."\n";
			}
			else
			{
				// Get performances list for all shows
				$perfsList = $this->validateDBObj->GetActivePerformancesList();
			
				$selected = ' selected="" ';
				
				$html = '<select name="perfID" id="perfID">'."\n";
				
				foreach ($perfsList as $perfRecord)
				{
					$perfDateTime = StageShowLibGenericDBaseClass::FormatDateForAdminDisplay($perfRecord->perfDateTime).'&nbsp;&nbsp;';
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
		
		function StoreTranslatedText($text, $domain)
		{
			$translation = __($text, $domain);
			if ($text != $translation)
			{
				$this->TL8Strings[$text] = $translation;				
			}
		}
		
		function TranslatedText($text, $unused)
		{
			if (isset($this->TL8Strings[$text]))
			{
				$TL8text = $this->TL8Strings[$text];
			}
			else
			{
				$TL8text = __($text, $this->myDomain);
			}
			$id = 'label_'.str_replace(' ', '_', $text);
			
			return $TL8text.'<input type="hidden" name="'.$id.'" id="'.$id.'" value="'.$TL8text.'" />';
		}
		
		function Tools_Validate()
		{												
	  		// FUNCTIONALITY: Tools - Online Sale Validator
			$myDBaseObj = $this->myDBaseObj;

			$this->WPNonceField(); 
			
			echo "<h3>".__('Validate Sale', $this->myDomain)."</h3>";
			
			$this->ValidateSaleForm();
		}
				
		function ValidateSaleForm()
		{
include STAGESHOW_INCLUDE_PATH.'stageshowlib_nonce.php';      
			$myDBaseObj = $this->myDBaseObj;
			
			$TxnId = '';
			$perfID = isset($_REQUEST['perfID']) ? $_REQUEST['perfID'] : 0;	
			if (!is_numeric($perfID)) return;
			
			$actionURL = StageShowLibUtilsClass::GetPageURL();
			
?>
<!--<form method="post" target="_self" action="<?php echo $actionURL; ?>">-->
<div id="stageshow-validate-table">
<?php 
			echo '
<table class="stageshow-form-table">
';		
			$TerminalLocation = isset($_POST['location']) ? $_POST['location'] : $myDBaseObj->GetLocation();	// TODO: Check for SQLi
			if ($TerminalLocation !== '')
			{
				echo '
					<tr>
						<td class="stageshow_tl8" id="label_Location">'.__("Location / Computer ID", $myDBaseObj->get_domain()).'&nbsp;</td>
						<td id="value_Location">'.$TerminalLocation.'</td>
					</tr>
					<tr>
						<td class="stageshow_tl8" id="label_Performance">'.__("Performance", $myDBaseObj->get_domain()).'</td>
						<td id="value_Performance">'.$this->GetValidatePerformanceSelect($perfID).'</td>
					</tr>
					';				
			}
?>
			<tr>
				<td class="stageshow_tl8" id="label_Transaction_ID"><?php _e('Sale Reference', $this->myDomain); ?></td>
				<td id="value_Transaction_ID">
					<input class="stageshow-tools-ui" type="text" maxlength="<?php echo STAGESHOW_TICKETID_TEXTLEN; ?>" size="<?php echo STAGESHOW_TICKETID_TEXTLEN+2; ?>" name="TxnId" id="TxnId" value="<?php echo $TxnId; ?>" autocomplete="off" />
					&nbsp;
					<input class="stageshow-tools-ui button-primary" onclick="stageshow_onclick_validate()" type="button" name="jqueryvalidatebutton" id="jqueryvalidatebutton" value="Validate"/>
				</td>
			</tr>
			<?php
			$jQueryURL = STAGESHOW_URL."include/".STAGESHOW_SALEVALIDATE_TARGET;
			$ourNOnce = StageShowLibNonce::GetStageShowLibNonce(STAGESHOW_SALEVALIDATE_TARGET);

			echo '
			<script>
				jQuery(document).ready(
					function()
					{
					   jQuery("#TxnId").on("change textInput input", function () 
					   {
					        var txnid = this.value;
					    	if (txnid.length > 0)
							{
								var lastChar = txnid.slice(-1);
								if (lastChar == " ")
								{
						      		stageshow_onclick_validate();
								}
							}
					    });

					    jQuery("#TxnId").keypress(function(e)
					    {
					    	if (e.keyCode == 13)
					    	{
					      		stageshow_onclick_validate();
							}
					    });
					    
						jQuery("#jqueryvalidatebutton").prop("disabled", false);
						stageshow_set_txnid_focus();
					}
				);

				
				function stageshow_set_txnid_focus()
				{
					jQuery("#TxnId").focus();
				}	

				function stageshow_onclick_validate()
				{
					/* Get input values from form */
					var TxnId = jQuery("#TxnId").val();
					var perfID = jQuery("#perfID").val();
					var location = jQuery("#value_Location").html();
					
					if (TxnId.length <= 0) return;
		
					/* Disable the button and input box .... this will be replaced when the page refreshes */					
					/* Set Cursor to Busy and Disable All UI Buttons */
					StageShowLib_SetBusy(true, "stageshow-tools-ui");

					var postvars = {
						jquery: "true"
					};
					
					/* Get translated label text strings */
					var labels = [];
					var tl8_srch = [];		
					var tl8_repl = [];		
					';
					
			foreach ($this->TL8Strings as $id => $text)
			{
	        	echo 'tl8_srch[tl8_srch.length] = "'.$id.'";'."\n";
	        	echo 'tl8_repl[tl8_repl.length] = "'.$text.'";'."\n";
			}
			
			$postParams = '';
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$postParams = '
					loginID: "'.$this->myDBaseObj->loginID.'",';
				echo '
					postvars.loginID = "'.$this->myDBaseObj->loginID.'";';
			}
					
			echo '
				  	jQuery("#stageshow-validate-table").find(".stageshow_tl8").each
				  	(
					  	function() 
					  	{
					  		{
	        					labels[labels.length] = "#"+this.id;				
	        					labels[labels.length] = this.textContent;				
							}
							
	   					}
   					);
					
					postvars.nonce = "'.$ourNOnce.'";
					postvars.TxnId = TxnId;
					postvars.perfID = perfID;
					postvars.location = location;
					postvars.validatesalebutton = true;
					
					/* Get Validation Result from Server */
					var url = "'.$jQueryURL.'";
				    jQuery.post(url,
					    {
					      nonce: "'.$ourNOnce.'",
					      TxnId: TxnId,
					      perfID: perfID,
					      location: location,'.$postParams.'
					      validatesalebutton: true,
					      jquery: "true"
					    },
					    function(data,status)
					    {
							divElem = jQuery("#stageshow-validate-table");
							divElem.html(data);
							
							/* Move .updated and .error alert boxes. Do not move boxes designed to be inline. */
							/* Code copied from wp-admin\js\common.js */
							/*
							jQuery("div.wrap h2:first").nextAll("div.updated, div.error").addClass("below-h2");
							jQuery("div.updated, div.error").not(".below-h2, .inline").insertAfter( $("div.wrap h2:first") );
							*/
							
							for (var index=0; index<labels.length; index +=2)
							{
								jQuery(labels[index]).text(labels[index+1]);
							}
							
							/* Apply translations to any message */
							messageElem = jQuery(".stageshow-validate-message");
							messageHtml = messageElem.html();
							for (var index=0; index<tl8_srch.length; index++)
							{
								messageHtml = messageHtml.replace(tl8_srch[index], tl8_repl[index]);
							}
							messageElem.html(messageHtml);

							/* Set Cursor to Normal and Enable All UI Buttons */
							StageShowLib_SetBusy(false, "stageshow-tools-ui");
					    	jQuery("#jqueryvalidatebutton").focus();
					    	setTimeout(stageshow_set_txnid_focus, '.STAGESHOW_VALIDATERESULT_TIMEOUT.');
					    }
				    );
				    
				}
			</script>
			';
			
			if(isset($_REQUEST['validatesalebutton']))
			{
				$this->ValidateSale($this->env, $perfID);
			}
			else
			{
				echo $this->SaleSummaryTable($this->env, array());
				echo $this->ShowValidation($this->env);	
			}
			echo '
			</table>
			</div>
			<!--</form>-->
			';
		}

		function ShowValidation($env, $ticketDetails = null)
		{
			return '';
		}
		
		function LogValidation($ticketDetails)
		{
		}

		function GetValidateReqTxnid($TxnId)
		{
			if (preg_match('/^([A-Z0-9]*)$/i', $TxnId) != 1)
			{
				return 0;
			}
		
			$ticketDetails = new stdClass();
			$ticketDetails->saleID = 0;
			$ticketDetails->txnId = $TxnId;
			
			return $ticketDetails;
		}

		function ValidateSale($env, $perfID)
		{
			$myDBaseObj = $this->myDBaseObj;
				 
			$myDBaseObj->CheckAdminReferer();
			$TxnId = trim(stripslashes($_REQUEST['TxnId']));
			$ticketDetails = $this->GetValidateReqTxnid($TxnId);
			if ($ticketDetails == null)
				return 0;
				
			$verifyMessageHTML = '';
			$saleDetailsHTML = '';
			$ticketsListTableHTML = '';
			$validatedMessageHTML = '';
			
			$validateMsg = $this->TranslatedText('Sale Validation', $this->myDomain).' ('.$this->TranslatedText('Sale Reference', $this->myDomain).': '.$TxnId.') - ';
			$msgClass = '';
			$showDetails = true;
			
			if (strlen($ticketDetails->txnId) == 0) return 0;
			
			$ticketsList = $results = $myDBaseObj->GetAllSalesListBySaleTxnId($ticketDetails);
			
			$entryCount = count($results);
			if ($entryCount == 0)
			{
				$validateMsg .= $this->TranslatedText('No matching record', $this->myDomain);
				$msgClass = 'stageshow-validate-notfound';							}
			else
			{
				$ticketDetails->saleID = $results[0]->saleID;
			 
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
					$validateMsg .= $this->TranslatedText('Wrong Performance', $this->myDomain);
					$msgClass = 'stageshow-validate-wrongperf error alert';
					
					$results = $ticketsList;
					$ticketDetails->saleID = 0;
					$salerecord = $results[0];
				}	
				else
				{
					$ticketDetails->perfID = $salerecord->perfID;
					$validatedMessageHTML = $this->ShowValidation($env, $ticketDetails);
					if (($validatedMessageHTML != '') && (STAGESHOW_VERIFYLOG_DUPLICATEACTION != 'ignore'))
					{
						$validateMsg .= $this->TranslatedText('Already Verified', $this->myDomain);
						$msgClass = "error stageshow-validate-duplicated";

						if (STAGESHOW_VERIFYLOG_DUPLICATEACTION == 'hide')
							$showDetails = false;					
					}
					else
					{
						$this->LogValidation($ticketDetails);
						
						$validateMsg .= $this->TranslatedText('Matching record found', $this->myDomain);
						switch($salerecord->saleStatus)
						{
							case PAYMENT_API_SALESTATUS_COMPLETED:
								$msgClass = 'stageshow-validate-ok updated ok';
								break;
									
							case PAYMENT_API_SALESTATUS_RESERVED:
								$msgClass = 'stageshow-validate-reserved error alert';
								$validateMsg .= ' - '.$this->TranslatedText('Sale Status', $this->myDomain).' '.__($salerecord->saleStatus, $this->myDomain);
								break;
									
							default:
								$msgClass = 'stageshow-validate-unknown error';
								$validateMsg .= ' - '.$this->TranslatedText('Sale Status', $this->myDomain).' '.__($salerecord->saleStatus, $this->myDomain);
								break;
									
						}
					}
				}	
			}
			
					
			$verifyMessageHTML = '<tr><td colspan="2"><div id="message" class="inline stageshow-validate-message '.$msgClass.'"><p>'.$validateMsg.'</p></div></td></tr>'."\n";
			
			$ticketsListTableHTML = $this->SaleSummaryTable($env, $results);
				
			if ($ticketsListTableHTML == '')
			{
				$validatedMessageHTML = $this->ShowValidation($env);	
			}
			
			echo "<table class='stageshow-validate-results'>\n";
			echo $verifyMessageHTML;
			echo $saleDetailsHTML;
			echo $ticketsListTableHTML;
			echo $validatedMessageHTML;
			echo "</table>\n";
						 
			return $ticketDetails->saleID;
		}
		
		function SaleSummaryTable($env, $results)
		{
			if (count($results)>0)
			{
				$salerecord = reset($results);
				$ticketsListTableHTML = '<tr><td class="stageshow_tl8" id="label_Name">'.__('Name', $this->myDomain).':</td><td id="value_Name">'.$salerecord->saleFirstName.' '.$salerecord->saleLastName.'</td></tr>'."\n";
				$ticketsListTableHTML .= '<tr><td class="stageshow_tl8" id="label_Sale_Status">'.__('Sale Status', $this->myDomain).':</td><td id="value_Sale_Status">'.__($salerecord->saleStatus, $this->myDomain).'</td></tr>'."\n";
			}
			else
			{
				$ticketsListTableHTML = '<tr class="stageshow-hidden-table"><td class="stageshow_tl8" id="label_Name">'.__('Name', $this->myDomain).':</td><td></td></tr>'."\n";
				$ticketsListTableHTML .= '<tr class="stageshow-hidden-table"><td class="stageshow_tl8" id="label_Sale_Status">'.__('Sale Status', $this->myDomain).':</td><td></td></tr>'."\n";
			}
			
			if ((count($results)>0) && ($salerecord->saleStatus == PAYMENT_API_SALESTATUS_RESERVED))
			{
				$ticketsListTableHTML .= '<tr><td class="stageshow_tl8" id="label_Total_Due">'.__('Total Due', $this->myDomain).':</td><td id="value_Total_Due">'.$salerecord->salePaid.'</td></tr>'."\n";
			}
			else
			{
				$ticketsListTableHTML .= '<tr class="stageshow-hidden-table"><td class="stageshow_tl8" id="label_Total_Due">'.__('Total Due', $this->myDomain).':</td><td></td></tr>'."\n";
			}

			ob_start();
			$classId = STAGESHOW_PLUGIN_NAME.'SalesAdminDetailsListClass';
			$salesList = new $classId($env);
			$salesList->blankTableClass = "stageshow-hidden-table";	
						
			echo '<tr><td colspan="2">'."\n";
			$salesList->OutputList($results);	
			echo "</td></tr>\n";				
			$ticketsListTableHTML .= ob_get_contents();
			ob_end_clean();
			
			return $ticketsListTableHTML;
		}
		
	}
}

?>