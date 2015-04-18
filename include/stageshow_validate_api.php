<?php
/* 
Description: StageShow-Plus extension for StageShow Plugin 
 
Copyright 2014 Malcolm Shergold, Corondeck Ltd. All rights reserved.

You must be a registered user to use this software
*/

include STAGESHOW_INCLUDE_PATH.'stageshowlib_dbase_base.php';
include STAGESHOW_INCLUDE_PATH.'stageshowlib_gatewaybase.php';

if (!class_exists('StageShowWPOrgValidateDBaseClass')) 
{
	class StageShowWPOrgValidateDBaseClass extends StageShowLibGenericDBaseClass
	{
		function get_domain()
		{
			// This function returns a default profile (for translations)
			return 'stageshow';
		}
		
		function GetLocation()
		{
			return '';
		}
		
		function CheckAdminReferer($referer = '')
		{
			return true;
		}
	
		function GetActivePerformancesFilter()
		{
			$timeNow = current_time('mysql');
			$sqlWhere = ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime>"'.$timeNow.'" ';
			return $sqlWhere;
		}
		
		function GetActivePerformancesList()
		{
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			
			// Add SQL filter(s)
			$sql .= $this->GetActivePerformancesFilter();
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
				
		function GetPerformancesListByPerfID($perfID)
		{
			$sql  = 'SELECT showName, perfDateTime FROM '.STAGESHOW_PERFORMANCES_TABLE.' ';
			$sql .= 'JOIN '.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID ';
			$sql .= 'WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$perfID.'"';
				
			// Get results
			$perfEntry = $this->get_results($sql);	
					
			return $perfEntry;
		}

		function GetJoinedTables($sqlFilters = null, $classID = '')
		{
			return '';
		}
		
		function GetAllSalesListBySaleTxnId($saleTxnId)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_SALES_TABLE.' ';
			$sql .= 'JOIN '.STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.saleID='.STAGESHOW_SALES_TABLE.'.saleID ';
			$sql .= 'JOIN '.STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID ';
			$sql .= 'JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID ';
			$sql .= 'JOIN '.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID ';

			$sql .= $this->GetJoinedTables();
			$sql .= 'WHERE '.STAGESHOW_SALES_TABLE.'.saleTxnId="'.$saleTxnId.'" ';
			$sql .= 'AND (('.STAGESHOW_PERFORMANCES_TABLE.'.perfState IS NULL) OR ('.STAGESHOW_PERFORMANCES_TABLE.'.perfState<>"deleted")) ';
			$sql .= 'AND '.STAGESHOW_SHOWS_TABLE.'.showState<>"deleted"';		
				
			// Get results
			$salesListArray = $this->get_results($sql);
					
			return $salesListArray;
		}	
		
	}
	
}

?>