<?php
/* 
Description: Code for Managing Prices Configuration
 
Copyright 2011 Malcolm Shergold

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

//		function OutputContent_Prices()
		{
			global $myShowObj;
			global $myDBaseObj;
			global $myPayPalAPILiveObj;
			global $myPayPalAPITestObj;
      
			$delpriceId = 0;
			
      $columns = array(
		    'perfID' => __('Performance', STAGESHOW_DOMAIN_NAME),
		    'priceType' => __('Type', STAGESHOW_DOMAIN_NAME),
		    'priceValue' => __('Price', STAGESHOW_DOMAIN_NAME),
		    'priceDelete' => __(' ', STAGESHOW_DOMAIN_NAME)
	    );
	
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('priceID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
      register_column_headers('sshow_prices_list', $columns);	

			$pricesMsg = '';
			$showID = 0;
			
			echo '<div class="wrap">';
			if (isset($_POST['savebutton']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				$showID = $_POST['showID'];
				$results = $myDBaseObj->GetPricesListByShowID($showID);

				// Verify that Price Types are unique for each performance				
				
        if(count($results) > 0)
        {
	        foreach($results as $result)
					{
							$newPerfID = $_POST['perfID'.$result->priceID];
							$newPriceType = stripslashes($_POST['priceType'.$result->priceID]);
							$newPriceValue = stripslashes($_POST['priceValue'.$result->priceID]);
							
							// Generate an entry that consists of the PerformanceID and the Price Type
							$priceEntry = $newPerfID.'-'.$newPriceType;
							if (isset($entriesList[$priceEntry]))
							{
								// Convert the perfID to a Performance Date & Time to display to the user
								$perfsList = $myDBaseObj->GetPerformancesListByPerfID($newPerfID);
								
								$pricesMsg = __('Duplicated Price Entry', STAGESHOW_DOMAIN_NAME).' ('.$perfsList[0]->perfDateTime.' - '.$newPriceType.')';
								break;
							}
							
							// Verify that the price value is not empty
							if (strlen($newPriceValue) == 0)
							{
								$pricesMsg = __('Price Not Specified', STAGESHOW_DOMAIN_NAME).' ('.$perfsList[0]->perfDateTime.' - '.$newPriceType.')';
								break;
							}
							
							// Verify that the price value is a numeric value
							if (!is_numeric($newPriceValue))
							{
								$pricesMsg = __('Invalid Price Entry', STAGESHOW_DOMAIN_NAME).' ('.$newPriceValue.')';
								break;
							}
							
							$entriesList[$priceEntry] = true;
					}
				}
				
				if ($pricesMsg !== '')
				{
					echo '<div id="message" class="updated"><p>'.__('Settings have NOT been saved', STAGESHOW_DOMAIN_NAME).'. '.$pricesMsg.'</p></div>';
				}
        else 
				{
					$perfsList = array();
				
					if(count($results) > 0)
					{
						foreach($results as $result)
						{
								$pricesUpdated = false;
								
								$newPerfID = $_POST['perfID'.$result->priceID];
								if ($newPerfID != $result->perfID)
								{
									$myDBaseObj->UpdatePricePerfID($result->priceID, $newPerfID);
									$result->perfID = $newPerfID;
									$pricesUpdated = true;
								}
								
								$newPriceType = stripslashes($_POST['priceType'.$result->priceID]);
								if ($newPriceType != $result->priceType)
								{
									$myDBaseObj->UpdatePriceType($result->priceID, $newPriceType);
									$result->priceType = $newPriceType;
									$pricesUpdated = true;
								}

								$newPriceValue = stripslashes($_POST['priceValue'.$result->priceID]);
								if ($newPriceValue != $result->priceValue)
								{
									$myDBaseObj->UpdatePriceValue($result->priceID, $newPriceValue);
									$result->priceValue = $newPriceValue;
									$pricesUpdated = true;
								}
								
								if ($pricesUpdated)
									$perfsList[count($perfsList)] = $result;
									
						} // End foreach
						
						if (count($perfsList) > 0)
							$myDBaseObj->UpdateCartButtons($perfsList);
					}
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}			
			else if(isset($_POST['addpricebutton']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				$showID = $_POST['showID'];
				
				// Performance ID of first performance is passed with call - Type ID is null ... AddPrice() will add (unique) value
				$perfID = $_POST['perfID'];
				$myDBaseObj->AddPrice($perfID, '', '0.00');
				
				// Note: Commented out ... buttons are only updated when entry is edited by user ....
/*								
				$myPayPalAPITestObj->UpdateButton($result->perfPayPalTESTButtonID, $description, $reference, $priceIDs, $ticketPrices);
				$myPayPalAPITestObj->UpdateInventory($result->perfPayPalTESTButtonID, $quantity);
*/
				echo '<div id="message" class="updated"><p>'.__('Settings have been saved', STAGESHOW_DOMAIN_NAME).'.</p></div>';
			}
			else if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				switch ($_GET['action'])
				{
					case 'delete':
						$delpriceId = $_GET['id']; 
						break;
				}
			}

			if ($delpriceId > 0)
			{
				// Don't delete if any tickets have been sold with this price
				$results = $myDBaseObj->GetTicketsListByPriceID($delpriceId);
				if (count($results) > 0)
				{
					echo '<div id="message" class="updated"><p>'.__('Price cannot be deleted - Tickets already sold!', STAGESHOW_DOMAIN_NAME).'</p></div>';
				}
				else
				{	
					// Delete a ticket price entry
					$myDBaseObj->DeletePriceByPriceID($delpriceId);

					echo '<div id="message" class="updated"><p>'.__('Price entry deleted', STAGESHOW_DOMAIN_NAME).'.</p></div>';
				}
			}
			
			// Stage Show Prices HTML Output - Start 
?>
<script type="text/javascript" src="<?php echo STAGESHOW_URL.'js/stageshow.js'; ?>"></script>
<div class="wrap">
  <div id="icon-stageshow" class="icon32"></div>
	<h2><?php echo $myShowObj->pluginName.' - '.__('Prices Editor', STAGESHOW_DOMAIN_NAME); ?></h2>
<?php
$showLists = $myDBaseObj->GetAllShowsList();
if (count($showLists) == 0)
{
	echo __('No Show Configured', STAGESHOW_DOMAIN_NAME)."<br>\n";
}
foreach ($showLists as $showList)
{
	$perfsLists = $myDBaseObj->GetPerformancesListByShowID($showList->showID);
?>
	<br></br>
	<form method="post" action="admin.php?page=sshow_prices">
		<h3><?php echo($showList->showName); ?></h3>
<?php 
if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
if (count($perfsLists) == 0) 
{ 
	_e('Show has NO Performances', STAGESHOW_DOMAIN_NAME); 
} 
else 
{ 
$results = $myDBaseObj->GetPricesListByShowID($showList->showID);
if(count($results) == 0)
{
	_e('Show has NO Prices', STAGESHOW_DOMAIN_NAME); 
	echo "<br>\n";
}
else
{
?>
		<table class="widefat" cellspacing="0">
      <thead>
        <tr>
          <?php print_column_headers('sshow_prices_list'); ?>
        </tr>
      </thead>

      <tfoot>
        <tr>
          <?php print_column_headers('sshow_prices_list', false); ?>
        </tr>
      </tfoot>
      <tbody>
        <?php
	foreach($results as $result)
	{
		if (($pricesMsg !== '') && ($showList->showID == $showID))
		{
			// Error updating values - Get value(s) from form controls
			$perfID = $_POST['perfID'.$result->priceID];;
			$priceType = stripslashes($_POST['priceType'.$result->priceID]);
			$priceValue = stripslashes($_POST['priceValue'.$result->priceID]);
		}
		else
		{
			// Get value(s) from database
			$perfID = $result->perfID;
			$priceType = $result->priceType;
			$priceValue = $result->priceValue;
		}
			echo '<tr>';
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
				echo '<td>'.$result->priceID.'</td>';
			echo '
	<td>
		<select name=perfID'.$result->priceID.'>'."\n";
		foreach($perfsLists as $perfsEntry)
		{
			// $result->perfDateTime
			$selectEntry = ($perfsEntry->perfID==$perfID?'selected=""':'');
			echo '<option value="',$perfsEntry->perfID.'" '.$selectEntry.'>'.$perfsEntry->perfDateTime.'&nbsp;&nbsp;</option>'."\n";
		}
		echo '
		</select>
	</td>
	<td><input name=priceType'.$result->priceID.' type=text maxlength='.STAGESHOW_PRICETYPE_TEXTLEN.' size='.STAGESHOW_PRICETYPE_TEXTLEN.' value="'.$priceType.'" /></td>
	<td><input name=priceValue'.$result->priceID.' type=text maxlength=6 size=6 value="'.$priceValue.'" /></td>
  <td style="background-color:#FFF">
	';
		$deleteLink = 'admin.php?page=sshow_prices&action=delete&id='.$result->priceID;
		$deleteLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($deleteLink, plugin_basename(__FILE__)) : $deleteLink;
		$deleteLink = '<a href="'.$deleteLink.'" onclick="javascript:return confirmDelete(\'Price Entry\')">Delete</a>';
		echo $deleteLink;
		echo '
	</td>
	</tr>';
	   
	}	// End of foreach($results as $result) ....
?>
      </tbody>
    </table>
<?php
} // if(count($results) > 0) ....
?>
      <br></br>
      <input type="hidden" name="showID" value="<?php echo $showList->showID; ?>"/>
      <input type="hidden" name="perfID" value="<?php echo $perfsLists[0]->perfID; ?>"/>
      <input class="button-secondary" type="submit" name="addpricebutton" value="<?php _e('Add New Price', STAGESHOW_DOMAIN_NAME) ?>"/>
<?php 
	if(count($results) > 0)
	{
		echo '<input class="button-primary" type="submit" name="savebutton" value="'.__('Save Settings', STAGESHOW_DOMAIN_NAME).'"/>';
	}
} 
?>
		</form>
<?php
}
?>
</div>

<?php
			// Stage Show Prices HTML Output - End 
		}				 
?>