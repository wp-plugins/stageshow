<?php
/* 
Description: StageShow Plugin Database Access functions
 
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

if (!defined('STAGESHOWLIB_DATABASE_FULL'))
{
	if (!class_exists('StageShowLibSalesCartDBaseClass')) 
		include STAGESHOW_INCLUDE_PATH.'stageshowlib_sales_trolley_dbase_api.php';
	
	class StageShowWPOrgCartDBaseClass_Parent extends StageShowLibSalesCartDBaseClass {}
}
else
{
	if (!class_exists('StageShowLibSalesDBaseClass')) 
		include STAGESHOW_INCLUDE_PATH.'stageshowlib_sales_dbase_api.php';
	
	class StageShowWPOrgCartDBaseClass_Parent extends StageShowLibSalesDBaseClass {}
}

if (!class_exists('StageShowWPOrgCartDBaseClass')) 
{
	// Set the DB tables names
	global $wpdb;
	
	$dbPrefix = $wpdb->prefix;
	if (defined('CORONDECK_RUNASDEMO'))
	{
		$dbPrefix .= str_replace('stageshow', 'demo_ss', STAGESHOW_DIR_NAME).'_';
	}
	else
	{
		$dbPrefix .= 'sshow_';		
	}
	
	if (defined('STAGESHOW_DATETIME_BOXOFFICE_FORMAT'))
	{
		define('STAGESHOWLIB_DATETIME_BOXOFFICE_FORMAT',STAGESHOW_DATETIME_BOXOFFICE_FORMAT);
	}

	if (!defined('STAGESHOW_TABLE_PREFIX'))
	{
		define('STAGESHOW_TABLE_PREFIX', $dbPrefix);
		
		define('STAGESHOW_SHOWS_TABLE', STAGESHOW_TABLE_PREFIX.'shows');
		define('STAGESHOW_PERFORMANCES_TABLE', STAGESHOW_TABLE_PREFIX.'perfs');
		define('STAGESHOW_PRICES_TABLE', STAGESHOW_TABLE_PREFIX.'prices');
		define('STAGESHOW_SALES_TABLE', STAGESHOW_TABLE_PREFIX.'sales');
		define('STAGESHOW_TICKETS_TABLE', STAGESHOW_TABLE_PREFIX.'tickets');

		define('STAGESHOW_DEMOLOG_TABLE', STAGESHOW_TABLE_PREFIX.'demolog');
	}
	
	if( !defined( 'STAGESHOW_DATETIME_TEXTLEN' ) )
	{
		define('STAGESHOW_DATETIME_TEXTLEN', 19);
		
		if( !defined( 'STAGESHOW_FILENAME_TEXTLEN' ) )
			define('STAGESHOW_FILENAME_TEXTLEN', 80);
		
		if( !defined( 'STAGESHOW_SHOWNAME_TEXTLEN' ) )
			define('STAGESHOW_SHOWNAME_TEXTLEN', 80);
		if( !defined( 'STAGESHOW_PERFREF_TEXTLEN' ) )
			define('STAGESHOW_PERFREF_TEXTLEN', 16);
		if( !defined( 'STAGESHOW_PRICETYPE_TEXTLEN' ) )
			define('STAGESHOW_PRICETYPE_TEXTLEN', 10);
		define('STAGESHOW_PRICEVISIBILITY_TEXTLEN', 10);	
		define('STAGESHOW_TICKETNAME_TEXTLEN', 110);
		define('STAGESHOW_TICKETTYPE_TEXTLEN', 32);
		define('STAGESHOW_TICKETSEAT_TEXTLEN', 10);
			
		define('STAGESHOW_PPLOGIN_USER_TEXTLEN', 127);
		define('STAGESHOW_PPLOGIN_PWD_TEXTLEN', 65);
		define('STAGESHOW_PPLOGIN_SIG_TEXTLEN', 65);
		define('STAGESHOW_PPLOGIN_EMAIL_TEXTLEN', 65);
		
		define('STAGESHOW_PPLOGIN_EDITLEN', 70);
		
		define('STAGESHOW_PPBUTTONID_TEXTLEN',16);
		define('STAGESHOW_ACTIVESTATE_TEXTLEN',10);

		define('STAGESHOW_TICKETNAME_DIVIDER', ' - ');

		define('STAGESHOW_STATE_ACTIVE', 'activate');
		define('STAGESHOW_STATE_INACTIVE', 'deactivate');
		define('STAGESHOW_STATE_DELETED', 'deleted');

		define('STAGESHOW_VISIBILITY_PUBLIC', 'public');
	}
	
	class StageShowWPOrgCartDBaseClass extends StageShowWPOrgCartDBaseClass_Parent // Define class
  	{
		const STAGESHOW_DATE_FORMAT = 'Y-m-d';
		
		var $perfJoined = false;
		
		function __construct($caller) //constructor	
		{
			$this->StageshowDbgoptionsName = STAGESHOW_DIR_NAME.'dbgsettings';
			
			// Options DB Field - In DEMO Mode make unique for each user, and Plugin type
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$this->StageshowOptionsName  = STAGESHOW_DIR_NAME.'settings_';
			}
			else
			{
				$this->StageshowOptionsName = 'stageshowsettings';
			}
			
			$opts = array (
				'Caller'             => $caller,
				'Domain'             => 'stageshow',
				'PluginFolder'       => STAGESHOW_FOLDER,
				'DownloadFilePath'   => '/wp-content/plugins/stageshow/stageshow_download.php',
				'CfgOptionsID'       => $this->StageshowOptionsName,
				'DbgOptionsID'       => $this->StageshowDbgoptionsName,
			);			

			if (defined('STAGESHOW_ADDBUTTON_URL'))
				$this->buttonImageURLs['add'] = STAGESHOW_ADDBUTTON_URL;
			if (defined('STAGESHOW_CHECKOUTBUTTON_URL'))
				$this->buttonImageURLs['checkout'] = STAGESHOW_CHECKOUTBUTTON_URL;
			if (defined('STAGESHOW_REMOVEBUTTON_URL'))
				$this->buttonImageURLs['remove'] = STAGESHOW_REMOVEBUTTON_URL;
			if (defined('STAGESHOW_CLOSEBUTTON_URL'))
				$this->buttonImageURLs['closewindow'] = STAGESHOW_CLOSEBUTTON_URL;
				
			// Call base constructor
			parent::__construct($opts);

			if (defined('CORONDECK_RUNASDEMO'))
			{
				$_REQUEST['loginID'] = $this->GetLoginID();
			}
		}
		
		function getTablePrefix()
		{
			return STAGESHOW_TABLE_PREFIX;
		}
		
		function getTableNames($dbPrefix)
		{
			$DBTables = parent::getTableNames($dbPrefix);
			
			$DBTables->Orders = $dbPrefix.'tickets';
			
			return $DBTables;
		}

		function init()
		{
			// This function should be called by the 'init' action of the Plugin
			// Action requiring setting of Cookies should be done here
		}
		
		function get_domain()
		{
			// This function returns the domain id (for translations) 
			// The domain is the same for all stageshow derivatives
			return 'stageshow';
		}
		
		function GetDBCredentials()
		{
			$defines = "
	if (!defined('STAGESHOW_URL'))
	{
	define('STAGESHOW_URL', '".STAGESHOW_URL."');
	}
	
	if (!defined('PAYMENT_API_SALESTATUS_COMPLETED'))
	{
	define('PAYMENT_API_SALESTATUS_COMPLETED', '".PAYMENT_API_SALESTATUS_COMPLETED."');
	define('PAYMENT_API_SALESTATUS_CHECKOUT', '".PAYMENT_API_SALESTATUS_PENDINGPPEXP."');
	define('PAYMENT_API_SALESTATUS_PENDINGPPEXP', '".PAYMENT_API_SALESTATUS_PENDINGPPEXP."');
	define('PAYMENT_API_SALESTATUS_RESERVED', '".PAYMENT_API_SALESTATUS_RESERVED."');
	define('PAYMENT_API_SALESTATUS_TIMEOUT', '".PAYMENT_API_SALESTATUS_TIMEOUT."');
	}
	
	if (!defined('STAGESHOW_TABLE_PREFIX'))
	{
	define('STAGESHOW_TABLE_PREFIX', '".STAGESHOW_TABLE_PREFIX."');
	define('STAGESHOW_SHOWS_TABLE', '".STAGESHOW_SHOWS_TABLE."');
	define('STAGESHOW_PERFORMANCES_TABLE', '".STAGESHOW_PERFORMANCES_TABLE."');
	define('STAGESHOW_PRICES_TABLE', '".STAGESHOW_PRICES_TABLE."');
	define('STAGESHOW_SALES_TABLE', '".STAGESHOW_SALES_TABLE."');
	define('STAGESHOW_TICKETS_TABLE', '".STAGESHOW_TICKETS_TABLE."');			
	}
";
							
			return parent::GetDBCredentials().$defines;
    	}
        
		function DBField($fieldName)
		{
			switch($fieldName)
			{
				case 'stockID':	    return 'priceID';
				case 'orderQty':	return 'ticketQty';
				case 'orderPaid':	return 'ticketPaid';
				default:			return $fieldName;
			}
		}
		
		function InTestMode()
		{
			if (!isset($_SESSION['stageshowlib_debug_test'])) return false;
			
			if (!file_exists(STAGESHOW_TEST_PATH.'stageshow_testsettings.php')) return false;

			if (!function_exists('wp_get_current_user')) return false;
			
			return current_user_can(STAGESHOWLIB_CAPABILITY_DEVUSER);
		}
		
		function prepareBoxOffice($showID)
		{
			if (isset($_GET['sc']))
			{
				// Output counts
				$results = $this->GetSalesListByShowID($showID);
				echo "<!-- Sh:$showID C:".count($results)." -->\n";	// TODP - Remove debug code ...
			}
		}
		
		function IsStateActive($state)
		{
			switch ($state)
			{
				case STAGESHOW_STATE_INACTIVE:
				case STAGESHOW_STATE_DELETED:
					return false;
					
				case STAGESHOW_STATE_ACTIVE:
				case 'Active':
				case '':
					return true;
			}
			
			return false;
		}

		function GetActiveShowsList()
		{
			$timeNow = current_time('mysql');
			
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			//$selectFields .= ', MAX(perfDateTime) AS maxPerfDateTime';

			$sqlFilters['groupBy'] = 'showID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';
			$sqlFilters['showState'] = STAGESHOW_STATE_ACTIVE;
			$sqlFilters['perfState'] = STAGESHOW_STATE_ACTIVE;
			
			$this->showJoined = true;
			$this->perfJoined = true;

			$sql  = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= ' AND perfDateTime>"'.$timeNow.'" ';
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$results = $this->get_results($sql, true, $sqlFilters);

			return $results;
		}
				
		function GetShowID($showName)
		{
			if ($showName == '')
			{
				$showID = 0;
			}
			else if (is_numeric($showName))
			{
				$showID = $showName;
			}
			else
			{
				$sql  = 'SELECT showID, showName FROM '.STAGESHOW_SHOWS_TABLE;
				$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="%s"';

				$values = array($showName);
				
				$showsEntries = $this->getresultsWithPrepare($sql, $values);
				$showID = (count($showsEntries) > 0) ? $showsEntries[0]->showID : 0;
			}
			
			return $showID;
		}
		
		function GetPerfID($showName, $perfDate)
		{
			if (is_numeric($perfDate))
			{
				$perfID = $perfDate;
			}
			else if (($showName == '') || ($perfDate == ''))
			{
				$perfID = 0;
			}
			else 
			{
				$showID = $this->GetShowID($showName);
				
				$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
				$sql .= " LEFT JOIN ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.STAGESHOW_SHOWS_TABLE.'.showID';
				$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID="'.$showID.'"';
				$sql .= ' AND '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime="%s"';
				
				$values = array($perfDate);
				
				$perfsEntries = $this->getresultsWithPrepare($sql, $values);
				$perfID = (count($perfsEntries) > 0) ? $perfsEntries[0]->perfID : 0;
			}
			
			return $perfID;
		}
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			$this->perfJoined = false;
			
			$results = parent::get_results($sql, $debugOutAllowed);
			
			return $results;
		}

		function GetPerformanceSummaryByPerfID($perfID)
		{
			$results = $this->GetPerformancesListByPerfID($perfID);
			if (count($results) == 0) return null;
			
			return $results[0];
		}
				
		function GetPerformancesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetPerformancesList($sqlFilters);
		}
				
		function GetPerformanceJoins($sqlFilters = null)
		{
			return '';
		}
				
		protected function GetPerformancesList($sqlFilters = null)
		{
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$selectFields .= ','.STAGESHOW_PRICES_TABLE.'.priceID';
			
			if (!isset($sqlFilters['groupBy']))	
			{			
				$sqlFilters['groupBy'] = 'perfID';
			}
			
			if (isset($sqlFilters['groupBy']))	
			{			
				$totalSalesField = $this->TotalSalesField($sqlFilters);
				if ($totalSalesField != '')
					$selectFields .= ','.$totalSalesField;
			}
			
			$this->perfJoined = true;

			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			$sql .= " LEFT JOIN ".STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';
			$sql .= $this->GetPerformanceJoins($sqlFilters);

			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
		
		function GetActivePerformances()
		{
			$selectFields  = '*';
			//$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			//$selectFields .= ','.STAGESHOW_PRICES_TABLE.'.priceID';
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= " JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';

			// Add SQL filter(s)
			$sqlCond  = '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="")';
			$sqlCond .= ' OR ';
			$sqlCond .= '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="'.STAGESHOW_STATE_ACTIVE.'")';
			$sqlWhere  = "($sqlCond)";
			$sqlWhere .= ' AND '.STAGESHOW_SHOWS_TABLE.'.showState="'.STAGESHOW_STATE_ACTIVE.'" ';
										
			$timeNow = current_time('mysql');
			$sqlWhere .= ' AND '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime>"'.$timeNow.'" ';

			$sqlWhere .= ' AND '.STAGESHOW_PRICES_TABLE.'.priceVisibility="'.STAGESHOW_VISIBILITY_PUBLIC.'" ';
			
			$sql .= ' WHERE '.$sqlWhere;
			
			$sql .= ' GROUP BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';

			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
		}
		
		function IsShowEnabled($result)
		{
			//echo "Show:$result->showID $result->showState<br>\n";
			return $this->IsStateActive($result->showState);
		}
		
 		function IsPerfExpired($result)
		{
			// Calculate how long before the booking window closes ...
			$timeToPerf = strtotime($result->perfDateTime) - current_time('timestamp');				
							
			if ($timeToPerf < 0) 
			{					
				$timeToPerf *= -1;
				
				echo "<!-- Performance (".$result->perfDateTime.") Expired ".$timeToPerf." seconds ago -->\n";
				// TODO-PRIORITY - Disable Performance Button (using Inventory Control) when it expires
				return true;
			}
			//echo "<!-- Performance Expires in ".$timeToPerf." seconds -->\n";
			
			return false;
		}
		
		function IsPerfEnabled($result)
		{
			if ($this->IsPerfExpired($result))
			{
				return false;
			}
			
			//echo "Show:$result->showID $result->showState Perf:$result->perfID $result->perfState<br>\n";
			return $this->IsStateActive($result->showState) && $this->IsStateActive($result->perfState);
		}
		
		function GetPricesListByShowID($showID, $activeOnly = false)
		{
			$showID = $this->GetShowID($showID);
			$sqlFilters['showID'] = $showID;
			return $this->GetPricesList($sqlFilters, $activeOnly);
		}
				
		function GetPricesListByPerfID($perfID, $activeOnly = false)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetPricesList($sqlFilters, $activeOnly);
		}
				
		function GetPricesListByPriceID($priceID, $activeOnly = false)
		{
			$sqlFilters['priceID'] = $priceID;
			return $this->GetPricesList($sqlFilters, $activeOnly);
		}
				
		function GetPricesJoins($sqlFilters)
		{
			return '';
		}
				
		function GetPricesOrder($sqlFilters)
		{
			$sql = ' , '.STAGESHOW_BOXOFFICE_SORTFIELD;
			
			return $sql;
		}
				
		function GetPricesList($sqlFilters, $activeOnly = false)
		{
			if ($activeOnly)
			{
				$sqlFilters['activePrices'] = true;
				$sqlFilters['perfState'] = STAGESHOW_STATE_ACTIVE;
			}

			$selectFields  = '*';
			if (isset($sqlFilters['saleID']))
			{
				// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
				$selectFields .= ', '.STAGESHOW_SALES_TABLE.'.saleID';

				$joinCmd = ' LEFT JOIN ';
			}
			else
				$joinCmd = ' JOIN ';
						
			// Explicitly add joined fields from "base" tables (otherwise values will be NULL if there is no matching JOIN)
			$selectFields .= ', '.STAGESHOW_PRICES_TABLE.'.priceID';
						
			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_PRICES_TABLE;
      		$sql .= ' '.$joinCmd.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
      		$sql .= ' '.$joinCmd.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= $this->GetPricesJoins($sqlFilters);
			$sql .= $this->GetWhereSQL($sqlFilters);

			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= ' , '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';			
			$sql .= $this->GetPricesOrder($sqlFilters);
			
			return $this->get_results($sql);
		}
		
// ----------------------------------------------------------------------
//
//			Start of CUSTOM SALES functions
//
// ----------------------------------------------------------------------

 		function GetSalesQueryFields($sqlFilters = null)
		{		
			if (isset($sqlFilters['addTicketFee']))
			{
				$sql  = ""; // ticketID, ";
				$sql .= "saleTxnId, saleStatus, saleFirstName, saleLastName, user_login, showName, perfDateTime, priceType, ticketQty, ticketPaid, ";
				$sql .= "saleFee*(ticketQty)/saleTotalQty AS ticketFee, ";
				$sql .= "saleTransactionFee*(ticketQty)/saleTotalQty AS ticketCharge, ";
				$sql .= "saleDateTime, saleEMail, salePPPhone, salePPStreet, salePPCity, salePPState, salePPZip, salePPCountry, perfRef";
			}
			else
			{
				$sql = parent::GetSalesQueryFields($sqlFilters);
			}
			return $sql;
		}
		
		function GetJoinedTables($sqlFilters = null, $classID = '')
		{
			$sqlJoin = '';
			
			$joinType = isset($sqlFilters['JoinType']) ? $sqlFilters['JoinType'] : 'JOIN';
			
			// JOIN parent tables
			$sqlJoin .= " $joinType ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.saleID='.STAGESHOW_SALES_TABLE.'.saleID';
			$sqlJoin .= " $joinType ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.priceID='.STAGESHOW_TICKETS_TABLE.'.priceID';
			$sqlJoin .= " $joinType ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			
			$this->perfJoined = true;						
			
			$sqlJoin .= " $joinType ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
						
			$this->showJoined = true;
						
			if (isset($sqlFilters['addTicketFee']))
			{
				$sqlJoin .= " LEFT JOIN (";
				$sqlJoin .= "SELECT ".STAGESHOW_SALES_TABLE.".saleID";
				$sqlJoin .= ", SUM(ticketQty) AS saleTotalQty ";
				$sqlJoin .= "FROM ".STAGESHOW_SALES_TABLE." JOIN ".STAGESHOW_TICKETS_TABLE." ON ".STAGESHOW_TICKETS_TABLE.".saleID=".STAGESHOW_SALES_TABLE.".saleID ";
				$sqlJoin .= "JOIN ".STAGESHOW_PRICES_TABLE." ON ".STAGESHOW_PRICES_TABLE.".priceID=".STAGESHOW_TICKETS_TABLE.".priceID ";
				$sqlJoin .= "GROUP BY ".STAGESHOW_SALES_TABLE.".saleID";
				$sqlJoin .= ") AS totals ON ".STAGESHOW_SALES_TABLE.".saleID = totals.saleID ";
			}
			
			return $sqlJoin;
		}
		
		function GetWhereSQL($sqlFilters)
		{
			$sqlWhere = parent::GetWhereSQL($sqlFilters);
			$sqlCmd = ($sqlWhere === '') ? ' WHERE ' : ' AND ';
			
			if (isset($sqlFilters['priceID']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceID="'.$sqlFilters['priceID'].'"';
				$sqlCmd = ' AND ';
			}
			
			if (isset($sqlFilters['perfID']) && ($sqlFilters['perfID'] > 0))
			{
				// Select a specified Performance Record
				$sqlWhere .= $sqlCmd.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$sqlFilters['perfID'].'"';
				$sqlCmd = ' AND ';
			}
			else 
			{
				if ($this->perfJoined)
				{
					// Select multi performance records
					if (isset($sqlFilters['perfState'])) 
					{
						if ($sqlFilters['perfState'] == STAGESHOW_STATE_ACTIVE)
						{
							$sqlCond  = '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="")';
							$sqlCond .= ' OR ';
							$sqlCond .= '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState="'.STAGESHOW_STATE_ACTIVE.'")';
							$sqlWhere .= $sqlCmd.'('.$sqlCond.')';							
						}
						else
						{
							$sqlWhere .= $sqlCmd.STAGESHOW_PERFORMANCES_TABLE.'.perfState="'.$sqlFilters['perfState'].'"';
						}
					}
					else
					{
						$sqlCond  = '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState IS NULL)';
						$sqlCond .= ' OR ';
						$sqlCond .= '('.STAGESHOW_PERFORMANCES_TABLE.'.perfState<>"'.STAGESHOW_STATE_DELETED.'")';
						$sqlWhere .= $sqlCmd.'('.$sqlCond.')';
					}
						$sqlCmd = ' AND ';
				}				
			}
						
			if (isset($sqlFilters['priceType']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceType="'.$sqlFilters['priceType'].'"';
				$sqlCmd = ' AND ';
			}
						
			if (isset($sqlFilters['activePrices']))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_PRICES_TABLE.'.priceValue>="0"';
				$sqlCmd = ' AND ';
			}			
			
			if (isset($sqlFilters['showID']) && ($sqlFilters['showID'] > 0))
			{
				$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showID="'.$sqlFilters['showID'].'"';
				$sqlCmd = ' AND ';
			}
			else if (!isset($sqlFilters['perfID']) && isset($this->showJoined) )
			{
				if (!isset($sqlFilters['showState']))
				{
					$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showState<>"'.STAGESHOW_STATE_DELETED.'"';
					$sqlCmd = ' AND ';
				}
				else
				{
					$sqlWhere .= $sqlCmd.STAGESHOW_SHOWS_TABLE.'.showState="'.$sqlFilters['showState'].'"';
					$sqlCmd = ' AND ';				
				}
			}
			
			return $sqlWhere;
		}
		
		function GetOptsSQL($sqlFilters, $sqlOpts = '')
		{
			if (isset($sqlFilters['groupBy']))
			{
				switch ($sqlFilters['groupBy'])
				{
					case 'saleID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_SALES_TABLE.'.saleID');
						break;
						
					case 'showID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_SHOWS_TABLE.'.showID');
						break;
						
					case 'perfID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_PERFORMANCES_TABLE.'.perfID');
						break;
						
					case 'priceID':
						$sqlOpts = $this->AddSQLOpt($sqlOpts, ' GROUP BY ', STAGESHOW_PRICES_TABLE.'.priceID');
						break;
						
					default:
						break;
				}
			}
			
			$sqlOpts = parent::GetOptsSQL($sqlFilters, $sqlOpts);
			return $sqlOpts;
		}
		
// ----------------------------------------------------------------------
//
//			Start of GENERIC SALES functions
//
// ----------------------------------------------------------------------
    
		function TotalSalesField($sqlFilters)
		{
			// totalQty may not include Pending sales (i.e. saleStatus=Checkout)) - add it here!
			$sql  = '  SUM(ticketQty) AS totalQty ';
			
			$statusOptions  = '(saleStatus="'.PAYMENT_API_SALESTATUS_COMPLETED.'")';
			$statusOptions .= ' OR ';
			$statusOptions .= '(saleStatus="'.PAYMENT_API_SALESTATUS_RESERVED.'")';
			$sql .= ', SUM(IF('.$statusOptions.', priceValue * ticketQty, 0)) AS soldValue ';
			$sql .= ', SUM(IF('.$statusOptions.', ticketQty, 0)) AS soldQty ';				
			
			return $sql;
		}
		
		function GetSalesListByShowID($showID)
		{
			$sqlFilters['showID']= $showID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function AddSaleFields(&$salesListArray)
		{
			for ($i=0; $i<count($salesListArray); $i++)
			{
				$salesListArray[$i]->ticketName = $salesListArray[$i]->showName.' - '.self::FormatDateForDisplay($salesListArray[$i]->perfDateTime);
				$salesListArray[$i]->ticketType = $salesListArray[$i]->priceType;
			}			
		}
		
		function DeleteOrders($saleID)
		{
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_TICKETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_TICKETS_TABLE.".saleID=$saleID";
		 
			$this->query($sql);
		}
		
		function GetTransactionFee()
		{
			return 0;
		}
		
// ----------------------------------------------------------------------
//
//			End of SALES functions
//
// ----------------------------------------------------------------------
		
		function query($sql)
		{
			$this->perfJoined = false;
			return parent::query($sql);
		}
		
		function ShowSQL($sql, $values = null)
		{
			parent::ShowSQL($sql, $values);
			
			unset($this->showJoined);
		}			
		
	}
}

?>