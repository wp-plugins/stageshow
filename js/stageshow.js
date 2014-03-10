
function seatAvailability(seatId)
{
	var bookedIndex = bookedSeats.indexOf(seatId);
	if (bookedIndex >= 0) return 'booked';
	
	var selectedIndex = selectedSeats.indexOf(seatId);
	if (selectedIndex >= 0) return 'selected';
	
	return '';
}

function getZoneNo(obj)
{
	var className = obj.className;
	var posn = className.indexOf("stageshow-boxoffice-zone");
	className = className.slice(posn+24);
	var zoneTemp = className.split(" ");
	var zoneNo = zoneTemp[0];
	return zoneNo;
}

function isZoneValid(obj)
{
	var zoneID = getZoneNo(obj);
	if ((zoneID > 0) && (zones[zoneID] > 0))
	{
		return true;		
	}
		
	return false;
}

function initSeatFromTrolley(obj)
{
	var html = obj.outerHTML;
	var posn = html.indexOf("clickSeat");
	var params = html.slice(posn).split(/[\(\),]+/);
	var zoneNo = params[2];
	clickSeat(obj, zoneNo);
}

function clickSeat(obj, zoneID)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem;
	
	seatIdParts = obj.id.split("-");
	seatId = seatIdParts[seatIdParts.length-1];
	
	if (!isZoneValid(obj))
	{
		return;
	}
				
	seatStatus = seatAvailability(seatId);
	if (seatStatus == 'booked')
	{
		return;
	}
	
	hiddenSeatsElem = document.getElementById("stageshow-boxoffice-layout-seats");
	hiddenZonesElem = document.getElementById("stageshow-boxoffice-layout-zones");
	
	/* Add a space either side of the name */
	/* This prevents a match with part of any longer Ids */
	seatId = " " + seatId + " ";
	zoneID = " " + zoneID + " ";
	
	var className = obj.className;
	var classPosn = className.search('stageshow-boxoffice-seat-available');
	
	/* Remove existing class specifier */
	className  = className.replace('stageshow-boxoffice-seat-available ', '');
	className  = className.replace('stageshow-boxoffice-seat-requested ', '');
	
	if (classPosn >= 0)
	{
		className = 'stageshow-boxoffice-seat-requested ' + className;		
		hiddenSeatsElem.value = hiddenSeatsElem.value + seatId;
		hiddenZonesElem.value = hiddenZonesElem.value + zoneID;
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
		hiddenSeatsElem.value = hiddenSeatsElem.value.replace(seatId, "");
		hiddenZonesElem.value = hiddenZonesElem.value.replace(zoneID, "");
	}
	obj.className = className;
	
}

function onSeatsLoad()
{
	/* Clear hidden pass back values - Required if page is refreshed */
	document.getElementById("stageshow-boxoffice-layout-seats").value = '';
	document.getElementById("stageshow-boxoffice-layout-zones").value = '';
	
	/* Note: Uses maxRows and maxCols which must be defined in template */
	var row, col;
	for (row=1; row<=maxRows; row++)
	{
		for (col=1; col<=maxCols; col++)
		{
			var seatId = row + '_' + col;
			var seatObj = document.getElementById('stageshow-boxoffice-layout-seat-' + seatId);
			
			if (seatObj != null)
			{
				var className  = seatObj.className.replace('stageshow-boxoffice-seat-unknown', '');
				var zoneValid = isZoneValid(seatObj);
				
				if (zoneValid)
				{					
					switch (seatAvailability(seatId))
					{
						case '': 
							seatObj.className = 'stageshow-boxoffice-seat-available ' + className;
							break;
							
						case 'selected': 
							seatObj.className = 'stageshow-boxoffice-seat-available ' + className;
							initSeatFromTrolley(seatObj);
							break;
							
						default: 
							seatObj.className = 'stageshow-boxoffice-seat-reserved ' + className;
							break;
							
					}
				}
				else
				{
					seatObj.className = 'stageshow-boxoffice-seat-disabled ' + className;					
				}
			}
		}
	}
}

