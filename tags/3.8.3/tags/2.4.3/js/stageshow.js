
function seatAvailable(seatId)
{
	var bookedIndex = bookedSeats.indexOf(seatId);
	return (bookedIndex < 0);
}

function clickSeat(obj, zoneID, zoneDef)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem, hiddenDefsElem;
	
	seatIdParts = obj.id.split("-");
	seatId = seatIdParts[seatIdParts.length-1];
	
	if (!seatAvailable(seatId))
	{
		return;
	}
	
	hiddenSeatsElem = document.getElementById("stageshow-boxoffice-layout-seats");
	hiddenZonesElem = document.getElementById("stageshow-boxoffice-layout-zones");
	hiddenDefsElem = document.getElementById("stageshow-boxoffice-layout-zonedefs");
	
	/* Add a space either side of the name */
	/* This prevents a match with part of any longer Ids */
	seatId = " " + seatId + " ";
	zoneID = " " + zoneID + " ";
	zoneDef = " " + zoneDef + " ";
	
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
		hiddenDefsElem.value = hiddenDefsElem.value + zoneDef
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
		hiddenSeatsElem.value = hiddenSeatsElem.value.replace(seatId, "");
		hiddenZonesElem.value = hiddenZonesElem.value.replace(zoneID, "");
		hiddenDefsElem.value = hiddenDefsElem.value.replace(zoneDef, "");
	}
	obj.className = className;
	
}

function onSeatsLoad()
{
	/* Clear hidden pass back values - Required if page is refreshed */
	document.getElementById("stageshow-boxoffice-layout-seats").value = '';
	document.getElementById("stageshow-boxoffice-layout-zones").value = '';
	document.getElementById("stageshow-boxoffice-layout-zonedefs").value = '';
	
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
				
				if (seatAvailable(seatId))
				{
					seatObj.className = 'stageshow-boxoffice-seat-available ' + className;
				}
				else
				{
					seatObj.className = 'stageshow-boxoffice-seat-reserved ' + className;
				}
			}
		}
	}
}

