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

if (!defined('STAGESHOWLIB_DBASE_CLASS'))
	define('STAGESHOWLIB_DBASE_CLASS', 'StageShowWPOrgDBaseClass');
	
if (!defined('STAGESHOWLIB_DATABASE_FULL')) define('STAGESHOWLIB_DATABASE_FULL', true);

if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'stageshow_EMail.php');

if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
	define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', 'StageShowLogo.jpg');
if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
	define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', 'StageShowHeader.gif');
	
if (!class_exists('StageShowWPOrgCartDBaseClass')) 
	include STAGESHOW_INCLUDE_PATH.'stageshow_trolley_dbase_api.php';
	
if (!class_exists('StageShowWPOrgDBaseClass')) 
{
	class StageShowWPOrgDBaseClass extends StageShowWPOrgCartDBaseClass	// Define class
  	{
		function __construct($caller) //constructor	
		{
			// Call base constructor
			parent::__construct($caller);
		}
		
		function AllUserCapsToServervar()
		{
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_RESERVEUSER);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_VALIDATEUSER);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_SALESUSER);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_ADMINUSER);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_SETUPUSER);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_VIEWSETTINGS);
			$this->UserCapToServervar(STAGESHOWLIB_CAPABILITY_DEVUSER);

			parent::AllUserCapsToServervar();
		}
		
		
					
		function upgradeDB()
		{
			// Call upgradeDB() in base class
			parent::upgradeDB();
			
			// Remove priceRef field
			$this->RemovePriceRefsField();

			if ($this->IfColumnExists(STAGESHOW_TICKETS_TABLE, 'ticketName'))
			{
				// "ticketName" column never populated - removed
				$this->deleteColumn(STAGESHOW_TICKETS_TABLE, 'ticketName');				
			}

			if ($this->IfColumnExists(STAGESHOW_TICKETS_TABLE, 'ticketType'))
			{
				// "ticketType" column never populated - removed
				$this->deleteColumn(STAGESHOW_TICKETS_TABLE, 'ticketType');				
			}

			// FUNCTIONALITY: DBase - On upgrade ... Add administrator capabilities
			// Add administrator capabilities
			$adminRole = get_role('administrator');
			
			// Add Capabilities for StageShow 
			if ( !empty($adminRole) ) 
			{
				$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_SALESUSER);
				$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_ADMINUSER);
				$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_SETUPUSER);
				$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_VIEWSETTINGS);				
				$adminRole->add_cap(STAGESHOWLIB_CAPABILITY_DEVUSER);
			}
			
			$this->GetLatestNews();
		}

		function RemovePriceRefsField()
		{
			if (!$this->IfColumnExists(STAGESHOW_PRICES_TABLE, 'priceRef'))
				return false;
				
			$this->deleteColumn(STAGESHOW_PRICES_TABLE, 'priceRef');
					
			return true;
		}

		function PurgeDeletedEntries($delField, $dbFields, $sqlOpts = '')
		{
			for ($i=0; $i<count($dbFields); $i++)
			{
				$dbField[$i] = $dbFields[$i];
			
				$dbFieldParts = explode('.', $dbField[$i]);
				$dbTable[$i] = $dbFieldParts[0];
				$dbColumn[$i] = $dbFieldParts[1];
			}
			
			$subTableIndex = count($dbFields)-1;
			
			// Delete performances have been marked as deleted that have no sales
			$sqlSelect  = 'SELECT '.$dbField[0].', '.$delField.' ';
			$sql  = 'FROM '.$dbTable[0].' ';
			for ($i=1; $i<count($dbFields); $i++)
			{
				$sql .= 'LEFT JOIN '.$dbTable[$i].' ON '.$dbTable[$i].'.'.$dbColumn[$i].'='.$dbTable[$i-1].'.'.$dbColumn[$i].'  ';
			}
			if ($sqlOpts != '')
			{
				$sql .= $sqlOpts.$dbField[0].' ';
			}
			
			if ($this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				$this->get_results('SELECT * '.$sql);
			}
			
			$sql .= ') AS delresults ';
			$sql .= 'WHERE (delresults.'.$delField.' = "'.STAGESHOW_STATE_DELETED.'") ';
			
			$innerSql = $sqlSelect.$sql;
			
			$sql  = 'DELETE FROM '.$dbTable[0].' ';
			$sql .= 'WHERE '.$dbColumn[0].' IN ( ';
			$sql .= 'SELECT '.$dbColumn[0].' FROM ( ';
			$sql .= $innerSql;
			$sql .= ') ';

			$this->query($sql);
		}
		
		function PurgeOrphans($dbFields, $condition = '')
		{
			$masterCol = $dbFields[0];
			
			$dbFieldParts = explode('.', $masterCol);
			$masterTable = $dbFieldParts[0];
			$masterIndex = $dbFieldParts[1];
			
			$subCol = $dbFields[1];
			
			$dbFieldParts = explode('.', $subCol);
			$subTable = $dbFieldParts[0];
			$subIndex = $dbFieldParts[1];
			$subFieldID = str_replace('.', '_', $subCol);
			
			$subCol = $subTable.'.'.$subIndex;
			
			$sqlSelect = 'SELECT '.$masterCol.', '.$subCol.' AS '.$subFieldID.' ';
			$sql  = 'FROM '.$masterTable.' ';
			$sql .= 'LEFT JOIN '.$subTable.' ON '.$masterTable.'.'.$subIndex.'='.$subTable.'.'.$subIndex.' ';
			$sql .= 'WHERE '.$subTable.'.'.$subIndex.' IS NULL ';
			
			if ($condition != '')
			{
				$sql .= 'AND '.$condition.' ';
			}
			
			if ($this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				$this->get_results('SELECT * '.$sql);
			}
			
			$innerSql = $sqlSelect.$sql;
			
			$sql  = 'DELETE FROM '.$masterTable.' ';
			$sql .= 'WHERE '.$masterIndex.' IN ( ';
			$sql .= 'SELECT '.$masterIndex.' FROM ( ';
			$sql .= $innerSql;
			$sql .= ') AS subresults ';
			$sql .= ') ';
			
			$this->query($sql);
		}
		
		function PurgeDB()	// TODO - TEST
		{
			// Call PurgeDB() in base class
			parent::PurgeDB();
			
			// Delete all Sales where all tickets are for shows that are deleted
			$this->PurgeDeletedEntries('showState', array(
				STAGESHOW_SALES_TABLE.'.saleID',
				STAGESHOW_TICKETS_TABLE.'.saleID',
				STAGESHOW_PRICES_TABLE.'.priceID',
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				STAGESHOW_SHOWS_TABLE.'.showID',
				),
				'GROUP BY '
				);

			// Delete all Sales where all tickets are for performances that are deleted
			$this->PurgeDeletedEntries('perfState', array(
				STAGESHOW_SALES_TABLE.'.saleID',
				STAGESHOW_TICKETS_TABLE.'.saleID',
				STAGESHOW_PRICES_TABLE.'.priceID',
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				),
				'GROUP BY '
				);

			// Delete orphaned Tickets entries (no corresponding Sale)
			$this->PurgeOrphans(array(STAGESHOW_TICKETS_TABLE.'.ticketID', STAGESHOW_SALES_TABLE.'.saleID'));
			
			// Delete all Performances marked as deleted that have no corresponding Sales
			$this->PurgeDeletedEntries('perfState', array(
				STAGESHOW_PERFORMANCES_TABLE.'.perfID',
				STAGESHOW_PRICES_TABLE.'.perfID',
				STAGESHOW_TICKETS_TABLE.'.priceID',
				));

			// Delete orphaned Prices entries
			$this->PurgeOrphans(array(STAGESHOW_PRICES_TABLE.'.priceID', STAGESHOW_PERFORMANCES_TABLE.'.perfID'));
			
			// Delete Shows marked as deleted that have no Performance
			$condition = STAGESHOW_SHOWS_TABLE.'.showState = "'.STAGESHOW_STATE_DELETED.'"';
			$this->PurgeOrphans(array(STAGESHOW_SHOWS_TABLE.'.showID', STAGESHOW_PERFORMANCES_TABLE.'.showID'), $condition);						
		}
		

	    function GetDefaultOptions()
	    {
			// FUNCTIONALITY: DBase - StageShow - On Activate ... Set EMail Template Path
			$defOptions = array(
		    	'EMailTemplatePath' => STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH,
			);
			
			return $defOptions;			
		}
		
		function uninstall()
		{
			// FUNCTIONALITY: DBase - StageShow - Uninstall - Delete Performance, Prices and Tickets tables and Capabilities
			
			parent::uninstall();
      		$this->DropTable(STAGESHOW_SHOWS_TABLE);
			$this->DropTable(STAGESHOW_PERFORMANCES_TABLE);      
			$this->DropTable(STAGESHOW_PRICES_TABLE);      
			$this->DropTable(STAGESHOW_TICKETS_TABLE);  			
			
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_RESERVEUSER);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_VALIDATEUSER);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_SALESUSER);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_ADMINUSER);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_SETUPUSER);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_VIEWSETTINGS);
			$this->DeleteCapability(STAGESHOWLIB_CAPABILITY_DEVUSER);
		}
		
		//Returns an array of admin options
		function getOptions($childOptions = array())
		{
			$saveToDB = false;
			
			// Initialise settings array with default values
			$ourOptions = array(        
		        'loaded' => true,
		        
				'SetupUserRole' => STAGESHOW_DEFAULT_SETUPUSER,
		        
		        'SLen' => 0,                
		        'PLen' => 4,
		        
		        'MaxTicketQty' => STAGESHOW_MAXTICKETCOUNT,
		        
		        'LatestNews' => '',
		        'NewsUpdateTime' => '',
		        
		        'Unused_EndOfList' => ''
			);
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			// Get current values from MySQL
			$currOptions = parent::getOptions($ourOptions);
			
			// Check for Upgrading from separate settings for Live and Test API Settings 
			if (isset($currOptions['PayPalAPITestUser']))
			{
				// FUNCTIONALITY: DBase - StageShow - Options - Merge PayPal settings after version 0.9.3
				// Update from Ver 0.9.3 or earlier setup
				
				$currOptions['PayPalAPIUser'] = $currOptions['PayPalAPILiveUser'];
				$currOptions['PayPalAPISig']  = $currOptions['PayPalAPILiveSig'];
				$currOptions['PayPalAPIPwd'] = $currOptions['PayPalAPILivePwd'];
				$currOptions['PayPalAPIEMail'] = $currOptions['PayPalAPILiveEMail'];
				
				$this->deleteColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalTESTButtonID');
				$this->deleteColumn(STAGESHOW_PERFORMANCES_TABLE, 'perfPayPalLIVEButtonID');

				unset($currOptions['PayPalAPILiveUser']);
				unset($currOptions['PayPalAPILiveSig']);
				unset($currOptions['PayPalAPILivePwd']);
				unset($currOptions['PayPalAPILiveEMail']);
				
				unset($currOptions['PayPalAPITestUser']);
				unset($currOptions['PayPalAPITestSig']);
				unset($currOptions['PayPalAPITestPwd']);
				unset($currOptions['PayPalAPITestEMail']);
				
				$saveToDB = true;
			}
			
			if ($currOptions['SetupUserRole'] == '') 
			{
				$currOptions['SetupUserRole'] = STAGESHOW_DEFAULT_SETUPUSER;
				$saveToDB = true;
			}
				
			$this->adminOptions = $currOptions;
			
			if ($saveToDB)
				$this->saveOptions();
			
			return $currOptions;
		}
        
		
		function getTableDef($tableName)
		{
			$sql = parent::getTableDef($tableName);
			switch($tableName)
			{
				case STAGESHOW_SHOWS_TABLE:
					$sql .= '
						showName VARCHAR('.STAGESHOW_SHOWNAME_TEXTLEN.') NOT NULL,
						showState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'), 
					';
					break;
					
				case STAGESHOW_PERFORMANCES_TABLE:
					$sql .= '
						showID INT UNSIGNED NOT NULL,
						perfState VARCHAR('.STAGESHOW_ACTIVESTATE_TEXTLEN.'),
						perfDateTime DATETIME NOT NULL,
						perfRef VARCHAR('.STAGESHOW_PERFREF_TEXTLEN.') NOT NULL,
						perfSeats INT NOT NULL,
					';
					break;
					
				case STAGESHOW_PRICES_TABLE:		
					$sql .= '
						perfID INT UNSIGNED NOT NULL,
						priceType VARCHAR('.STAGESHOW_PRICETYPE_TEXTLEN.') NOT NULL,
						priceValue DECIMAL(9,2) NOT NULL,
					';
					break;
					
				case STAGESHOW_TICKETS_TABLE:
					$sql .= '
						saleID INT UNSIGNED NOT NULL,
						priceID INT UNSIGNED NOT NULL,
						ticketQty INT NOT NULL,
						ticketPaid DECIMAL(9,2) NOT NULL DEFAULT 0.0,
						ticketSeat TEXT,
						';
					break;
				
				case STAGESHOW_DEMOLOG_TABLE:
					$sql .= '
						logDateTime DATETIME NOT NULL,
					';
					break;
					
			}
			
			return $sql;
		}
		
		function clearAll()
		{
			parent::clearAll();

			$this->DropTable(STAGESHOW_SHOWS_TABLE);
			$this->DropTable(STAGESHOW_PERFORMANCES_TABLE);
			$this->DropTable(STAGESHOW_PRICES_TABLE);
			$this->DropTable(STAGESHOW_TICKETS_TABLE);
		}
		
		function createDB($dropTable = false)
		{
      		global $wpdb;
     
			if ($dropTable && isset($this->adminOptions['showName']))
			{
				unset($this->adminOptions['showName']);
				$this->saveOptions();
			}
			
			parent::createDB($dropTable);

			$this->createDBTable(STAGESHOW_TICKETS_TABLE, 'ticketID', $dropTable);

			// ------------------- STAGESHOW_SHOWS_TABLE -------------------
			if ($dropTable)
			{
				$addingShowsTable = false;			
			}
			else
			{
				if ($this->tableExists(STAGESHOW_PERFORMANCES_TABLE)) 
					$addingShowsTable = !$this->tableExists(STAGESHOW_SHOWS_TABLE);
				else
					$addingShowsTable = false;	
			}
				
			$this->createDBTable(STAGESHOW_SHOWS_TABLE, 'showID', $dropTable);
			
			// StageShow to StageShow-Plus Update
			if ($addingShowsTable && isset($this->adminOptions['showName']))
			{
				// See if we have a show configured for StageShow before adding SHOWS table
				if ($this->adminOptions['showName'] != '')
				{
					$showName = $this->adminOptions['showName'];
					$showState = $this->adminOptions['showState'];
					
					$this->AddShow($showName, $showState);
					
					if (isset($this->adminOptions['showName']))
					{
						unset($this->adminOptions['showName']);
						unset($this->adminOptions['showState']);
						$this->saveOptions();
					}
					
				}
			}
			
			$this->createDBTable(STAGESHOW_PERFORMANCES_TABLE, 'perfID', $dropTable);
			$this->createDBTable(STAGESHOW_PRICES_TABLE, 'priceID', $dropTable);
			
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$this->createDBTable(STAGESHOW_DEMOLOG_TABLE, 'demologID', $dropTable);
			}
		}
		
        
		
		function GetShowsSettings($extraFields = '')
		{
			$selectFields = 'showName,perfState,perfDateTime,perfSeats,priceType,priceValue';
			if ($extraFields != '')
			{
				$selectFields .= ','.$extraFields;
			}
			
			$sql  = 'SELECT '.$selectFields.' FROM '.STAGESHOW_SHOWS_TABLE.' ';
			$sql .= 'LEFT JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.STAGESHOW_SHOWS_TABLE.'.showID ';
			$sql .= 'LEFT JOIN '.STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID ';
			$sql .= 'ORDER BY showName, perfDateTime, priceType ';
				
			return $this->get_results($sql);
		}

		
		function CreateNewPerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = -1)
		{
			if ($showID <= 0) 
			{
				$rtnMsg = __('Internal Error - showID', $this->get_domain());
				return 0;
			}
			
			$perfState = '';
			$perfID = 0;
			
			// Get the show name
			$shows = $this->GetShowsList($showID);
			$showName = $shows[0]->showName;
		
			// Add performance to database					
			// Give performance unique Ref - Check what default reference IDs already exist in database
			$perfID = $this->AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats);
			if ($perfID == 0)
				$rtnMsg = __('Performance Reference is not unique', $this->get_domain());
			else
				$rtnMsg = __('New Performance Added', $this->get_domain());
			
			return $perfID;			
		}
		
		function GetEmail($ourOptions, $emailRole = '')
		{
			// FUNCTIONALITY: DBase - GetEmail - Uses AdminEMail (otherwise WP admin email) - Optionally with OrganisationID from settings
			// StageShow ignores the "emailRole" parameter and always uses the AdminEMail entry
			$ourEmail = '';

			if (strlen($ourOptions['AdminEMail']) > 0)
				$ourEmail = $ourOptions['AdminEMail'];
			else
				$ourEmail = get_bloginfo('admin_email');
				
			// Get from email address from settings
			if ($ourOptions['AdminID'] !== '')
				$ourEmail = $ourOptions['AdminID'].' <'.$ourEmail.'>';
			else if ($ourOptions['OrganisationID'] !== '')
				$ourEmail = $ourOptions['OrganisationID'].' <'.$ourEmail.'>';
				
			return $ourEmail;
		}
		

		function StateActiveText($state)
		{
			switch ($state)
			{
				case STAGESHOW_STATE_DELETED:
					return __("DELETED", $this->get_domain());
					
				default:
					if ($this->IsStateActive($state))
						return __("Active", $this->get_domain());
					else
						return __("INACTIVE", $this->get_domain());
					break;
			}
			
			return '';
		}

		function GetAllShowsList()
		{
			return $this->GetShowsList(0);
		}
		
		function GetShowsList($showID = 0)
		{
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_SHOWS_TABLE.'.showID';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$selectFields .= ','.STAGESHOW_PRICES_TABLE.'.priceID';

			$sqlFilters['showID'] = $showID;
			$sqlFilters['groupBy'] = 'showID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';
			
			if (isset($sqlFilters['groupBy']))	
			{			
				$totalSalesField = $this->TotalSalesField($sqlFilters);
				if ($totalSalesField != '')
					$selectFields .= ','.$totalSalesField;
			}
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_SHOWS_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.STAGESHOW_SHOWS_TABLE.'.showID';
			$sql .= " LEFT JOIN ".STAGESHOW_PRICES_TABLE.' ON '.STAGESHOW_PRICES_TABLE.'.perfID='.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			$sql .= " LEFT JOIN ".STAGESHOW_TICKETS_TABLE.' ON '.STAGESHOW_TICKETS_TABLE.'.priceID='.STAGESHOW_PRICES_TABLE.'.priceID';
			$sql .= " LEFT JOIN ".STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID='.STAGESHOW_TICKETS_TABLE.'.saleID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$results = $this->get_results($sql, true, $sqlFilters);

			return $results;
		}
		
		function UpdateSettings($result, $tableId, $settingId, $indexId, $index)
		{
			$newVal = $_POST[$settingId.$index];	// TODO: Check for SQLi
			if ($newVal == $result->$settingId)
				return;
				
			$sql  = 'UPDATE '.$tableId;
			$sql .= ' SET '.$settingId.'="'.$newVal.'"';
			$sql .= ' WHERE '.$indexId.'='.$index;;
			 
			$this->query($sql);	
		}
		
		function IsShowNameUnique($showName)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
			
			$showsEntries = $this->get_results($sql);
			return (count($showsEntries) > 0) ? false : true;
		}
		
		function SetShowActivated($showID, $showState = STAGESHOW_STATE_ACTIVE)
		{
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showState="'.$showState.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;

			$this->query($sql);	
			return "OK";							
		}
		
		function CanAddShow()
		{		
			// FUNCTIONALITY: Shows - StageShow only supports a single show
			$rtnVal = true;
			
			$sql  = 'SELECT COUNT(*) AS showLen FROM '.STAGESHOW_SHOWS_TABLE;
			 
			$results = $this->get_results($sql);
			$rtnVal = ($results[0]->showLen < 1);
			
			return $rtnVal;
		}
		 
		function AddShow($showName = '', $showState = STAGESHOW_STATE_ACTIVE)
		{
			// FUNCTIONALITY: Shows - StageShow - Add Show
			// Check if a show can be added
			if (!$this->CanAddShow())
				return 0;
				
	      	if ($showName === '')
	      	{
				$newNameNo = 1;
				while (true)
				{
					$showName = __('Unnamed Show', $this->get_domain());
					if ($newNameNo > 1) $showName .= ' '.$newNameNo;
					//if ($this->InTestMode()) $showName .= " (".StageShowLibUtilsClass::GetSiteID().")";
						
					if ($this->IsShowNameUnique($showName))
						break;
					$newNameNo++;
				}
			}
			else
			{
				if (!$this->IsShowNameUnique($showName))
					return 0;	// Error - Show Name is not unique
			}
						
			$sql = 'INSERT INTO '.STAGESHOW_SHOWS_TABLE.'(showName, showState) VALUES("'.$showName.'", "'.$showState.'")';
			$this->query($sql);	
					
     		return $this->GetInsertId();
		}
				
		function UpdateShowName($showID, $showName)
		{
			if (!$this->IsShowNameUnique($showName))
				return "ERROR";
				
			$sql  = 'UPDATE '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' SET showName="'.$showName.'"';
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showID='.$showID;;
			$this->query($sql);	

			return "OK";
		}
			
		function CanDeleteShow($showEntry)
		{
			$lastPerfDate = $this->GetLastPerfDateTime($showEntry->showID);

			if (($perfDate = strtotime($lastPerfDate)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}

			$showSales = $showEntry->totalQty;
			$canDelete |= ($showSales == 0);		
			
			if ($this->getDbgOption('Dev_ShowMiscDebug') == 1) 
			{
				echo "CanDeleteShow(".$showEntry->showID.") returns $canDelete <br>\n";
			}
			
			return $canDelete;		
		}	
		
		function renameColumn($table_name, $oldColName, $newColName)
		{
 			$colSpec = $this->getColumnSpec($table_name, $oldColName);
			if (!isset($colSpec->Field))
				return __("DB Error", $this->get_domain()).": $oldColName ".__("Column does not exist", $this->get_domain());
				
			$sql = "ALTER TABLE $table_name CHANGE $oldColName $newColName ".$colSpec->Type;
			if ($colSpec->Null == 'NO')
				$sql .= " NOT NULL";
			if ($colSpec->Default != '')
				$sql .= " DEFAULT = '".$colSpec->Default."'";

			$this->query($sql);	
			return "OK";							
		}

		function GetAllPerformancesList()
		{
			return $this->GetPerformancesList();
		}
				
		function GetPerformancesListByShowID($showID)
		{
			$sqlFilters['showID'] = $showID;
			return $this->GetPerformancesList($sqlFilters);
		}

		function CanDeletePerformance($perfsEntry)
		{
			$perfDateTime = $perfsEntry->perfDateTime;
				
			// Performances can be deleted if there are no tickets sold or 24 hours after start date/time
			if (($perfDate = strtotime($perfDateTime)) === false)
				$canDelete = false;
			else
			{
				$dateDiff = strtotime("now")-$perfDate;
				$canDelete = ($dateDiff > 60*60*24);
			}
			
			$perfSales = $perfsEntry->ticketQty;
			$canDelete |= ($perfSales == 0);
			
			return $canDelete;
		}
		
		function SetPerfActivated($perfID, $perfState = STAGESHOW_STATE_ACTIVE)
		{
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET perfState="'.$perfState.'"';
			$sql .= $this->GetWhereSQL($sqlFilters);

			$this->query($sql);	
			return "OK";							
		}
		
		private function GetLastPerfDateTime($showID = 0)
		{
			$sql  = 'SELECT MAX(perfDateTime) AS LastPerf FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.showID='.$showID;
			
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return 0;
			return $results[0]->LastPerf;
		}
		
		function IsPerfRefUnique($perfRef)
		{
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfRef="'.$perfRef.'"';
			 
			$perfsCount = $this->get_results($sql);
			return ($perfsCount[0]->MatchCount > 0) ? false : true;
		}
		
		function CanAddPerformance()
		{
			$rtnVal = true;
			
			$sql  = 'SELECT COUNT(*) AS perfLen FROM '.STAGESHOW_PERFORMANCES_TABLE;
			 
			$results = $this->get_results($sql);
			$rtnVal = (($this->adminOptions['PLen']==0) || ($results[0]->perfLen<$this->adminOptions['PLen']));
			
			return $rtnVal;
		}

		function GetAllPlansList()
		{
			return array();
		}
		
		function AddPerformance($showID, $perfState, $perfDateTime, $perfRef, $perfSeats)
		{
			if ($perfRef === '')
			{
				$perfRefNo = 1;
				while (true)
				{
					// Query Database for proposed Performance Ref until we find one that doesn't already exist
					$perfRef = 'PERF'.$perfRefNo;
					if ($this->IsPerfRefUnique($perfRef))
						break;
					$perfRefNo++;
				}
			}
			else
			{
				if (!$this->IsPerfRefUnique($perfRef))
					return 0;	// Error - Performance Reference is not unique
			}
			
			$sql  = 'INSERT INTO '.STAGESHOW_PERFORMANCES_TABLE.'(showID, perfState, perfDateTime, perfRef, perfSeats)';
			$sql .= ' VALUES('.$showID.', "'.$perfState.'", "'.$perfDateTime.'", "'.$perfRef.'", "'.$perfSeats.'")';
			 
			$this->query($sql);
			
     		return $this->GetInsertId();
		}
				
		function UpdatePerformanceTime($perfID, $newPerfDateTime)
		{
			$sqlSET = 'perfDateTime="'.$newPerfDateTime.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}
				
		function UpdatePerformanceRef($perfID, $newPerfRef)
		{
			if (!$this->IsPerfRefUnique($newPerfRef))
			{
				return "ERROR";
			}
				
			$sqlSET = 'perfRef="'.$newPerfRef.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}
				
		function UpdatePerformanceSeats($perfID, $newPerfSeats)
		{
			$sqlSET = 'perfSeats="'.$newPerfSeats.'"';
			return $this->UpdatePerformanceEntry($perfID, $sqlSET);
		}								
				
		function UpdatePerformanceEntry($perfID, $sqlSET)
		{
			$sqlFilters['perfID'] = $perfID;
				 
			$sql  = 'UPDATE '.STAGESHOW_PERFORMANCES_TABLE;
			$sql .= ' SET '.$sqlSET;
			$sql .= $this->GetWhereSQL($sqlFilters);

			$this->query($sql);	
			return "OK";							
		}
		
		function IsPriceValid($newPriceValue, $result)
		{
			// Verify that the price value is not empty
			if (strlen($newPriceValue) == 0)
			{
				return __('Price Not Specified', $this->get_domain());
			}

			// Verify that the price value is a numeric value
			if (!is_numeric($newPriceValue))
			{
				return __('Invalid Price Entry', $this->get_domain());
			}

			// Verify that the price value is positive!
			if ($newPriceValue < 0.0)
			{
				return __('Price Entry cannot be negative', $this->get_domain());
			}
			
			return '';
		}

		function IsPriceTypeUnique($perfID, $priceType)
		{
			$sql  = 'SELECT COUNT(*) AS MatchCount FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.'.priceType="'.$priceType.'"';
			$sql .= ' AND '.STAGESHOW_PRICES_TABLE.'.perfID="'.$perfID.'"';

			$pricesEntries = $this->get_results($sql);
			return ($pricesEntries[0]->MatchCount > 0) ? false : true;
		}
		
		function AddPrice($perfID, $priceType, $priceValue = STAGESHOW_PRICE_UNKNOWN, $visibility = STAGESHOW_VISIBILITY_PUBLIC, $noOfSeats = 1)
		{
     		if ($perfID <= 0) 
     		{
     			return 0;
     		}
      
      		if ($priceType === '')
      		{
				$priceTypeNo = 1;
				while (true)
				{
					// Query Database for proposed Performance Ref until we find one that doesn't already exist
					$priceType = 'TYPE'.$priceTypeNo;
					if ($this->IsPriceTypeUnique($perfID, $priceType))
						break;
					$priceTypeNo++;
				}
			}
			else
			{
				if (!$this->IsPriceTypeUnique($perfID, $priceType))
				{
					return 0;	// Error - Performance Reference is not unique					
				}
			}
			
			$sql  = 'INSERT INTO '.STAGESHOW_PRICES_TABLE.' (perfID, priceType, priceValue)';
			$sql .= ' VALUES('.$perfID.', "'.$priceType.'", "'.$priceValue.'")';
			 			
			$this->query($sql);
			
     		return $this->GetInsertId();
		}
		
		function UpdatePriceType($priceID, $newPriceType)
		{
			$sqlSET = 'priceType="'.$newPriceType.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceValue($priceID, $newPriceValue)
		{
			$sqlSET = 'priceValue="'.$newPriceValue.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
		}								
				
		function UpdatePriceEntry($priceID, $sqlSET)
		{
			$sql  = 'UPDATE '.STAGESHOW_PRICES_TABLE;
			$sql .= ' SET '.$sqlSET;			
			$sql .= ' WHERE priceID='.$priceID;			

			$this->query($sql);	
			return "OK";							
		}

		function DeleteShowByShowID($showID)
		{
			// Get the show name
			$sql = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.".showID=$showID";
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return '';
			
			$this->SetShowActivated($showID, STAGESHOW_STATE_DELETED);

			return $results[0]->showName;
		}			
		
		function DeletePerformanceByPerfID($perfID)
		{
			$this->SetPerfActivated($perfID, STAGESHOW_STATE_DELETED);
		}			
		
		function DeletePriceByPriceID($ID)
		{
			return $this->DeletePrice($ID, 'priceID');
		}			
		
		function DeletePriceByPerfID($ID)
		{
			return $this->DeletePrice($ID, 'perfID');
		}			
		
		private function DeletePrice($ID, $IDfield)
		{
			$sql  = 'DELETE FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' WHERE '.STAGESHOW_PRICES_TABLE.".$IDfield=$ID";
			 
			$this->query($sql);
		}					

		function GetAllTicketTypes()
		{
			$sql  = 'SELECT priceType FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' GROUP BY priceType';
			$sql .= ' ORDER BY priceType';
			 
			return $this->get_results($sql);
		}
		
		function GetTicketsListByPerfID($perfID)
		{
			$sqlFilters['orderBy'] = 'saleLastName,'.STAGESHOW_SALES_TABLE.'.saleID DESC';
			$sqlFilters['perfID'] = $perfID;
			return $this->GetSalesList($sqlFilters);
		}
		
		function GetAllSalesQty($sqlFilters = null)
		{
			$sqlFilters['groupBy'] = 'perfID';
			$sqlFilters['JoinType'] = 'RIGHT JOIN';

			$sql  = 'SELECT *,'.$this->TotalSalesField($sqlFilters).' FROM '.STAGESHOW_SALES_TABLE;	
			$sql .= $this->GetJoinedTables($sqlFilters, __CLASS__);
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.showID, '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
					
			$salesListArray = $this->get_results($sql);
							 
			return $salesListArray;
		}
		
// ----------------------------------------------------------------------
//
//			Start of CUSTOM SALES functions
//
// ----------------------------------------------------------------------
    
		function GetSalesQtyByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			return $this->GetSalesQty($sqlFilters);
		}
				
		function GetSalesQtyBySaleID($saleID)
		{
			$sqlFilters['saleID'] = $saleID;
			return $this->GetSalesQty($sqlFilters);
		}
				
		
		
		
		
// ----------------------------------------------------------------------
//
//			Start of GENERIC SALES functions
//
// ----------------------------------------------------------------------
    
		function GetSaleStockID($itemRef, $itemOption)
		{
			// itemRef format: {showID}-{perfID}
			$itemRefs = explode('-', $itemRef);
			$sqlFilters['showID'] = $itemRefs[0];
			$sqlFilters['perfID'] = $itemRefs[1];
			$sqlFilters['priceType'] = $itemOption;
					
			$priceEntries = $this->GetPricesList($sqlFilters);
			
			if (count($priceEntries) > 0) 
				$stockID = $priceEntries[0]->priceID;
			else
				$stockID = 0;
			
			return $stockID;
		}
				
		
				
		function GetSalesListByPerfID($perfID)
		{
			$sqlFilters['perfID'] = $perfID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetSalesListByPriceID($priceID)
		{
			$sqlFilters['priceID']= $priceID;
			$sqlFilters['groupBy']= 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		function GetAllSalesListBySaleTxnId($saleTxnId)
		{
			// Add TotalSalesField .... groupBy does the trick!
			$sqlFilters['saleTxnId'] = $saleTxnId;
			//$sqlFilters['groupBy'] = 'saleID';
			return $this->GetSalesList($sqlFilters);
		}
				
		
		function GetPricesListWithSales($saleID)
		{
			$selectFields  = '*';
			
			$sqlWhere1 = ' WHERE '.STAGESHOW_TICKETS_TABLE.'.saleID = "'.$saleID.'"';
			$sqlWhere2 = ' ';
			if (defined('CORONDECK_RUNASDEMO'))
			{
				$sqlWhere1 .= ' AND '.STAGESHOW_TICKETS_TABLE.'.loginID = "'.$this->loginID.'"';
				$sqlWhere2 .= ' WHERE '.STAGESHOW_PRICES_TABLE.'.loginID = "'.$this->loginID.'"';
			}
			$sql  = 'SELECT '.$selectFields;
			$sql .= ' FROM ( SELECT * FROM '.STAGESHOW_TICKETS_TABLE.$sqlWhere1.' ) AS sales';
			$sql .= ' RIGHT JOIN ( SELECT * FROM '.STAGESHOW_PRICES_TABLE.$sqlWhere2.' ) AS prices';
			$sql .= ' ON sales.priceID = prices.priceID';
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID=prices.perfID';			
			$sql .= ' JOIN '.STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			$sql .= ' LEFT JOIN '.STAGESHOW_SALES_TABLE.' ON '.STAGESHOW_SALES_TABLE.'.saleID=sales.saleID';
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime, prices.priceType';

			$this->ShowSQL($sql); 
			
			$showOutput = $this->getDbgOption('Dev_ShowDBOutput'); 
			$this->dbgOptions['Dev_ShowDBOutput'] = '';
			
			$salesListArray = $this->get_results($sql);			
			$this->AddSaleFields($salesListArray);
			
			$this->dbgOptions['Dev_ShowDBOutput'] = $showOutput;
			$this->show_results($salesListArray);
								
			return $salesListArray;
		}
		
		
		function DeleteSale($saleID)
		{
			parent::DeleteSale($saleID);

			$this->DeleteOrders($saleID);			
		}			
		
				
		
// ----------------------------------------------------------------------
//
//			End of SALES functions
//
// ----------------------------------------------------------------------
    
		function GetSalesEMail()
		{
			return $this->adminOptions['AdminEMail'];
		}
		
		function IsCurrencyField($tag)
		{
			switch ($tag)
			{
				case '[ticketPaid]':
				case '[priceValue]':
					return true;
			}
			
			return parent::IsCurrencyField($tag);					
		}
		
		function GetInfoServerURL($pagename)
		{
			$filename = $pagename.'.php';
			
			$customURL = $this->getDbgOption('Dev_InfoSrvrURL');
			if ($customURL != '')
			{
				$updateCheckURL = $this->get_pluginURI().'/';
				$pageURLPosn = strrpos($updateCheckURL, '/StageShow');
				$updateCheckURL = $customURL.substr($updateCheckURL, $pageURLPosn+1, strlen($updateCheckURL));
			}
			else
			{
				$updateCheckURL = $this->get_pluginURI().'/';				
			}
			$updateCheckURL .= $filename;

			$updateCheckURL = str_replace('\\', '/', $updateCheckURL);
			$updateCheckURL = str_replace("//$filename", "/$filename", $updateCheckURL);
			
			//$updateCheckURL = add_query_arg('email', urlencode($this->adminOptions['AdminEMail']), $updateCheckURL);
			$updateCheckURL = add_query_arg('ver', urlencode($this->get_version()), $updateCheckURL);
			$updateCheckURL = add_query_arg('url', urlencode(get_option('siteurl')), $updateCheckURL);
			
			//echo "updateCheckURL: $updateCheckURL<br>\n";
			return $updateCheckURL;
		}
		    
		function GetHTTPPage($reqURL)
		{
			$rtnVal = '';
			$response = $this->HTTPGet($reqURL);
						
			if ($response['APIStatus'] == 200)
				$rtnVal = $response['APIResponseText'];
			
			//if ($rtnVal == '')
			//	echo "GetHTTPPage($reqURL) Failed<br>\n";
				
			return $rtnVal;
		}

		function CheckIsConfigured()
		{
			$this->GetLatestNews();
			return parent::CheckIsConfigured();
		}
				
		function GetLatestNews()
		{
			$news = $this->CheckLatestNews();
			return $news['LatestNews'];
		}
				
		function CheckLatestNews()
		{
			$latest['LatestNews'] = '';
			$latest['Status'] = "UNKNOWN";
			$getUpdate = true;
			
			if (isset($this->adminOptions['NewsUpdateTime']) && ($this->adminOptions['NewsUpdateTime'] !== '') )
			{
				$lastUpdate = $this->adminOptions['NewsUpdateTime'];
				$latest['LastUpdate'] = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, $lastUpdate);
				
				$updateInterval = STAGESHOW_NEWS_UPDATE_INTERVAL*24*60*60;
				$nextUpdate = $lastUpdate + $updateInterval;
				$latest['NextUpdate'] = date(StageShowLibDBaseClass::MYSQL_DATETIME_FORMAT, $nextUpdate);
				
				if ($nextUpdate > current_time('timestamp'))
				{
					$latest['Status'] = "UpToDate";
					$latest['LatestNews'] = $this->adminOptions['LatestNews'];
					$getUpdate = false;
				}			
			}
			
			if ($getUpdate)
			{
				// Get URL of StagsShow News server from Plugin Info
				$updateCheckURL = $this->GetInfoServerURL('news');
				$htmlPage = $this->GetHTTPPage($updateCheckURL);
				if (strpos($htmlPage, '<html') || strpos($htmlPage, '<html'))
				{
					$latest['Status'] = "HTTP_CompletePage";
					$latest['LatestNews'] = '';
				}
				else if (strlen($latest['LatestNews']) <= 2)
				{
					$latest['Status'] = "HTTP_Empty";
					$latest['LatestNews'] = '';
				}
				else
				{
					$latest['LatestNews'] = $htmlPage;
					$latest['Status'] = "HTTP_OK";
				}
					
				$this->adminOptions['LatestNews'] = $latest['LatestNews'];
					
				$this->adminOptions['NewsUpdateTime'] = current_time('timestamp');
				$this->saveOptions();
				//echo "News Updated<br>\n";
			}				
			
			return $latest;
		}
		
		function AddTableLocks($sql)
		{
			$sql = parent::AddTableLocks($sql);
			$sql .= ', '.STAGESHOW_PRICES_TABLE.' READ';
			$sql .= ', '.STAGESHOW_PERFORMANCES_TABLE.' READ';
			$sql .= ', '.STAGESHOW_SHOWS_TABLE.' READ';
			return $sql;
		}
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			// Add any email fields that are not in the sale record ...
			$dateFormat = self::GetDateFormat();
			$timeFormat = self::GetTimeFormat();
					
			$timestamp = strtotime($saleDetails->perfDateTime);
			$saleDetails->perfDate = date($dateFormat, $timestamp);
			$saleDetails->perfTime = date($timeFormat, $timestamp);
			
			$eMailFields = parent::AddEMailFields($EMailTemplate, $saleDetails);
			
			return $eMailFields;
		}
		
		function FormatEMailField($tag, $field, &$saleDetails)
		{
			if ($tag =='[ticketSeat]') 
				return '';
			
			return parent::FormatEMailField($tag, $field, $saleDetails);
		}	
		
	}
}

?>
