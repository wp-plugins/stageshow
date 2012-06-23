<?php
/* 
Description: Code for Overview Page
 
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

include STAGESHOW_INCLUDE_PATH.'mjslib_table.php';

if (!class_exists('StageShowOverviewAdminListClass')) 
{
	class StageShowOverviewAdminListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
			// Call base constructor
			$editMode = false;
			parent::__construct($env, $editMode);
				
			$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "overviewtab";
		}
		
		function GetRecordID($result)
		{
			return $result->showID;
		}
		
		function GetMainRowsDefinition()
		{
			return array(
				array('Label' => 'Show',         'Id' => 'showName',    'Type' => MJSLibTableClass::TABLEENTRY_VALUE, ),
				array('Label' => 'Performances', 'Id' => 'perfCount',   'Type' => MJSLibTableClass::TABLEENTRY_VALUE, ),						
				array('Label' => 'Tickets Sold', 'Id' => 'salesCount',  'Type' => MJSLibTableClass::TABLEENTRY_VALUE, ),						
			);
		}
		
		function GetDetailsRowsDefinition()
		{
			$ourOptions = array(
//				array('Label' => 'Name',	                     'Id' => 'showName',      'Type' => MJSLibTableClass::TABLEENTRY_TEXT, 'Len' => PAYPAL_APILIB_PPSALENAME_TEXTLEN,      'Size' => PAYPAL_APILIB_PPSALENAME_EDITLEN, ),
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
				array('Type' => MJSLibTableClass::TABLEENTRY_FUNCTION, 'Func' => 'ShowSaleDetails'),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsFooter(), $ourOptions);
			
			return $ourOptions;
		}
		
		function ShowSaleDetails($result)
		{		
			$saleResults = $this->myDBaseObj->GetPerformancesListByShowID($result->showID);

			$env = $this->env;
			$salesList = new StageShowOverviewAdminDetailsListClass($env, $this->editMode);	
			
			// Set Rows per page to disable paging used on main page
			$salesList->enableFilter = false;
			
			ob_start();	
			$salesList->OutputList($saleResults);	
			$saleDetailsOoutput = ob_get_contents();
			ob_end_clean();

			return $saleDetailsOoutput;
		}
		
	}
}

if (!class_exists('StageShowOverviewAdminDetailsListClass')) 
{
	class StageShowOverviewAdminDetailsListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->HeadersPosn = MJSLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "showtab".$result->showID;
		}
		
		function GetRecordID($result)
		{
			return $result->perfID;
		}
		
		function GetMainRowsDefinition()
		{
			$ourOptions = array(
				array('Label' => 'Performance',  'Id' => 'perfDateTime', 'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),
				array('Label' => 'Quantity',     'Id' => 'totalQty',     'Type' => MJSLibTableClass::TABLEENTRY_VIEW, ),						
			);
			
			$ourOptions = array_merge(parent::GetDetailsRowsDefinition(), $ourOptions);
			return $ourOptions;
		}
	}
}

include STAGESHOW_INCLUDE_PATH.'mjslib_admin.php';      

if (!class_exists('StageShowOverviewAdminClass')) 
{
	class StageShowOverviewAdminClass extends MJSLibAdminClass // Define class
	{
		function __construct($env)
		{
			parent::__construct($env);

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
?>			
			<div class="wrap">
			<div id="icon-stageshow" class="icon32"></div>
			<h2>
				<?php echo $myPluginObj->pluginName.' - '.__('Overview', STAGESHOW_DOMAIN_NAME); ?>
			</h2>
<?php

			if(isset($_POST['createsample']))
			{
				$myPluginObj->CreateSample();
			}
			
			// Stage Show Overview HTML Output - Start 
			$this->Output_Overview($env);
			$this->Output_ShortcodeHelp();
			$this->Output_UpdateServerHelp();
			$this->Output_UpdateInfo();
			
			echo '</div>';
		}
		
		function Output_Overview($env)
		{
			$myDBaseObj = $this->myDBaseObj;
			$results = $myDBaseObj->GetAllShowsList();
						
?>
						<br>
							<h2>Shows</h2>
							<?php	
	
			if(count($results) == 0)
			{
				if ($myDBaseObj->CheckIsConfigured())
				{
					echo "<div class='noconfig'>".__('No Show Configured', STAGESHOW_DOMAIN_NAME)."</div>\n";
					echo '
					<form method="post" action="admin.php?page='.STAGESHOW_MENUPAGE_ADMINMENU.'">
					<br>
						<input class="button-primary" type="submit" name="createsample" value="'.__('Create Sample', STAGESHOW_DOMAIN_NAME).'"/>
					<br>
					</form>';
				}
			}
			else
			{
				foreach ($results as $key=>$result)
				{
					$perfsList = $myDBaseObj->GetPerformancesListByShowID($result->showID);
					
					$results[$key]->perfCount = count($perfsList);
					$results[$key]->salesCount = $myDBaseObj->GetSalesQtyByShowID($result->showID);
				}
			
				$overviewList = new StageShowOverviewAdminListClass($env);		
				$overviewList->OutputList($results);		
			}
		}
		
		function Output_ShortcodeHelp()
		{
?>
			<br>			
				<h2>Shortcodes</h2>
				StageShow generates output to your Wordpress pages for the following shortcodes:
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th>Shortcode</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[sshow-boxoffice]</td>
						<td>Add Box Office for all performances</td>
					</tr>
				</tbody>
			</table>
<?php
		}

		function Output_UpdateServerHelp()
		{
		}
		
		function Output_UpdateInfo()
		{
			if (defined('STAGESHOW_PLUS_UPDATE_SERVER_URL'))
			{
				$msg = "<strong>Using Custom Update Server - Root URL=".STAGESHOW_PLUS_UPDATE_SERVER_URL."<br>\n";
				echo '<br><div id="message" class="error"><p>'.$msg.'</p></div>';
			}
			
			// Get News entry from server
			$myDBaseObj = $this->myDBaseObj;
			$latest = $myDBaseObj->GetLatestNews();

			// Deal with "Not Found" error ....
			if ($latest === '')
				return;
			
?>
			<br>
				<h2>StageShow Updates</h2>
					<table class="widefat" cellspacing="0">
						<thead>
							<tr>
								<th>Latest Updates</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
<?php
				echo $latest;
?>
								</td>
							</tr>
						</tbody>
					</table>
<?php
		}

	}
}

?>
