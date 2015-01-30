<?php
/* 
Description: Code for Data Export functionality
 
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

include '../include/stageshowlib_export.php';

if (!class_exists('StageShowWPOrgTDTExportAdminClass')) 
{
	class StageShowWPOrgTDTExportAdminClass extends StageShowLibExportAdminClass  // Define class
	{
		function __construct($myDBaseObj) //constructor	
		{
			parent::__construct($myDBaseObj);
			
	  		// FUNCTIONALITY: Export - Settings, Tickets or Summary
			if ( isset( $_POST['downloadexport'] ) )
			{
				if ( isset( $_POST['download'] ) ) 
				{
					$this->fieldNames = $this->GetFields();
	
					$perfID = 0;					
					$showID = StageShowLibHTTPIO::GetRequestedInt('export_showid', 0);
					if ($showID != 0) 
					{
						$showAndperfID = StageShowLibHTTPIO::GetRequestedInt('export_perfid', 0);
						if ($showAndperfID != 0)
						{
							$showAndperfIDParts = explode('.', $showAndperfID);
							$perfID = $showAndperfIDParts[1];
						}
					}
					
					switch ($_POST['export_type'])
					{          
						case 'settings':
								if (!current_user_can(STAGESHOWLIB_CAPABILITY_ADMINUSER)) die("Access Denied"); 
								$this->fileName = 'stageshow-settings';
								$this->output_downloadHeader('text/tab-separated-values');
								$this->export_shows();
								break;
			          
						case 'tickets':
								$this->fileName = 'stageshow-tickets';
								$this->output_downloadHeader('text/tab-separated-values');
								$this->export_tickets($showID, $perfID);
								break;          

						case 'summary':
								$this->fileName = 'stageshow-summary';
								$this->output_downloadHeader('text/tab-separated-values');
								$this->export_summary($showID, $perfID);
								break;								
					}
				}			       
			}
			else if ( isset( $_POST['downloadvalidator'] ) )
			{
				$validatorFields = 'saleTxnId,saleStatus,saleFirstName,saleLastName,showName,perfDateTime,priceType,zoneRef,ticketSeat,ticketQty,priceNoOfSeats,ticketPaid,ticketFee,ticketCharge,saleDateTime,perfRef,verifyLocation,verifyDateTime';
				$this->fieldNames = $this->SelectFields($validatorFields);
	
				$this->fileName = 'stageshowValidator';
				$this->fileExtn = 'html';			
				
				$this->output_downloadHeader('text/html');
				$this->output_htmlhead();
				$this->export_validator();
				$this->output_endhtmlhead();
				$this->ouput_downloadFooter(true);
			}
			else
				die;
		}

		function GetFields()
		{	
			$gatewayName = $this->myDBaseObj->gatewayObj->GetName();
			$fieldNames = array(
				'perfDateTime'       => __('Performance Date & Time', $this->myDomain),
				'perfID'             => __('Performance ID', $this->myDomain),
				'perfRef'            => __('Performance Ref', $this->myDomain),
				'perfSeats'          => __('Performance Seats', $this->myDomain),
				'perfState'          => __('Performance State', $this->myDomain),
				'planID'             => __('Plan ID', $this->myDomain),
				'planRef'            => __('Plan Ref', $this->myDomain),
				'presetID'           => __('Preset ID', $this->myDomain),
				'priceID'            => __('Price ID', $this->myDomain),
				'priceType'          => __('Price Type', $this->myDomain),
				'priceValue'         => __('Price', $this->myDomain),
				'saleCheckoutTime'   => __('Checkout Time', $this->myDomain),
				'saleDateTime'       => __('Date & Time', $this->myDomain),
				'saleEMail'          => __('Sale EMail', $this->myDomain),
				'saleTransactionFee' => __('Booking Fee', $this->myDomain),
				'saleFee'            => $gatewayName.' '.__('Fees', $this->myDomain),
				'saleDonation'       => __('Donation', $this->myDomain),
				'salePostage'        => __('Postage', $this->myDomain),
				'saleID'             => __('Sale ID', $this->myDomain),
				'saleFirstName'      => __('First Name', $this->myDomain),
				'saleLastName'       => __('Last Name', $this->myDomain),
				'salePaid'           => __('Paid', $this->myDomain),
				'salePPCity'         => __('City', $this->myDomain),
				'salePPCountry'      => __('Country', $this->myDomain),
				'salePPName'         => __('Name', $this->myDomain),
				'salePPPhone'        => __('Phone', $this->myDomain),
				'salePPState'        => __('County', $this->myDomain),
				'salePPStreet'       => __('Street', $this->myDomain),
				'salePPZip'          => __('Postcode', $this->myDomain),
				'saleStatus'         => __('Sale Status', $this->myDomain),
				'saleTxnId'          => __('Transaction ID', $this->myDomain),
				'saleNoteToSeller'   => __('Note To Seller', $this->myDomain),
				'showEMail'          => __('Show EMail', $this->myDomain),
				'showExpires'        => __('Show Expires', $this->myDomain),
				'showID'             => __('Show ID', $this->myDomain),
				'showName'           => __('Show Name', $this->myDomain),
				'showNote'           => __('Show Note', $this->myDomain),
				'showOpens'          => __('Show Opens', $this->myDomain),
				'showState'          => __('Show State', $this->myDomain),
				'ticketID'           => __('Ticket ID', $this->myDomain),
				'ticketCharge'       => __('Ticket Charge', $this->myDomain),
				'ticketFee'          => __('Ticket Fee', $this->myDomain),
				'ticketPaid'         => __('Ticket Paid', $this->myDomain),
				'ticketPostage'      => __('Ticket Postage', $this->myDomain),
				'ticketQty'          => __('Ticket Qty', $this->myDomain),
				'verifyDateTime'     => __('Verify Date & Time', $this->myDomain),
				'verifyID'           => __('Verify ID', $this->myDomain),
				'verifyLocation'     => __('Verify Location', $this->myDomain),
			);
			
			return array_merge(parent::GetFields(), $fieldNames);
		}
			
		function GetValidatorTableFields()
		{			
			return array (
				'showName' => 'show',
				'perfDateTime' => 'performance',
				'priceType' => 'type',
				'ticketQty' => 'qty',
			);
		}

		function output_htmlhead()
		{
			echo '<html>
<head>
<title>StageShow Validator</title>
<meta http-equiv="Content-Type" content="text/html;">
<link href="css/style.css" rel="stylesheet" type="text/css">
<style>

.table_verify td, .table_verify th 
{
	padding: 10px;
	text-align: left;
	vertical-align: top;
	
	border-bottom-color: #DFDFDF;
	border-top-color: #FFFFFF;
	
	background-color: #F9F9F9;
	XXXbackground-image: -moz-linear-gradient(center top , #F9F9F9, #ECECEC);
}	

td.col_show
{
	width: 400px;
}

td.col_performance
{
	width: 400px;
}

.table_verify th 
{
	border-top-radius: 20px;
	Xborder-top-left-radius: 3px;
	background-color: #F1F1F1;
	background-image: -moz-linear-gradient(center top , #F9F9F9, #ECECEC);
}

.table_verify td 
{
	border-style: solid;
	border-width: 0px 0px 1px 0px;
}

</style>
<script language="JavaScript">
<!-- Hide script from old browsers
';
		}
		
		function output_endhtmlhead()
		{
			echo '		
// End of Hide script from old browsers -->
</script>
</head>
';
		}
		
		function ouput_downloadFooter()
		{
			echo '
<body>
<h2>'.__('Validate Sale', $this->myDomain).'</h2>
<table class="stageshow-form-table">
	<tr>
		<th>'.__('Transaction ID', $this->myDomain).'</th>
		<td>
			<input type="text" maxlength="20" size="20" name="TxnId" id="TxnId" value="" onkeypress="onKeyPress(event)" autocomplete="off" />
		</td>
	</tr>
</table>
<p>
<p class="submit">
<input class="button-secondary" type="button" name="validatesalebutton" onClick=onclickverify(this) value="'.__('Validate', $this->myDomain).'"/>
<br>
<div id="VerifyResult" name="VerifyResult"></div>
</body>
</html>';
		}

		function export_shows()
		{
			$this->exportDB($this->myDBaseObj->GetShowsSettings());
		}

		function export_tickets($showID, $perfID)
		{			
			if ($showID !=0)
				$sqlFilters['showID'] = $showID;
					
			if ($perfID !=0)
				$sqlFilters['perfID'] = $perfID;
				
			$sqlFilters['addTicketFee'] = true;
			$this->exportDB($this->myDBaseObj->GetSalesList($sqlFilters));
		}

		function export_validator($showID=0, $perfID=0)
		{			
			echo 'var ticketDataList = new Array
(
';

			if ($showID !=0)
				$sqlFilters['showID'] = $showID;
					
			if ($perfID !=0)
				$sqlFilters['perfID'] = $perfID;
				
			$sqlFilters['addTicketFee'] = true;
			$this->exportDB($this->myDBaseObj->GetSalesList($sqlFilters), true);
			
			$tableFields = $this->GetValidatorTableFields();
			
			echo '				
"");
			
var columnFields = new Array();
var verifysList = new Array();

window.onload = onPageLoad;

function onPageLoad(obj) 
{
	// Set initial focus to TxnId edit box
	var ourTxnIdObj = document.getElementById("TxnId");
	ourTxnIdObj.focus();
}
	
function onKeyPress(obj) 
{
	if (obj.keyCode == 13)
	{
		VerifyTxnId();
	}
}

function onclickverify(obj) 
{
	VerifyTxnId();
}
	
function LogVerified(index) 
{
	var VerifiesList = "";
	
	try
	{
		var timeNow = new Date();		
		if (typeof verifysList[index] === "undefined")	
		{
			verifysList[index] = "";
		}
			
		VerifiesList = verifysList[index];
		verifysList[index] += timeNow.toLocaleString() + "<br> ";	
	}
	catch (err)
	{
	}
	
	return VerifiesList;
		
}
	
function VerifyTxnId() 
{
	var ourTxnIdObj = document.getElementById("TxnId");
	var ourTxnId = ourTxnIdObj.value.trim();
	var matchedLines = 0;
	var verifyResult = "";
	
	//alert("Verifying - TxnId:" + ourTxnId);
	
	for (var index = 0; index < ticketDataList.length; index++)
	{
		var nextLine = ticketDataList[index];
		ticketDataArray = nextLine.split("\t");
		if (index == 0) 
		{
			// First line ... just index the column field IDs
			for (var fieldNo = 0; fieldNo < ticketDataArray.length; fieldNo++)
			{
				fieldId = ticketDataArray[fieldNo];
				columnFields[fieldId] = fieldNo;
			}
		}
		else
		{
			var thisTxnId = ticketDataArray[columnFields["saleTxnId"]];
			if (thisTxnId != ourTxnId)
				continue;
			matchedLines++;
			
			if (matchedLines == 1)
			{
				verifyHistory = LogVerified(ourTxnId);
				if (verifyHistory !== "")
				{
					verifyResult += "<h3>'.__('History', $this->myDomain).':</h3>";		
					verifyResult += verifyHistory;		
					verifyResult += "<br>";		
				}		
			
				verifyResult += "<h3>'.__('Sale Details', $this->myDomain).':</h3>";		
				verifyResult += "<table>";
				
				verifyResult += "<tr><td>'.__("TxnId", $this->myDomain).':</td><td>" + ourTxnId + "</td></tr>\n"; 
				verifyResult += "<tr><td>'.__("Name", $this->myDomain).':</td><td>" + ticketDataArray[columnFields["saleFirstName"]] + " " + ticketDataArray[columnFields["saleLastName"]] + "</td></tr>\n"; 
				verifyResult += "<tr><td>'.__("Date & Time", $this->myDomain).':</td><td>" + ticketDataArray[columnFields["saleDateTime"]] + "</td></tr>\n"; 
				
				verifyResult += "</table><br><table class=table_verify>\n"; 
				
				verifyResult += "<tr>"; 
';			
			foreach ($tableFields as $tableField => $tableClass)
			{
				$colClass = 'col_'.$tableClass;
				$colTitle = $this->fieldNames[$tableField];
				echo '
				verifyResult += "<th class='.$colClass.'>'.__($colTitle, $this->myDomain).'</th>";';
			}
echo '				 				
				verifyResult += "</tr>\n"; 
			}
			
			verifyResult += "<tr>"; 
';			
			foreach ($tableFields as $tableField => $tableClass)
			{
				$colClass = 'ticket_'.$tableClass;
				$colTitle = $this->fieldNames[$tableField];
				echo '
			verifyResult += "<td class='.$colClass.'>" + ticketDataArray[columnFields["'.$tableField.'"]] + "</td>"; ';
			}
echo '				 				
			verifyResult += "</tr>\n"; 
		}
	}
	
	if (matchedLines > 0)
	{
		ourTxnIdObj.value = "";
		verifyResult += "<table>";
	}
	else
	{
		ourTxnIdObj.select();
		verifyResult += "'.__("No matching record found", $this->myDomain).'<br>";
	}
		
	document.getElementById("VerifyResult").innerHTML = verifyResult;
	
	// Set focus to TxnId edit box
	ourTxnIdObj.focus();
}
';			
		}

		function export_summary($showID=0, $perfID=0)
		{
			$accumList = array();
			
			// Get All Sales - Sort by Show Name, then Performance Date/Time, then by Performance ID, then by Buyer Name, then Sale EMail

			// Get list of ticket types for all shows
			$typesList = $this->myDBaseObj->GetAllTicketTypes();
			
			// Add ticket name to array created by GetFields()
			$this->fieldNames = array_merge($this->fieldNames, array('ticketName' => __('Ticket Name', $this->myDomain)));
			
			// Add custom ticket type name to array created by GetFields()
			foreach ($typesList as $typeRec)	
			{
				$typeName = $typeRec->priceType;
				$this->fieldNames = array_merge($this->fieldNames, array($typeName => $typeName));
			}
			
			$showLists = $this->myDBaseObj->GetAllShowsList();
			foreach ($showLists as $showEntry)
			{
				if (($showID !=0) && ($showEntry->showID != $showID))
					continue;					
					
				$perfsLists = $this->myDBaseObj->GetPerformancesListByShowID($showEntry->showID);
				foreach ($perfsLists as $perfsList)
				{
					if (($perfID !=0) && ($perfsList->perfID != $perfID))
						continue;					

					// Get all sales for this performance
					$salesList = $this->myDBaseObj->GetTicketsListByPerfID($perfsList->perfID);
					$lastSaleID = 0;
					
					foreach ($salesList as $thisSale)
					{
						$ticketType = $thisSale->ticketType;

						if ($lastSaleID == $thisSale->saleID)
						{
							$lastSaleIndex = count($accumList) - 1;					
							$saleRec = $accumList[$lastSaleIndex];
							
							$saleRec->ticketQty += $thisSale->ticketQty;
							$saleRec->$ticketType += $thisSale->ticketQty;
							
							$accumList[$lastSaleIndex] = $saleRec;
						}
						else
						{
							$saleRec = new stdClass();
							$saleRec->saleFirstName = $thisSale->saleFirstName;
							$saleRec->saleLastName = $thisSale->saleLastName;
							$saleRec->ticketQty = $thisSale->ticketQty;
							
							foreach ($typesList as $typeRec)
							{
								$typeName = $typeRec->priceType;
								$saleRec->$typeName = 0;
							}
							
							$fieldsList = array('ticketName', 'saleEMail', 'saleDateTime', 'saleTxnId', 'saleNoteToSeller');
							foreach ($fieldsList as $fieldId)
								$saleRec->$fieldId = $thisSale->$fieldId;
								
							$saleRec->$ticketType += $thisSale->ticketQty;
							
							$noOfSales = count($accumList);
							$accumList[$noOfSales] = $saleRec;
						}
						
						$lastSaleID = $thisSale->saleID;
					}
				}
			}
	
			$this->exportDB($accumList);
		}
	}
}

?>
