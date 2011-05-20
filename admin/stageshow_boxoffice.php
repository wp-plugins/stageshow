<?php
/* 
Description: Code for Box Office Page
 
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
      global $myDBaseObj;
      global $myPayPalAPILiveObj;
      global $myPayPalAPITestObj;
			
			// Choose PayPal target environment
			if ($myDBaseObj->adminOptions['PayPalEnv'] === 'live')
				$myPayPalAPIObj = $myPayPalAPILiveObj;
			else
				$myPayPalAPIObj = $myPayPalAPITestObj;
			
      // Get all database entries for this show ... ordered by date/time then ticket type
      $results = $myDBaseObj->GetPricesListByShowID($showID);
			$perfCount = 0;
			
      if (count($results) == 0) return;
      
      $hiddenTags  = "\n";
      $hiddenTags .= '<input type="hidden" name="cmd" value="_s-xclick"/>'."\n";
      if (strlen($myDBaseObj->adminOptions['PayPalLogoImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="image_url" value="'.$myDBaseObj->GetURL($myDBaseObj->adminOptions['PayPalLogoImageURL']).'"/>'."\n";
      }
      if (strlen($myDBaseObj->adminOptions['PayPalHeaderImageURL']) > 0) {
        $hiddenTags .= '<input type="hidden" name="cpp_header_image" value="'.$myDBaseObj->GetURL($myDBaseObj->adminOptions['PayPalHeaderImageURL']).'"/>'."\n";
      }

      $hiddenTags .= '<input type="hidden" name="on0" value="TicketType"/>'."\n";      
      $hiddenTags .= '<input type="hidden" name="SiteURL" value="'.get_site_url().'"/>'."\n";
      
      if (strlen($myPayPalAPIObj->PayPalNotifyURL) > 0)
	      $notifyTag  = '<input type="hidden" name="notify_url" value="'.$myPayPalAPIObj->PayPalNotifyURL.'"/>'."\n";
      else
				$notifyTag = '';
				
			$altTag = $myDBaseObj->adminOptions['OrganisationID'].' '.__('Tickets', STAGESHOW_DOMAIN_NAME);
?>
			<div class="wrap">
				<div id="icon-stageshow" class="icon32"></div>
				<h2>
					<?php echo $results[0]->showName; ?>
				</h2>
				<br></br>
					<?php      
      foreach($results as $result)
			{
				if (!$myDBaseObj->IsPerfExpired($result))
				{
					$perfCount++;
					if ($perfCount == 1) echo '
					<table class="widefat" cellspacing="0">
						<tr>
							<td>Date/Time</td>
							<td>Ticket Type</td>
							<td>Price</td>
							<td>Qty</td>
							<td>&nbsp;</td>
						</tr>';
					
					$perfPayPalButtonID = ($myDBaseObj->adminOptions['PayPalEnv'] === 'live' ? $result->perfPayPalLIVEButtonID : $result->perfPayPalTESTButtonID);
					//echo "perfPayPalButtonID = $perfPayPalButtonID<br>\n";
					
					// Line below is test code to use different Notify URLs for each button
					//$notifyTag = '<input type="hidden" name="notify_url" value="'.get_site_url().'/wp-content/plugins/stageshow/stageshow_NotifyURL_x'.$result->perfID.'.php"/>'."\n";
					echo '<tr>
						<form target="paypal" action="'.$myPayPalAPIObj->PayPalURL.' method="post">
						'.$hiddenTags.'
						'.$notifyTag.'
						<input type="hidden" name="os0" value="'.$result->priceType.'"/>
						<input type="hidden" name="hosted_button_id" value="'.$perfPayPalButtonID.'"/>
						<td>'.$myDBaseObj->FormatDateForDisplay($result->perfDateTime).'</td>
						<td>'.$result->priceType.'</td>
						<td>'.$result->priceValue.'</td>
						<td>
							<select name="quantity">
								<option value="1" selected="">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
							</select>
						</td>
						<td>';
					
					if ($myDBaseObj->IsPerfExpired($result)) echo '&nbsp;';
					else if ($result->perfSeats == 0) echo '
						'.__('Sold Out', STAGESHOW_DOMAIN_NAME);
					else echo '
						<input type="submit" value="Add"  alt="'.$altTag.'"/>';
						echo '
						</td>
						</form>
					</tr>';
				}
			}
			if ($perfCount == 0) 
				echo __('Bookings closed', STAGESHOW_DOMAIN_NAME)."<br>\n";
			else echo '
			  </table>';
?>
			<br></br>
</div>

<?php
			// Stage Show BoxOffice HTML Output - End 
		}				 
?>