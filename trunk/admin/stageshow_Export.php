<?php
/* 
Description: Code for Data Export functionality
 
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

include '../../../../wp-config.php';
  
if (!class_exists('StageShowExportAdminClass')) 
{
	class StageShowExportAdminClass  // Define class
	{
		var $myDBaseObj;
		
		function __construct($myDBaseObj) //constructor	
		{
			$this->myDBaseObj = $myDBaseObj;

	  		// FUNCTIONALITY: Export - Settings, Tickets or Summary
			if ( isset( $_GET['downloadexport'] ) )
			{
				$this->filename = 'stageshow.txt';	
							
				if ( isset( $_GET['download'] ) ) 
				{
					switch ($_GET['sshow_ex_type'])
					{          
						case 'settings':
								if (!current_user_can(STAGESHOW_CAPABILITY_ADMINUSER)) die("Access Denied"); 
								$this->ouput_downloadHeader();
								$this->export_shows();
								break;
			          
						case 'tickets':
								$this->ouput_downloadHeader();
								$this->export_tickets();
								break;          

						case 'summary':
								$this->ouput_downloadHeader();
								$this->export_summary();
								break;
								
						case 'all':
						default :
								$this->ouput_downloadHeader();
								$this->export_shows();
								$this->export_tickets();
								break;
					}
				}			       
			}
			else if ( isset( $_GET['downloadvalidator'] ) )
			{
				$this->filename = 'stageshowValidator.html';
				
				$this->ouput_downloadHeader();
				$this->output_htmlhead();
				$this->export_tickets(true);
				$this->output_endhtmlhead();
				$this->ouput_downloadFooter(true);
			}
			else
				die;
		}

		function header($content)
		{
			if ( $this->myDBaseObj->isOptionSet('Dev_ShowSQL')
				|| $this->myDBaseObj->isOptionSet('Dev_ShowDBOutput') )
				echo $content."<br>\n";
			else
				header($content);				
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
		
		function ouput_downloadHeader()
		{
			$this->header( 'Content-Description: File Transfer' );
			$this->header( 'Content-Disposition: attachment; filename=' . $this->filename );
			$this->header( 'Content-Type: text/html; charset=utf-8' );	
		}

		function ouput_downloadFooter()
		{
			echo '
<body>
<h3>Validate Sale</h3>
<table class="form-table">
	<tr>
		<th>Transaction ID</th>
		<td>
			<input type="text" maxlength="20" size="20" name="TxnId" id="TxnId" value="" autocomplete="off" />
		</td>
	</tr>
</table>
<p>
<p class="submit">
<input class="button-secondary" type="button" name="validatesalebutton" onClick=onclickverify(this) value="Validate"/>
<br>
<div id="VerifyResult" name="VerifyResult"></div>
</body>
</html>';
		}

		function export_sshow($dbList, $exportHTML = false)
		{
			$header = '';
			$line = '';
			
			foreach($dbList as $dbEntry)
			{
				$header = '';
				if ($exportHTML) 
				{
					$header .= '"';
					$line .= '"';
				}
				
				foreach ($dbEntry as $key => $option)
				{
					$option = str_replace("\r\n",",",$option);	
					$option = str_replace("\r",",",$option);	
					$option = str_replace("\n",",",$option);	// Remove any CRs in the db entry ... i.e. in Address Fields
					
					$header .= "$key\t";
					$line .= "$option\t";
				}
				
				if ($exportHTML) 
				{
					$header .= '",';
					$line .= '",';
				}
				$header .= "\n";
				$line .= "\n";
				}
			
			echo $header.$line;
		}

		function export_shows()
		{
			$this->export_sshow($this->myDBaseObj->GetPricesList(null));
		}

		function export_tickets($exportHTML = false)
		{			
			if ($exportHTML)
				echo 'var ticketDataList = new Array
(
';
				
			$this->export_sshow($this->myDBaseObj->GetSalesList(null), $exportHTML);
			if (!$exportHTML)
				return;
			
			echo '				
"");
			
var columnFields = new Array();

function onclickverify(obj) 
{
	var ourTxnId = document.getElementById("TxnId").value;
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
				// salePaid	ticketName	ticketType	ticketQty	ticketSeat	perfID	priceType	priceRef	priceValue	showID	perfState	perfDateTime	perfRef	perfSeats	perfPayPalButtonID	perfOpens	perfExpires	perfNote	perfNotePosn	showName	showNote	showState	showOpens	showExpires	showEMail	",
			
				verifyResult += "<table>";
				
				verifyResult += "<tr><td>Name:</td><td>" + ticketDataArray[columnFields["saleName"]] + "</td></tr>\n"; 
				verifyResult += "<tr><td>EMail:</td><td>" + ticketDataArray[columnFields["saleEMail"]] + "</td></tr>\n"; 
				verifyResult += "<tr><td>Date & Time:</td><td>" + ticketDataArray[columnFields["saleDateTime"]] + "</td></tr>\n"; 
				
				verifyResult += "</table><br><table class=table_verify>\n"; 
				
				verifyResult += "<tr>"; 
				verifyResult += "<th class=col_show>Show</th>"; 
				verifyResult += "<th class=col_type>Type</th>"; 
				verifyResult += "<th class=col_price>Price</th>"; 
				verifyResult += "<th class=col_qty>Qty</th>"; 
				
				verifyResult += "</tr>\n"; 
			}
			
			verifyResult += "<tr>"; 
			verifyResult += "<td class=col_ticketName>" + ticketDataArray[columnFields["ticketName"]] + "</td>"; 
			verifyResult += "<td class=col_ticketType>" + ticketDataArray[columnFields["ticketType"]] + "</td>"; 
			verifyResult += "<td class=col_ticketprice>" + "TBD" + "</td>"; 
			verifyResult += "<td class=col_ticketQty>" + ticketDataArray[columnFields["ticketQty"]] + "</td>\n"; 
			verifyResult += "</tr>\n"; 
		}
	}
	
	
	if (matchedLines > 0)
		verifyResult += "<table>";
	
	document.getElementById("VerifyResult").innerHTML = verifyResult;
	
	//alert("Verifying - TxnId:" + ourTxnId + " matched " + matchedLines + " Lines");
	
}
';			
		}

		function export_summary()
		{
			$accumList = array();
			
			// Get All Sales - Sort by Show Name, then Performance Date/Time, then by Performance ID, then by Buyer Name, then Sale EMail
			
			// Get list of ticket types for all shows
			$typesList = $this->myDBaseObj->GetAllTicketTypes();
					
			$showLists = $this->myDBaseObj->GetAllShowsList();
			foreach ($showLists as $showEntry)
			{
				$perfsLists = $this->myDBaseObj->GetPerformancesListByShowID($showEntry->showID);
				foreach ($perfsLists as $perfsList)
				{
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
							$saleRec->saleName = $thisSale->saleName;
							$saleRec->ticketQty = $thisSale->ticketQty;
							
							foreach ($typesList as  $typeRec)
							{
								$typeName = $typeRec->priceType;
								$saleRec->$typeName = 0;
							}
							
							$fieldsList = array('ticketName', 'saleEMail', 'saleDateTime', 'saleTxnId');
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
			
			$this->export_sshow($accumList);
		}
	}
}

$stageShowDBaseClass = STAGESHOW_DBASE_CLASS;
new StageShowExportAdminClass(new $stageShowDBaseClass(__FILE__));

?>
