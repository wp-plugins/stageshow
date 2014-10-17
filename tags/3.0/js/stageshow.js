
function stageshow_SeatAvailability(seatId)
{
	var bookedIndex = bookedSeats.indexOf(seatId);
	if (bookedIndex >= 0) return 'booked';
	
	var selectedIndex = selectedSeats.indexOf(seatId);
	if (selectedIndex >= 0) return 'selected';
	
	return '';
}

function stageshow_GetZoneNo(obj)
{
	var className = obj.className;
	var posn = className.indexOf("stageshow-boxoffice-zone");
	className = className.slice(posn+24);
	var zoneTemp = className.split(" ");
	var zoneNo = zoneTemp[0];
	return zoneNo;
}

function stageshow_IsZoneValid(obj)
{
	var zoneID = stageshow_GetZoneNo(obj);
	if ((zoneID > 0) && (zones[zoneID] >= 0))
	{
		return zoneID;		
	}
		
	return 0;
}

function stageshow_InitSeatFromTrolley(obj)
{
	stageshow_ClickSeat(obj);
}

function stageshow_ClickSeat(obj)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem;
	
	seatIdParts = obj.id.split("-");
	seatId = seatIdParts[seatIdParts.length-1];
	
	zoneID = stageshow_IsZoneValid(obj);
	if (zoneID == 0)
	{
		return;
	}
				
	seatStatus = stageshow_SeatAvailability(seatId);
	if (seatStatus == 'booked')
	{
		return;
	}
	
	hiddenSeatsElem = document.getElementById("stageshow-boxoffice-layout-seats");
	hiddenZonesElem = document.getElementById("stageshow-boxoffice-layout-zones");
	
	/* Add a space either side of the name */
	/* This prevents a match with part of any longer Ids */
	seatIdMark = " " + seatId + " ";
	zoneIDMark = " " + zoneID + " ";
	
	var className = obj.className;
	var classPosn = className.search('stageshow-boxoffice-seat-available');
	
	/* Remove existing class specifier */
	className  = className.replace('stageshow-boxoffice-seat-available ', '');
	className  = className.replace('stageshow-boxoffice-seat-requested ', '');
	
	if (classPosn >= 0)
	{
		if (zones[zoneID] <= 0)
			return;
			
		className = 'stageshow-boxoffice-seat-requested ' + className;		
		hiddenSeatsElem.value = hiddenSeatsElem.value + seatIdMark;
		hiddenZonesElem.value = hiddenZonesElem.value + zoneIDMark;
		zones[zoneID] = zones[zoneID] - 1;
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
		hiddenSeatsElem.value = hiddenSeatsElem.value.replace(seatIdMark, "");
		hiddenZonesElem.value = hiddenZonesElem.value.replace(zoneIDMark, "");
		zones[zoneID] = zones[zoneID] + 1;
	}
	obj.className = className;
	
}

function stageshow_OnSeatsLoad()
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
				var zoneID = stageshow_IsZoneValid(seatObj);
				
				if (zoneID > 0)
				{					
					switch (stageshow_SeatAvailability(seatId))
					{
						case '': 
							seatObj.className = 'stageshow-boxoffice-seat-available ' + className;
							break;
							
						case 'selected': 
							seatObj.className = 'stageshow-boxoffice-seat-available ' + className;
							stageshow_ClickSeat(seatObj);
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
