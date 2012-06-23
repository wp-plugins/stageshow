<?php /* Hide template from public access ... Next line is email subject - Following lines are email body
[organisation] Bookings Summary
<html>
<a href="[url]"><img src="[logoimg]" alt="[organisation]" border="0"></a><br>
<br>
<h3>Bookings Summary for [organisation]:</h3>
<br>
<table>
<tr>
<td align="center">Show Name</td>
<td align="center">Performance</td>
<td align="center">Quantity</td>
</tr>
[startloop]
<tr>
<td align="left">[showName]</td>
<td align="left">&nbsp;[perfDateTime]&nbsp;</td>
<td align="center">&nbsp;[totalQty]&nbsp;</td>
</tr>
[endloop]
<table>
<br>
For further details log on to the web site at <a href="[url]/wp-admin/">[url]/wp-admin/</a><br>
</html>
*/ ?>
