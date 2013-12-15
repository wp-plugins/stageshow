<?php

include 'stageshowlib_paypalsimulator.php';

if (!class_exists('StageShowPayPalSimulator')) 
{
	class StageShowPayPalSimulator extends PayPalSimulator
	{
		function __construct($notifyDBaseClass, $saleId = 0) 
		{
			parent::__construct($notifyDBaseClass, $saleId);
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
			
			
			$this->totalSale += ($result->priceValue * $result->ticketQty * $result->priceNoOfSeats);
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