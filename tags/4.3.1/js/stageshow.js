
const SeatAvailableClass = "stageshow-boxoffice-seat-available";
const SeatRequestedClass = "stageshow-boxoffice-seat-requested";
const SeatReservedClass = "stageshow-boxoffice-seat-reserved";
const SeatDisabledClass = "stageshow-boxoffice-seat-disabled";

const SeatLeftEndClass = "stageshow-boxoffice-leftend";
const SeatRightEndClass = "stageshow-boxoffice-rightend";

var hasEndLimitTags;
var hasDebugOutput;

function stageshow_SeatAvailability(seatId)
{
	var bookedIndex = bookedSeats.indexOf(seatId);
	if (bookedIndex >= 0) 
	{
		return 'booked';
	}
	
	var selectedIndex = selectedSeats.indexOf(seatId);
	if (selectedIndex >= 0) 
	{
		return 'selected';
	}
	
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
	var chkZoneID = stageshow_GetZoneNo(obj);
	if ((chkZoneID > 0) && (zones[chkZoneID] >= 0))
	{
		return parseInt(chkZoneID);		
	}
		
	return 0;
}

function stageshow_InitSeatFromTrolley(obj)
{
	stageshow_ClickSeat(obj);
}

function stageshow_IsSeatState(obj, srchState)
{
	thisSeatClass = obj.className;
	return (thisSeatClass.indexOf(srchState) > -1);
}

function stageshow_GetSeatState(obj)
{
	thisSeatClass = obj.className;
	if (thisSeatClass.indexOf(SeatRequestedClass) > -1)
	{
		return SeatRequestedClass;
	}
	
	if (thisSeatClass.indexOf(SeatReservedClass) > -1)
	{
		return SeatReservedClass;
	}
	
	if (thisSeatClass.indexOf(SeatDisabledClass) > -1)
	{
		return SeatDisabledClass;
	}
	
	return "stageshow-boxoffice-seat-available";
}

function stageshow_CheckClickSeat(obj)
{
	if (!hasEndLimitTags)
	{
		return true;
	}
	
	if (hasDebugOutput)
	{
		RequestedRightCountElem = document.getElementById("RequestedRightCount");
		AvailableRightCountElem = document.getElementById("AvailableRightCount");
		RequestedLeftCountElem = document.getElementById("RequestedLeftCount");
		AvailableLeftCountElem = document.getElementById("AvailableLeftCount");

		RequestedRightCountElem.value = "";
		AvailableRightCountElem.value = "";
		RequestedLeftCountElem.value = "";
		AvailableLeftCountElem.value = "";		
	}
	
	seatPosnParts = obj.id.split("_");
	clickedColNo = parseInt(seatPosnParts[1]);
	
	/* Scan for a "gap" to the left of selected seat */
	var scanCount = [];
	
	nextSeatObj = obj;

	seatState = stageshow_GetSeatState(nextSeatObj);
	unselectRequest = (seatState == SeatRequestedClass);
	
	if (unselectRequest)
	{
		if ( (stageshow_IsSeatState(obj, SeatLeftEndClass))
		  || (stageshow_IsSeatState(obj, SeatRightEndClass)) )
		{
			return true;
		}
		
		for (seatOffset=-1; seatOffset<=1; seatOffset+=2)
		{
			seatColNo = clickedColNo + seatOffset;
			seatObjId = seatPosnParts[0] + '_' + seatColNo;
			nextSeatObj = document.getElementById(seatObjId);
			seatState = stageshow_GetSeatState(nextSeatObj);
			if (seatState != SeatRequestedClass)
			{
				return true;
			}
		}
		
		return false;
	}
	
	scanCountIndex = 0;
	for (seatOffset=1; seatOffset<=2; seatOffset++)
	{
		if (seatOffset == 1)
		{
			/* Repeat scan to the right */
			endClass = SeatRightEndClass;
			nextSeatOffset = 1;
		}
		else
		{
			/* Repeat scan to the left */
			endClass = SeatLeftEndClass;
			nextSeatOffset = -1;
		}
		
		if (stageshow_IsSeatState(obj, endClass))
		{
			scanCount[scanCountIndex+1] = 0;
			scanCount[scanCountIndex+2] = 1000;
			scanCountIndex += 2;
			continue;
		}

		scanFor = SeatRequestedClass;
		seatColNo = clickedColNo + nextSeatOffset;
			
		for (stateScan=1; stateScan<=2; stateScan++)
		{
			scanCountIndex++;
			scanCount[scanCountIndex] = 0;
			while (true)
			{
				seatObjId = seatPosnParts[0] + '_' + seatColNo;
				nextSeatObj = document.getElementById(seatObjId);
				seatState = stageshow_GetSeatState(nextSeatObj);
				if (seatState != scanFor)
				{
					if ( (seatState != SeatAvailableClass) && (stateScan == 1) )
					{
						/*  The Seat next to the Last Seat Requested is un-available ... 
							OK to add seat at the other end 
						*/
						return true;
					}
					break;
				}
				
				scanCount[scanCountIndex] += 1;
				
				if (stageshow_IsSeatState(nextSeatObj, endClass))
				{
					if (stateScan == 1)
					{
						/*  The Last Seat Requested is at the end of a row ... 
							OK to add seat at the other end 
						*/
						return true;
					}
					else
					{
						scanCount[scanCountIndex] += 1000;						
					}
					break;
				}
				
				seatColNo = seatColNo + nextSeatOffset;
			}
			 
			scanFor = SeatAvailableClass;
		}
			
	}	
	
	if (hasDebugOutput)
	{
		RequestedRightCountElem.value = scanCount[1];
		AvailableRightCountElem.value = scanCount[2];
		RequestedLeftCountElem.value = scanCount[3];
		AvailableLeftCountElem.value = scanCount[4];		
	}
	
	if ( ((scanCount[2] > 0) && (scanCount[2] < minSeatSpace))
	  || ((scanCount[4] > 0) && (scanCount[4] < minSeatSpace)) )
	{
		return false;
	}
	
	return true;
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

	if (!stageshow_CheckClickSeat(obj))
	{
		return;
	}

	/* Add a space either side of the name */
	/* This prevents a match with part of any longer Ids */
	seatIdMark = " " + seatId + " ";
	zoneIDMark = " " + zoneID + " ";
	
	var className = obj.className;
	var classPosn = className.search(SeatAvailableClass);
	
	hiddenSeatsElem = document.getElementById("stageshow-boxoffice-layout-seats");
	hiddenZonesElem = document.getElementById("stageshow-boxoffice-layout-zones");
	
	/* Remove existing class specifier */
	className  = className.replace(SeatAvailableClass + ' ', '');
	className  = className.replace(SeatRequestedClass + ' ', '');
	
	if (classPosn >= 0)
	{
		if (zones[zoneID] <= 0)
			return;
			
		className = SeatRequestedClass + ' ' + className;		
		hiddenSeatsElem.value = hiddenSeatsElem.value + seatIdMark;
		hiddenZonesElem.value = hiddenZonesElem.value + zoneIDMark;
		zones[zoneID] = zones[zoneID] - 1;
	}
	else
	{
		className = SeatAvailableClass + ' ' + className;
		hiddenSeatsElem.value = hiddenSeatsElem.value.replace(seatIdMark, "");
		hiddenZonesElem.value = hiddenZonesElem.value.replace(zoneIDMark, "");
		zones[zoneID] = zones[zoneID] + 1;
	}
	obj.className = className;
	
}

function stageshow_OnSeatsLoad()
{
	/* Check if Block End Markers are defined */
	elemsList = document.getElementsByClassName(SeatLeftEndClass);
	hasEndLimitTags = (elemsList.length > 0);	
	hasDebugOutput = (document.getElementById("RequestedRightCount") != null);
	
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
							seatObj.className = SeatAvailableClass + ' ' + className;
							break;
							
						case 'selected': 
							seatObj.className = SeatAvailableClass + ' ' + className;
							stageshow_ClickSeat(seatObj);
							break;
							
						default: 
							seatObj.className = SeatReservedClass + ' ' + className;
							break;
							
					}
				}
				else
				{
					seatObj.className = SeatDisabledClass + ' ' + className;					
				}
			}
		}
	}
}

function stageshow_OnClickAdd(obj)
{
	if (typeof stageshow_OnClickAdd == 'function') 
	{ 
  		return stageshow_OnClickAdd(obj); 
	}	
	return true;
}

function stageshow_OnClickSelectseats(obj)
{
	if (typeof stageshowCustom_OnClickSelectseats == 'function') 
	{ 
  		return stageshowCustom_OnClickSelectseats(obj); 
	}	
	return true;
}

function stageshow_OnClickSeatsselected(obj)
{
	if (typeof stageshowCustom_OnClickSeatsselected == 'function') 
	{ 
  		return stageshowCustom_OnClickSeatsselected(obj); 
	}	
	return true;
}

function stageshow_OnClickReserve(obj)
{
	if (typeof stageshowCustom_OnClickReserve == 'function') 
	{ 
  		return stageshowCustom_OnClickReserve(obj); 
	}	
	return true;
}

function stageshow_OnClickCheckout(obj)
{
	if (typeof stageshowCustom_OnClickCheckout == 'function') 
	{ 
  		return stageshowCustom_OnClickCheckout(obj); 
	}
	return true;
}

function stageshow_OnClickSubmitDetails(obj)
{
	if (typeof stageshowCustom_OnClickSubmitDetails == 'function') 
	{ 
  		return stageshowCustom_OnClickSubmitDetails(obj); 
	}
	
	return stageshowStandard_OnClickSubmitDetails(obj); 
}

function stageshow_OnClickRemove(obj)
{
	if (typeof stageshowCustom_OnClickRemove == 'function') 
	{ 
  		return stageshowCustom_OnClickRemove(obj); 
	}
	return true;
}
