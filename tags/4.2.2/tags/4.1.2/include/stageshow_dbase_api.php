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

if (!defined('STAGESHOW_DBASE_CLASS'))
	define('STAGESHOW_DBASE_CLASS', 'StageShowDBaseClass');
	
if (!defined('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH'))
	define('STAGESHOW_ACTIVATE_EMAIL_TEMPLATE_PATH', 'stageshow_EMail.php');

include 'stageshowlib_sales_dbase_api.php';      

if (!class_exists('StageShowDBaseClass')) 
{
	// Set the DB tables names
	global $wpdb;
	
	$dbPrefix = $wpdb->prefix;
	if (defined('RUNSTAGESHOWDEMO'))
	{
		$dbPrefix .= str_replace('stageshow', 'demo_ss', STAGESHOW_DIR_NAME).'_';
	}
	else
	{
		$dbPrefix .= 'sshow_';		
	}
	define('STAGESHOW_TABLE_PREFIX', $dbPrefix);
	
	define('STAGESHOW_SHOWS_TABLE', STAGESHOW_TABLE_PREFIX.'shows');
	define('STAGESHOW_PERFORMANCES_TABLE', STAGESHOW_TABLE_PREFIX.'perfs');
	define('STAGESHOW_PRICES_TABLE', STAGESHOW_TABLE_PREFIX.'prices');
	define('STAGESHOW_SALES_TABLE', STAGESHOW_TABLE_PREFIX.'sales');
	define('STAGESHOW_TICKETS_TABLE', STAGESHOW_TABLE_PREFIX.'tickets');

	define('STAGESHOW_DEMOLOG_TABLE', STAGESHOW_TABLE_PREFIX.'demolog');
		
	if (!defined('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_LOGOIMAGE_FILE', 'StageShowLogo.jpg');
	if (!defined('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE'))
		define('PAYPAL_APILIB_DEFAULT_HEADERIMAGE_FILE', 'StageShowHeader.gif');
	
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
	define('STAGESHOW_VISIBILITY_ADMIN', 'admin');

	define('STAGESHOW_SALESTATUS_RESERVED', 'Reserved');

	define('STAGESHOW_PRICE_S1_P1_ALL', '12.50');
	define('STAGESHOW_PRICE_S1_P2_ADULT', '5.50');
	define('STAGESHOW_PRICE_S1_P3_ADULT', '4.00');
	define('STAGESHOW_PRICE_S1_P4_ALL', '6.00');
	define('STAGESHOW_PRICE_S1_P2_CHILD', '3.00');
	define('STAGESHOW_PRICE_S1_P3_CHILD', '2.00');
	
	class StageShowDBaseClass extends StageShowLibSalesDBaseClass // Define class
  	{
		const STAGESHOW_DATE_FORMAT = 'Y-m-d';
		
		var $perfJoined = false;
		
		function __construct($caller) //constructor	
		{
			$StageshowDbgoptionsName = STAGESHOW_DIR_NAME.'dbgsettings';
			
			// Options DB Field - In DEMO Mode make unique for each user, and Plugin type
			if (defined('RUNSTAGESHOWDEMO'))
			{
				$this->GetLoginID();
				$StageshowOptionsName  = STAGESHOW_DIR_NAME.'settings_';
				//$StageshowOptionsName .= $this->loginID;
			}
			else
			{
				$StageshowOptionsName = 'stageshowsettings';
			}
			
			$opts = array (
				'Caller'             => $caller,
				'PluginFolder'       => dirname(plugin_basename(dirname(__FILE__))),
				'DownloadFilePath'   => '/wp-content/plugins/stageshow/stageshow_download.php',
				'CfgOptionsID'       => $StageshowOptionsName,
				'DbgOptionsID'       => $StageshowDbgoptionsName,
			);			
			
			// Call base constructor
			parent::__construct($opts);
			
			$this->setPayPalCredentials(STAGESHOW_PAYPAL_IPN_NOTIFY_URL);			
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
					
		function HasSettings()
		{
			$results = $this->GetAllShowsList();
			return (count($results) > 0);
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
				$adminRole->add_cap(STAGESHOW_CAPABILITY_SALESUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_ADMINUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_SETUPUSER);
				$adminRole->add_cap(STAGESHOW_CAPABILITY_VIEWSETTINGS);				
				$adminRole->add_cap(STAGESHOW_CAPABILITY_DEVUSER);
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
		
		function init()
		{
			// This function should be called by the 'init' action of the Plugin
			// Action requiring setting of Cookies should be done here
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
			
			$this->DeleteCapability(STAGESHOW_CAPABILITY_RESERVEUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_VALIDATEUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_SALESUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_ADMINUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_SETUPUSER);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_VIEWSETTINGS);
			$this->DeleteCapability(STAGESHOW_CAPABILITY_DEVUSER);
		}
		
		//Returns an array of admin options
		function getOptions($childOptions = array(), $saveToDB = true)
		{
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
			$currOptions = parent::getOptions($ourOptions, false);
			
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
			}
			
			if ($currOptions['SetupUserRole'] == '') 
				$currOptions['SetupUserRole'] = STAGESHOW_DEFAULT_SETUPUSER;
				
			$this->adminOptions = $currOptions;
			
			if ($saveToDB)
				$this->saveOptions();
			
			return $currOptions;
		}
        
		function get_domain()
		{
			// This function returns the domain id (for translations) 
			// The domain is the same for all stageshow derivatives
			return 'stageshow';
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
					
					//$this->LogToDebugFile("Activate.log", "StageShow Shows: ");		
					//$this->LogToDebugFile("Activate.log", print_r($showName, true));		
					
					$this->AddShow($showName, $showState);
					
					if (isset($this->adminOptions['showName']))
					{
						unset($this->adminOptions['showName']);
						unset($this->adminOptions['showState']);
						$this->saveOptions();
					}
					
					//$this->LogToDebugFile("Activate.log", "Updated in StageShowDBaseClass() constructor ");	
					//$this->LogToDebugFile("Activate.log", "adminOptions: ");																					
					//$this->LogToDebugFile("Activate.log", print_r($this->adminOptions, true));												
				}
			}
			
			$this->createDBTable(STAGESHOW_PERFORMANCES_TABLE, 'perfID', $dropTable);
			$this->createDBTable(STAGESHOW_PRICES_TABLE, 'priceID', $dropTable);
			
			if (defined('RUNSTAGESHOWDEMO'))
			{
				$this->createDBTable(STAGESHOW_DEMOLOG_TABLE, 'demologID', $dropTable);
			}
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

		function AddSamplePerformance(&$rtnMsg, $showID, $perfDateTime, $perfRef = '', $perfSeats = -1)
		{
			$perfID = $this->CreateNewPerformance($rtnMsg, $showID, $perfDateTime, $perfRef, $perfSeats);
			
			$this->perfIDs[$perfRef] = $perfID;
			
			return $perfID;
		}
		
		function AddSamplePrice($perfRef, $priceType, $priceValue = STAGESHOW_PRICE_UNKNOWN, $visibility = STAGESHOW_VISIBILITY_PUBLIC)
		{
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$priceValue = $priceValue/STAGESHOW_SAMPLEPRICE_DIVIDER;
				$priceValue = number_format($priceValue, 2);
			}
			$perfID = $this->perfIDs[$perfRef];
			$priceID = $this->AddPrice($perfID, $priceType, $priceValue, $visibility);
			
			return $priceID;
		}
		
		function InTestMode()
		{
			if (!$this->testModeEnabled) return false;
			
			if (!function_exists('wp_get_current_user')) return false;
			
			return current_user_can(STAGESHOW_CAPABILITY_DEVUSER);
		}
		
		function CreateSample($sampleDepth = 0)
		{
			// FUNCTIONALITY: DBase - StageShow - Implement "Create Sample"
			$showName1 = "The Wordpress Show";
			//if ($this->InTestMode()) $showName1 .= " (".StageShowLibUtilsClass::GetSiteID().")";

			// Sample dates to reflect current date/time
			$showTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+28 days"))." 20:00";
			$showTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+29 days"))." 20:00";
			$showTime3 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+30 days"))." 14:30";
			$showTime4 = date(self::STAGESHOW_DATE_FORMAT, strtotime("+30 days"))." 20:00";
			// Populate table
			$this->sample_showID1 = $this->AddShow($showName1);
			$statusMsg = '';
			// Populate performances table	  
			$perfCount = 4;
			if (defined('STAGESHOW_SAMPLE_PERFORMANCES_COUNT'))
				$perfCount = STAGESHOW_SAMPLE_PERFORMANCES_COUNT;
			$perfID1 = $perfCount >= 1 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime1, "Day1Eve", 80) : -1;
			$perfID2 = $perfCount >= 2 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime2, "Day2Eve", 60) : -1;
			$perfID3 = $perfCount >= 3 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime3, "Day3Mat", 80) : -1;
			$perfID4 = $perfCount >= 4 ? $this->AddSamplePerformance($statusMsg, $this->sample_showID1, $showTime4, "Day3Eve", 60) : -1;
			if (($perfID1 == 0) ||($perfID2 == 0) || ($perfID3 == 0) || ($perfID4 == 0))
			{
				echo '<div id="message" class="error"><p>'.__('Cannot Add Performances', $this->get_domain()).' - '.$statusMsg.'</p></div>';
				return;
			}
			
			if ($sampleDepth < 2)
			{
				// Populate prices table
				$this->priceID_S1_P1_ALL   = $this->AddSamplePrice('Day1Eve', 'All',   STAGESHOW_PRICE_S1_P1_ALL);
				$this->priceID_S1_P2_ADULT = $this->AddSamplePrice('Day2Eve', 'Adult', STAGESHOW_PRICE_S1_P2_ADULT);
				$this->priceID_S1_P3_ADULT = $this->AddSamplePrice('Day3Mat', 'Adult', STAGESHOW_PRICE_S1_P3_ADULT);
				$this->priceID_S1_P4_ALL   = $this->AddSamplePrice('Day3Eve', 'All',   STAGESHOW_PRICE_S1_P4_ALL);
				$this->priceID_S1_P2_CHILD = $this->AddSamplePrice('Day2Eve', 'Child', STAGESHOW_PRICE_S1_P2_CHILD);
				$this->priceID_S1_P3_CHILD = $this->AddSamplePrice('Day3Mat', 'Child', STAGESHOW_PRICE_S1_P3_CHILD);
			}
			
			if (!$this->isDbgOptionSet('Dev_NoSampleSales') && ($sampleDepth < 1))
			{
				// Add some ticket sales
				$saleTime1 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-4 days"))." 17:32:47";
				$saleTime2 = date(self::STAGESHOW_DATE_FORMAT, strtotime("-3 days"))." 10:14:51";
				$saleEMail = 'other@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$saleID = $this->AddSampleSale($saleTime1, 'A.N.', 'Other', $saleEMail, 12.00, 'SQP4KMTNIEXGS5ZBU', PAYPAL_APILIB_SALESTATUS_COMPLETED,
					'1 The Street', 'Somewhere', 'Bigshire', 'BG1 5AT', 'UK');
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_CHILD, 4, STAGESHOW_PRICE_S1_P3_CHILD);
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_ADULT, 1, STAGESHOW_PRICE_S1_P3_ADULT);
				
				$saleEMail = 'mybrother@someemail.co.zz';
				if (defined('STAGESHOW_SAMPLE_EMAIL'))
					$saleEMail = STAGESHOW_SAMPLE_EMAIL;
				$total2 = (4 * STAGESHOW_PRICE_S1_P1_ALL);
				$saleID = $this->AddSampleSale($saleTime2, 'M.Y.', 'Brother', $saleEMail, $total2, '1S34QJHTK9AAQGGVG', PAYPAL_APILIB_SALESTATUS_COMPLETED,
					'The Bungalow', 'Otherplace', 'Littleshire', 'LI1 9ZZ', 'UK');
				$this->AddSampleSaleItem($saleID, $this->priceID_S1_P1_ALL, 4, STAGESHOW_PRICE_S1_P1_ALL);
				
				$timeStamp = current_time('timestamp');
				if (defined('STAGESHOW_EXTRA_SAMPLE_SALES'))
				{
					// Add a lot of ticket sales
					for ($sampleSaleNo = 1; $sampleSaleNo<=STAGESHOW_EXTRA_SAMPLE_SALES; $sampleSaleNo++)
					{
						$saleDate = date(self::MYSQL_DATETIME_FORMAT, $timeStamp);
						$saleFirstName = 'Sample'.$sampleSaleNo;
						$saleLastName = 'Buyer'.$sampleSaleNo;
						$saleEMail = 'extrasale'.$sampleSaleNo.'@sample.org.uk';
						$saleID = $this->AddSampleSale($saleDate, $saleFirstName, $saleLastName, $saleEMail, 12.50, 'TXNID_'.$sampleSaleNo, PAYPAL_APILIB_SALESTATUS_COMPLETED,
						'Almost', 'Anywhere', 'Very Rural', 'Tinyshire', 'TN55 8XX', 'UK');
						$this->AddSampleSaleItem($saleID, $this->priceID_S1_P3_ADULT, 3, STAGESHOW_PRICE_S1_P3_ADULT);
						$timeStamp = strtotime("+1 hour +7 seconds", $timeStamp);
					}
				}
			}
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
		
			// PayPal button(s) created - Add performance to database					
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
			$newVal = $_POST[$settingId.$index];
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
				$sql  = 'SELECT * FROM '.STAGESHOW_SHOWS_TABLE;
				$sql .= ' WHERE '.STAGESHOW_SHOWS_TABLE.'.showName="'.$showName.'"';
				
				$showsEntries = $this->get_results($sql);
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
				$sql .= ' AND '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime="'.$perfDate.'"';
				
				$perfsEntries = $this->get_results($sql);
				$perfID = (count($perfsEntries) > 0) ? $perfsEntries[0]->perfID : 0;
			}
			
			return $perfID;
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
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			$this->perfJoined = false;
			
			$results = parent::get_results($sql, $debugOutAllowed);
			
			return $results;
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
		
		function GetActivePerformancesList()
		{
			$selectFields  = '*';
			$selectFields .= ','.STAGESHOW_PERFORMANCES_TABLE.'.perfID';
			
			$sqlFilters['activePerfs'] = true;
			
			$sql = "SELECT $selectFields FROM ".STAGESHOW_PERFORMANCES_TABLE;
			$sql .= " LEFT JOIN ".STAGESHOW_SHOWS_TABLE.' ON '.STAGESHOW_SHOWS_TABLE.'.showID='.STAGESHOW_PERFORMANCES_TABLE.'.showID';
			
			// Add SQL filter(s)
			$sql .= $this->GetWhereSQL($sqlFilters);
			$sql .= $this->GetOptsSQL($sqlFilters);
			
			$sql .= ' ORDER BY '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime';
			
			$perfsListArray = $this->get_results($sql);

			return $perfsListArray;
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
			$sql = ' , '.STAGESHOW_PRICES_TABLE.'.priceType';
			
			return $sql;
		}
				
		function GetPricesList($sqlFilters, $activeOnly = false)
		{
			if ($activeOnly)
			{
				if (!isset($this->allowAdminOnly)) $sqlFilters['publicPrices'] = true;
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
		
		function UpdatePricePerfID($priceID, $newPerfID)
		{
			$sqlSET = 'perfID="'.$newPerfID.'"';
			return $this->UpdatePriceEntry($priceID, $sqlSET);
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
		
		function GetTicketTypes($perfID)
		{
			$sql  = 'SELECT * FROM '.STAGESHOW_PRICES_TABLE;
			$sql .= ' JOIN '.STAGESHOW_PERFORMANCES_TABLE.' ON '.STAGESHOW_PERFORMANCES_TABLE.'.perfID='.STAGESHOW_PRICES_TABLE.'.perfID';
			$sql .= ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfID="'.$perfID.'"';
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
    
		function GetSalesQtyByShowID($showID)
		{
			$sqlFilters['showID'] = $showID;
			return $this->GetSalesQty($sqlFilters);
		}
				
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
				
		function GetSalesQueryFields($sqlFilters = null)
		{		
			if (isset($sqlFilters['addTicketFee']))
			{
				$sql  = "ticketID, saleTxnId, saleStatus, saleFirstName, saleLastName, showName, perfDateTime, priceType, ticketQty, ticketPaid, ";
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
			if (isset($sqlFilters['activePerfs']))
			{
				$timeNow = current_time('mysql');
				$sqlWhere = ' WHERE '.STAGESHOW_PERFORMANCES_TABLE.'.perfDateTime>"'.$timeNow.'" ';
				return $sqlWhere;
			}
			
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
		
		// Add Sale - Address details are optional
		function AddSampleSale($saleDateTime, $saleFirstName, $saleLastName, $saleEMail, $salePaid, $saleTxnId, $saleStatus, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry, $salePPPhone = '')
		{
			$salePaid += $this->GetTransactionFee();
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$salePaid = $salePaid/STAGESHOW_SAMPLEPRICE_DIVIDER;
			}
			$salePaid = number_format($salePaid, 2);
			
			return parent::AddSampleSale($saleDateTime, $saleFirstName, $saleLastName, $saleEMail, $salePaid, $saleTxnId, $saleStatus, $salePPStreet, $salePPCity, $salePPState, $salePPZip, $salePPCountry, $salePPPhone);
		}
				
		function AddSampleSaleItem($saleID, $stockID, $qty, $paid, $saleExtras = array())
		{
			if (defined('STAGESHOW_SAMPLEPRICE_DIVIDER'))
			{
				$paid = $paid/STAGESHOW_SAMPLEPRICE_DIVIDER;
				$paid = number_format($paid, 2);
			}
			
			return parent::AddSaleItem($saleID, $stockID, $qty, $paid, $saleExtras);
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
				
		function TotalSalesField($sqlFilters)
		{
			// totalQty may not include Pending sales (i.e. saleStatus=Checkout)) - add it here!
			$sql  = '  SUM(ticketQty) AS totalQty ';
			
			$statusOptions  = '(saleStatus="'.PAYPAL_APILIB_SALESTATUS_COMPLETED.'")';
			$statusOptions .= ' OR ';
			$statusOptions .= '(saleStatus="'.STAGESHOW_SALESTATUS_RESERVED.'")';
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
				
		function AddSaleFields(&$salesListArray)
		{
			for ($i=0; $i<count($salesListArray); $i++)
			{
				$salesListArray[$i]->ticketName = $salesListArray[$i]->showName.' - '.StageShowLibSalesDBaseClass::FormatDateForDisplay($salesListArray[$i]->perfDateTime);
				$salesListArray[$i]->ticketType = $salesListArray[$i]->priceType;
			}			
		}
		
		function GetPricesListWithSales($saleID)
		{
			$selectFields  = '*';
			
			$sqlWhere1 = ' WHERE '.STAGESHOW_TICKETS_TABLE.'.saleID = "'.$saleID.'"';
			$sqlWhere2 = ' ';
			if (defined('RUNSTAGESHOWDEMO'))
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
		
		function DeleteTickets($saleID)
		{
			// Delete a show entry
			$sql  = 'DELETE FROM '.STAGESHOW_TICKETS_TABLE;
			$sql .= ' WHERE '.STAGESHOW_TICKETS_TABLE.".saleID=$saleID";
		 
			$this->query($sql);
		}
		
		function DeleteSale($saleID)
		{
			parent::DeleteSale($saleID);

			$this->DeleteTickets($saleID);			
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
    
		function LogToFile($Filepath, $LogLine, $OpenMode = 0)
		{
			// Use global values for OpenMode
			
			// Create a filesystem object
			if (($OpenMode == self::ForAppending) || ($OpenMode == 0))
			{
				$logFile = fopen($Filepath,"ab");
			}
			else
			{
				$logFile = fopen($Filepath,"wb");
			}

			// Write log entry
			if ($logFile != 0)
			{
				$LogLine .= "\n";
				fwrite($logFile, $LogLine, strlen($LogLine));
				fclose($logFile);

				$rtnStatus = true;
			}
			else
			{
				echo "Error writing to $Filepath<br>\n";
				//echo "Error was $php_errormsg<br>\n";
				$rtnStatus = false;
			}

			return $rtnStatus;
		}

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
		
		function AddSaleFromTrolley($saleID, $cartEntry, $saleExtras = array())
		{
			$this->AddSaleItem($saleID, $cartEntry->itemID, $cartEntry->qty, $cartEntry->price, $saleExtras);
		}

		function echoHTML($html)
		{
			$search = array ("'<br>[\s]*\n'i",				
											 "'<p>[\s]*\n'i",					
											 "'<br>'i",								// End of line
											 "'<p>'i",								// Paragraph
											 "'<'i",									// Less than
											 "'>'i",									// Less than
											 "'\n'i");									// Greater than

			$replace = array ("<br>",
												"<p>",
												"<br>\n",
												"<p>\n",
												"&lt;",								
												"&gt;",								
												"<br>\n");

			return preg_replace($search, $replace, $html);
		}		
		
// ----------------------------------------------------------------------
//
//			Generic Utilities Function
//
// ----------------------------------------------------------------------
		function GetInfoServerURL($pagename)
		{
			$filename = $pagename.'.php';
			
			if (defined('STAGESHOW_INFO_SERVER_URL'))
			{
				$updateCheckURL = $this->get_pluginURI().'/';
				$pageURLPosn = strrpos($updateCheckURL, '/StageShow');
				$updateCheckURL = STAGESHOW_INFO_SERVER_URL.substr($updateCheckURL, $pageURLPosn+1, strlen($updateCheckURL));
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
				$latest['LatestNews'] = $this->GetHTTPPage($updateCheckURL);	
				if (strlen($latest['LatestNews']) <= 2)
				{
					$latest['Status'] = "HTTP_Empty";
					$latest['LatestNews'] = '';
				}
				else
				{
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
		
		function FormatEMailField($tag, $field, $saleDetails)
		{
			if ($tag =='[ticketSeat]') 
				return '';
			
			return parent::FormatEMailField($tag, $field, $saleDetails);
		}	
		
	}
}

?>