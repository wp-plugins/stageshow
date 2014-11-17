<?php /* Hide template from public access ... Next line is email subject - Following lines are email body
[organisation] Booking Confirmation
<html>
<a href="[url]"><img src="[logoimg]" alt="[organisation]" border="0"></a><br><br>
Thank you for your booking online with [organisation]<br>
<br>
<h3>Order Details:</h3>
<br>
Purchaser:  [saleName]<br>
EMail:      [saleEMail]<br>
Reference:  [saleTxnId]<br>
Address:    [salePPStreet] <br>
            [salePPCity] <br>
            [salePPState]<br>
            [salePPZip]<br>

<h3>Purchased:</h3>
[startloop]
[ticketQty] x [ticketType] for [ticketName] <br> [endloop]
<br>
<h3>Payment:</h3>
Total: [salePaid]<br>
<br>
[saleBarcode]<br>
Any queries relating to this booking should be emailed to <a href="mailto:[salesEMail]">[salesEMail]</a><br>
<br>
For further information on shows please visit our web site on <a href="[url]">[url]</a><br>
</html>
*/ ?>
