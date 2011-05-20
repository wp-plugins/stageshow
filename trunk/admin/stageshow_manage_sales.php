<?php
/* 
Description: Code for Sales Page
 
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

		{
			global $myShowObj;
			global $myDBaseObj;
      
			$detailsSaleId = 0;
			$deleteSaleId = 0;
			$salesFor = '';
			
			if (isset($_GET['action']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				switch ($_GET['action'])
				{
					case 'details':
						$detailsSaleId = $_GET['id']; 
						break;
						
					case 'delete':
						$deleteSaleId = $_GET['id']; 
						break;
						
					case 'show':
						$showId = $_GET['id']; 
						$results = $myDBaseObj->GetSalesListByShowID($showId);	
						$salesFor = '- '.$results[0]->showName.' ';
						break;
						
					case 'perf':
						$perfId = $_GET['id']; 
						$results = $myDBaseObj->GetSalesListByPerfID($perfId);
						$salesFor = '- '.$results[0]->showName.' ('.$myDBaseObj->FormatDateForDisplay($results[0]->perfDateTime).') ';
						break;
				}
			}

			if (isset($_POST['emailsale']))
			{
				check_admin_referer(plugin_basename(__FILE__)); // check nonce created by wp_nonce_field()
				
				$detailsSaleId = $_POST['id'];
				$myDBaseObj->EMailSale($detailsSaleId);
			}
			
			if ($deleteSaleId > 0)
			{
				$myDBaseObj->DeleteSale($deleteSaleId);
			}

			if ($salesFor == '')			
				$results = $myDBaseObj->GetAllSalesList();		// Get list of sales (one row per sale)

			if ($detailsSaleId > 0)
			{
				$columns = array(
					'saleShowName' => __('Show', STAGESHOW_DOMAIN_NAME),
					'ticketType' => __('Type', STAGESHOW_DOMAIN_NAME),
					'quantity' => __('Quantity', STAGESHOW_DOMAIN_NAME)
				);
			}
			else
			{
				$columns = array(
					'saleName' => __('Name', STAGESHOW_DOMAIN_NAME),
					'saleDate' => __('Transaction Date', STAGESHOW_DOMAIN_NAME),
					'saleStatus' => __('Status', STAGESHOW_DOMAIN_NAME),
					'noOfTickets' => __('No of Tickets', STAGESHOW_DOMAIN_NAME),
					'saleActions' => ''
				);
			}
			
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
			{
				// Add the ID column
				$columns = array_merge(array('saleID' => __('ID', STAGESHOW_DOMAIN_NAME)), $columns); 
			}
			
      register_column_headers('sshow_sales_list', $columns);	

			echo '<div class="wrap">';

			// Stage Show Sales HTML Output - Start 
?>
			<div class="wrap">
				<div id="icon-stageshow" class="icon32"></div>
				<h2><?php echo $myShowObj->pluginName.' '.$salesFor.' - '.__('Sales Log', STAGESHOW_DOMAIN_NAME); ?></h2>
				<br></br>
				<form method="post" action="admin.php?page=sshow_sales">
					<h3>
<?php 
	if ($detailsSaleId > 0)	
		_e('Sale Details', STAGESHOW_DOMAIN_NAME); 
	else
		_e('Summary', STAGESHOW_DOMAIN_NAME); 
?>
					</h3>
							<?php
if ( function_exists('wp_nonce_field') ) wp_nonce_field(plugin_basename(__FILE__));
if(count($results) == 0)
{
	echo __('No Sales', STAGESHOW_DOMAIN_NAME)."<br>\n";
}
else 
{
	if ($detailsSaleId > 0)
	{
		$ticketsList = $myDBaseObj->GetTicketsListBySaleID($detailsSaleId);	// Get list of tickets for a single sale
		
		if (!defined('STAGESHOW_STREET_LABEL')) 
			define ('STAGESHOW_STREET_LABEL', 'Address');
		if (!defined('STAGESHOW_CITY_LABEL')) 
			define ('STAGESHOW_CITY_LABEL', 'Town/City');
		if (!defined('STAGESHOW_STATE_LABEL')) 
			define ('STAGESHOW_STATE_LABEL', 'County');
		if (!defined('STAGESHOW_ZIP_LABEL')) 
			define ('STAGESHOW_ZIP_LABEL', 'Postcode');
?>
		<input type="hidden" name="id" value="<?php echo $detailsSaleId ?>"/>
		<table>
		<tr valign="top" id="tags">
			<td><?php _e('Name', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->saleName) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('EMail', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->saleEMail) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('PayPal Username', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePPName) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(STAGESHOW_STREET_LABEL, STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePPStreet) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(STAGESHOW_CITY_LABEL, STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePPCity) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(STAGESHOW_STATE_LABEL, STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePPState) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e(STAGESHOW_ZIP_LABEL, STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePPZip) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Paid', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->salePaid) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Transaction Date/Time', STAGESHOW_DOMAIN_NAME) ?>:&nbsp</td>
			<td><?php echo($ticketsList[0]->saleDateTime) ?></td>
		</tr>
		<tr valign="top" id="tags">
			<td><?php _e('Transaction ID', STAGESHOW_DOMAIN_NAME) ?>:</td>
			<td><?php echo($ticketsList[0]->saleTxnId) ?></td>
		</tr>
	</table>
	<br></br>
	<?php						
	}
?>

	<table class="widefat" cellspacing="0">
		<thead>
			<tr>
				<?php print_column_headers('sshow_sales_list'); ?>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<?php print_column_headers('sshow_sales_list', false); ?>
			</tr>
		</tfoot>
		<tbody>
			<?php
	if ($detailsSaleId > 0)
	{
		foreach($ticketsList as $ticket)
		{
			echo '<tr>';
			if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
				echo '<td>'.$ticket->ticketID.'</td>';
			echo '
				<td>'.$ticket->ticketName.'</td>
				<td>'.$ticket->ticketType.'</td>
				<td>'.$ticket->ticketQty.'</td>
			</tr>';
		}
	}
	else
	{
		$lastDaleId = 0;
		foreach($results as $result)
		{
			if ($lastDaleId != $result->saleID)
			{
				$lastDaleId = $result->saleID;
				
				// For each sale .... find the number of tickets
				$noOfTickets = 0;
				$ticketsList = $myDBaseObj->GetTicketsListBySaleID($result->saleID);
				foreach($ticketsList as $ticket)
				{
					$noOfTickets += $ticket->ticketQty;
				}
				echo '<tr>';
				if ($myDBaseObj->adminOptions['Dev_ShowDBIds'])
					echo '<td>'.$ticket->saleID.'</td>';
				echo '
				<td>'.$result->saleName.'</td>
				<td>'.$result->saleDateTime.'</td>
				<td>'.$result->saleStatus.'</td>
				<td>'.$noOfTickets.'</td>
				<td style="background-color:#FFF">
	';
		$modLink = 'admin.php?page=sshow_sales&action=details&id='.$result->saleID;
		$modLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($modLink, plugin_basename(__FILE__)) : $modLink;
		$modLink = '<a href="'.$modLink.'">Details</a>,&nbsp';
		echo $modLink;
		$modLink = 'admin.php?page=sshow_sales&action=delete&id='.$result->saleID;
		$modLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($modLink, plugin_basename(__FILE__)) : $modLink;
		$modLink = '<a href="'.$modLink.'" onclick="javascript:return confirmDelete(\'Sale to '.$result->saleName.' ('.$result->saleDateTime.')\')">Delete</a>';
		echo $modLink;
		echo '				
				</td>
				</tr>';
			}
		}
	}
}
?>
      </tbody>
    </table>
      <br></br>
<?php
	if ($detailsSaleId > 0)
      echo '
			<input class="button-secondary" type="submit" name="showsales" value="'.__('Back to Sales Summary', STAGESHOW_DOMAIN_NAME).'">
			&nbsp;
      <input class="button-secondary" type="submit" name="emailsale" value="'.__('Send Confirmation EMail', STAGESHOW_DOMAIN_NAME).'">
			';
?>			
    </form>
</div>

<?php
        // Stage Show Sales HTML Output - End
		}
?>