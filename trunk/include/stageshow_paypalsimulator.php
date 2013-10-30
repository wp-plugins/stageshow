<?php

include 'stageshowlib_paypalsimulator.php';

if (!class_exists('StageShowPayPalSimulator')) 
{
	class StageShowPayPalSimulator extends StageShowLibPayPalSimulator
	{
		function __construct($saleId = 0) 
		{
			parent::__construct();
			
			$notifyDBaseClass = STAGESHOW_DBASE_CLASS;
			$this->myDBaseObj = new $notifyDBaseClass(__FILE__);
			$this->totalSale = 0.00;

			$formHTML = '';
			
			$formHTML .= $this->OutputHeader();

			$devMode = true;
			if (isset($_GET['id']))
			{
				$saleId = $_GET['id'];
				$devMode = false;
			}
			elseif (isset($_POST['id']))
			{
				$saleId = $_POST['id'];
			}
				
			if ($saleId > 0)
			{
				$notifyURL = $this->GetNotifyURL();
				$actionHTML = ($notifyURL != '') ? 'action="'.$notifyURL.'" ' : '';
				$formHTML .=  '<form name="ipntest" '.$actionHTML.' method="post">';			
				$formHTML .=  $this->OutputSaleForm($saleId);
			}
			else
			{
				$formHTML .=  '<form name="ipntest" method="post">';			
				$formHTML .=  $this->OutputSaleSelect(); 
			}
			$formHTML .=  '</form>';			
			
			echo $formHTML;
	    }

		function GetNotifyURL() 
		{
			return '';
	    }

		function OutputHeader() 
		{
	    }

		function OutputFooter() 
		{
	    }

		function OutputItemsTableHeader() 
		{
			$html  = '';
			$html .= '
			<div>
			<table  class="stageshow-simulator-detailstable">
				<tr class="stageshow-simulator-detailsrow">
					<td class="stageshow-simulator-datetime">Date & Time</td>
					<td class="stageshow-simulator-type">Ticket Type</td>
					<td class="stageshow-simulator-seat">Seat</td>
					<td class="stageshow-simulator-price">Price</td>
					<td class="stageshow-simulator-qty">Qty</td>
				</tr>
			';
			
			return $html;    
	    }
		
		function OutputItemsTableRow($indexNo, $result) 
		{
			$html = '<tr class="stageshow-simulator-detailsrow">';
			
			$description = $result->showName.' - '.$this->myDBaseObj->FormatDateForDisplay($result->perfDateTime);
			$reference = $result->showID.'-'.$result->perfID;
			$seat = isset($result->ticketSeat) ? $result->ticketSeat : 'N/A';		

			$html .= '<td class="stageshow-simulator-datetime" >'.$this->myDBaseObj->FormatDateForDisplay($result->perfDateTime).'</td>';
			$html .= '<td class="stageshow-simulator-type" >'.$result->ticketType.'</td>';
			$html .= '<td class="stageshow-simulator-seat" >'.$seat.'</td>';
			$html .= '<td class="stageshow-simulator-price" >'.$result->priceValue.'</td>';
			$html .= '<td class="stageshow-simulator-qty" >';
			
			$html .= '
				<input type="hidden" name="quantity'.$indexNo.'" value="'.$result->ticketQty.'"/>
			';
			
			
			$this->totalSale += ($result->priceValue * $result->ticketQty);
			$html .= $result->ticketQty;
			$customVal = $result->saleID;
				
			$html .= '
				</td>
			</tr>';
				
			return $html;    
	    }
		
		function OutputItemsTable($results) 
		{
			if (count($results) == 0) return '';
			
			$html  = "<h2>".$results[0]->showName."</h2>\n";
			$html .= parent::OutputItemsTable($results);
			return $html;
		}		

	}
}

?>