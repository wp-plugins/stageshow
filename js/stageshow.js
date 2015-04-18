
const SeatAvailableClassText = "stageshow-boxoffice-seat-available";
const SeatRequestedClassText = "stageshow-boxoffice-seat-requested";
const SeatReservedClassText = "stageshow-boxoffice-seat-reserved";
const SeatDisabledClassText = "stageshow-boxoffice-seat-disabled";

const SeatStateAvailable = 0;
const SeatStateRequested = 1;
const SeatStateReserved = 2;
const SeatStateDisabled = 3;

const SeatLeftEndClass = "stageshow-boxoffice-leftend";
const SeatRightEndClass = "stageshow-boxoffice-rightend";

var hasEndLimitTags;
var hasDebugOutput;

var zonesReq = new Array();

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
	if (thisSeatClass.indexOf(SeatRequestedClassText) > -1)
	{
		return SeatStateRequested;
	}
	
	if (thisSeatClass.indexOf(SeatReservedClassText) > -1)
	{
		return SeatStateReserved;
	}
	
	if (thisSeatClass.indexOf(SeatDisabledClassText) > -1)
	{
		return SeatStateDisabled;
	}
	
	return SeatStateAvailable;
}

function stageshow_CheckClickSeat(obj)
{
	if (!hasEndLimitTags)
		return true;
	
	if (typeof stageshowCustom_CheckClickSeat == 'function') 
	{ 
  		return stageshowCustom_CheckClickSeat(obj); 
	}
	
	if (minSeatSpace <= 0)
		return true;
		
	if (hasDebugOutput)
	{
		DebugSeatingGapBlockingElem = document.getElementById("DebugSeatingGapBlocking");
		DebugSeatingGapBlockingElem.value = "";
	}
	
	seatPosnParts = obj.id.split("_");
	clickedColNo = parseInt(seatPosnParts[1]);
	
	/* Scan for "gaps" in this block of unallocated seats */
	var seatsStates = [];
	var limits = [];
	var rowEnd = [];
	
	seatState = stageshow_GetSeatState(obj);
	switch (seatState)
	{
		case SeatStateAvailable:
			seatState = SeatStateRequested;
			break;	
			
		case SeatStateRequested:
			seatState = SeatStateAvailable;
			break;	
			
		default:
			return false;
	}
	seatsStates[clickedColNo] = seatState;
	
	for (scanLoopIndex=0; scanLoopIndex<=1; scanLoopIndex++)
	{
		seatNo = clickedColNo;
		if (scanLoopIndex == 0)
		{
			scanOffset = -1;
			scanEnd = SeatLeftEndClass;
		}
		else
		{
			scanOffset = 1;
			scanEnd = SeatRightEndClass;
		}
		limits[scanLoopIndex] = seatNo;
		rowEnd[scanLoopIndex] = true;
		if (stageshow_IsSeatState(obj, scanEnd))
		{
			continue;
		}
		
		for (; seatNo>=1; )
		{
			seatNo += scanOffset;
			seatObjId = seatPosnParts[0] + '_' + seatNo;
			nextSeatObj = document.getElementById(seatObjId);
			seatState = stageshow_GetSeatState(nextSeatObj);
						
			if (seatState >= SeatStateReserved)
			{
				rowEnd[scanLoopIndex] = false;
				break;
			}
			
			seatsStates[seatNo] = seatState;
			limits[scanLoopIndex] = seatNo;		
			if (stageshow_IsSeatState(nextSeatObj, scanEnd))
			{
				break;
			}
			
		}
	}
	
	inBlock = false;
	blocksCount = 0;
	availSeatsCount = 0;	
	foundSmallGap = false;
	
	for (seatNo=limits[0]; seatNo<=limits[1]; seatNo++)
	{
		seatState = seatsStates[seatNo];
		switch (seatState)
		{
			case SeatStateAvailable:
				inBlock = false;
				availSeatsCount++;
				break;	
				
			case SeatStateRequested:
				if (!inBlock) blocksCount++;
				inBlock = true;
				if ((availSeatsCount > 0) && (availSeatsCount < minSeatSpace))
					foundSmallGap = true;
				availSeatsCount = 0;
				break;	
		}
	}
	if ((availSeatsCount > 0) && (availSeatsCount < minSeatSpace))
		foundSmallGap = true;

	if (blocksCount > 1) 
		return false;
	
	if (rowEnd[0] && (seatsStates[limits[0]] == SeatStateRequested)) 
		return true;
	if (rowEnd[1] && (seatsStates[limits[1]] == SeatStateRequested)) 
		return true;
	
	if (foundSmallGap) 
		return false;
	
	return true;
}

function stageshow_UpdateZonesCount(zoneID, zoneCountRequested, zoneCountCurrent)
{
	var elemIdRoot = "stageshow-boxoffice-zoneSeatsBlock";
	var blockElem = document.getElementById(elemIdRoot);
	if (blockElem == null)
		return;
		
	var zoneElem = document.getElementById(elemIdRoot+zoneID);
	if (zoneElem == null)
		return;
		
	requestedElem = document.getElementById(elemIdRoot+"-requested"+zoneID);
	requestedElem.innerHTML = zoneCountRequested;
	
	zoneCountSelected = zoneCountRequested - zoneCountCurrent;
	selectedElem = document.getElementById(elemIdRoot+"-selected"+zoneID);
	selectedElem.innerHTML = zoneCountSelected;
	
	zoneElem.style.display = '';
	blockElem.style.display = '';
}

function stageshow_ClickSeat(obj)
{
	stageshow_ToggleSeat(obj, true);
}

function stageshow_ToggleSeat(obj, isClick)
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
		alert(CantReserveSeatMessage);
		return;
	}

	/* Add a space either side of the name */
	/* This prevents a match with part of any longer Ids */
	seatIdMark = " " + seatId + " ";
	zoneIDMark = " " + zoneID + " ";
	
	var className = obj.className;
	var classPosn = className.search(SeatAvailableClassText);
	
	hiddenSeatsElem = document.getElementById("stageshow-boxoffice-layout-seats");
	hiddenZonesElem = document.getElementById("stageshow-boxoffice-layout-zones");
	
	/* Remove existing class specifier */
	className  = className.replace(SeatAvailableClassText + ' ', '');
	className  = className.replace(SeatRequestedClassText + ' ', '');
	
	if (classPosn >= 0)
	{
		if (zones[zoneID] <= 0)
			return;
			
		className = SeatRequestedClassText + ' ' + className;		
		hiddenSeatsElem.value = hiddenSeatsElem.value + seatIdMark;
		hiddenZonesElem.value = hiddenZonesElem.value + zoneIDMark;
		zones[zoneID] = zones[zoneID] - 1;
	}
	else
	{
		className = SeatAvailableClassText + ' ' + className;
		hiddenSeatsElem.value = hiddenSeatsElem.value.replace(seatIdMark, "");
		hiddenZonesElem.value = hiddenZonesElem.value.replace(zoneIDMark, "");
		zones[zoneID] = zones[zoneID] + 1;
	}
	if (isClick)
	{
		stageshow_UpdateZonesCount(zoneID, zonesReq[zoneID], zones[zoneID]);
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
	
	for (var zoneID in zones) 
	{
		zonesReq[zoneID] = zones[zoneID];
	}
	
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
							seatObj.className = SeatAvailableClassText + ' ' + className;
							break;
							
						case 'selected': 
							seatObj.className = SeatAvailableClassText + ' ' + className;
							stageshow_ToggleSeat(seatObj, false);
							break;
							
						default: 
							seatObj.className = SeatReservedClassText + ' ' + className;
							break;
							
					}
				}
				else
				{
					seatObj.className = SeatDisabledClassText + ' ' + className;					
				}
			}
		}
	}
	
	for (var zoneID in zones) 
	{
		stageshow_UpdateZonesCount(zoneID, zonesReq[zoneID], zones[zoneID]);		
	}
}

function stageshow_OnClickAdd(obj)
{
	if (typeof stageshowCustom_OnClickAdd == 'function') 
	{ 
  		return stageshowCustom_OnClickAdd(obj); 
	}	
	
	return stageshowJQuery_OnClickTrolleyButton(obj); 
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

function stageshow_OnClickCheckoutdetails(obj)
{
	if (typeof stageshowCustom_OnClickCheckoutdetails == 'function') 
	{ 
  		return stageshowCustom_OnClickCheckoutdetails(obj); 
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
	
	return stageshowJQuery_OnClickTrolleyButton(obj); 
}

function stageshow_enable_interface(classId, state)
{
	var classSpec = "."+classId;
	var buttonElemsList = jQuery(classSpec);
	jQuery.each(buttonElemsList,
		function(i, listObj) 
		{
			var uiElemSpec = "#" + listObj.name;
			var uiElem = jQuery(uiElemSpec);
			
			if (state)
			{
				uiElem.prop("disabled", false);			
				uiElem.css("cursor", "default");				
			}
			else
			{
				uiElem.prop("disabled", true);			
				uiElem.css("cursor", "progress");				
			}
				
	    	return true;
		}
	);		
	
}
