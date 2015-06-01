

var SeatAvailableClassText = 'stageshow-boxoffice-seat-available';
var SeatRequestedClassText = 'stageshow-boxoffice-seat-requested';
var SeatReservedClassText = 'stageshow-boxoffice-seat-reserved';
var SeatDisabledClassText = 'stageshow-boxoffice-seat-disabled';

var SeatStateInvalid = -1;
var SeatStateAvailable = 0;
var SeatStateRequested = 1;
var SeatStateReserved = 2;
var SeatStateDisabled = 3;

var SeatLeftEndClass = 'stageshow-boxoffice-leftend';
var SeatRightEndClass = 'stageshow-boxoffice-rightend';

var hasEndLimitTags;

var zonesReq = new Array();

function stageshow_SeatAvailability(seatId)
{
	var bookedIndex = jQuery.inArray(seatId, bookedSeats);
	if (bookedIndex >= 0) 
	{
		return 'booked';
	}
	
	var selectedIndex = jQuery.inArray(seatId, selectedSeats);
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
		
	seatPosnParts = obj.id.split("_");
	clickedColNo = parseInt(seatPosnParts[1]);
	
	var seatsStates = [];
	var limits = [];
	
	/* Get the new state of the seat just clicked */
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
	
	/* 
		Scan this row both ways - Stop at one of the following conditions:
		When the seat is at the end of the row
		When the seat is next to an aisle
		When the next seat is a Reserved Seat
	*/
	availSeatsCount = 0;	
	for (loopCount=0; loopCount<=1; loopCount++)
	{
		seatNo = clickedColNo;
		if (loopCount == 0) 
		{
			scanOffset = -1;
			scanEnd = SeatLeftEndClass;
		}
		else 
		{
			scanOffset = 1;
			scanEnd = SeatRightEndClass;
		}
		
		limits[loopCount] = seatNo;
		if (stageshow_IsSeatState(obj, scanEnd))
		{
			continue;
		}
		
		for ( ; (seatNo > 0) && (seatNo <= maxCols); )
		{
			seatNo += scanOffset;
			seatObjId = seatPosnParts[0] + '_' + seatNo;
			nextSeatObj = document.getElementById(seatObjId);
			seatState = stageshow_GetSeatState(nextSeatObj);
						
			if (seatState >= SeatStateReserved)
			{
				/* Stop scanning without updating seatsStates */
				break;
			}
			
			seatsStates[seatNo] = seatState;
			limits[loopCount] = seatNo;		
			if (stageshow_IsSeatState(nextSeatObj, scanEnd))
			{
				/* Update seatsStates - Then Stop scanning */
				break;
			}			
		}
	}
	
	/* Add a right hand terminator for the scan */
	seatNo = limits[1] + 1;
	seatsStates[seatNo] = SeatStateInvalid;
	limits[1] = seatNo;
	
	/*  Scan Seats Block for an available blocks smaller than the limit */
	lastSeatState = -1;	
	availableBlocksCount=0;
	requestedBlocksCount=0;
	conseqAvailableSeats = 0;
	smallGapsCount = 0;
	for (seatNo=limits[0]; seatNo<=limits[1]; seatNo++)
	{
		seatState = seatsStates[seatNo];
		if (seatState == SeatStateAvailable)
		{
			if (lastSeatState != seatState) availableBlocksCount++;
			conseqAvailableSeats++;	
		}
		else
		{			
			if (lastSeatState != seatState)
			{
				if (seatState == SeatStateRequested)
				{
					requestedBlocksCount++;
				}
				if ((conseqAvailableSeats > 0) && (conseqAvailableSeats < minSeatSpace))
				{
					smallGapsCount++;
				}
			}
			conseqAvailableSeats = 0;
		}
		lastSeatState = seatState;
	}
	
	if ((requestedBlocksCount > 1) || (availableBlocksCount > 1))
	{
		if (smallGapsCount > 0)
		{
			return false;
		}
	}
	
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

	if (isClick && !stageshow_CheckClickSeat(obj))
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
	elemsList = jQuery("."+SeatLeftEndClass);
	hasEndLimitTags = (elemsList.length > 0);	
	
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

function stageshowJQuery_OnClickTrolleyButton(obj, inst)
{
	var scIndex = inst;
	
	/* Set Cursor to Busy and Disable All UI Buttons */
	StageShowLib_SetBusy(true, "stageshow-trolley-ui");
			
	var postvars = {
		jquery: "true"
	};
	postvars.count = inst;
	postvars.timeout = 30000;
	postvars.cache = false;
	postvars.path = window.location.pathname + window.location.search;
	
	postvars = stageshowJQuery_PostVars(postvars);

	var buttonId = obj.id;	
	postvars[buttonId] = "submit";
	var qty = 0;
	var nameParts = buttonId.split("_");
	if (nameParts[0] == "AddTicketSale")
	{
		var qtyId = "quantity_" + nameParts[1];
		var qty = document.getElementById(qtyId).value;
		postvars[qtyId] = qty;
	}
	
	ourAtts = attStrings[scIndex-1];
	ourAtts = ourAtts.split(",");
	for (var attId=0; attId<ourAtts.length; attId++) 
	{
		var thisAtt = ourAtts[attId].split("=");
		var key = thisAtt[0];
		var value = thisAtt[1];
		
		postvars[key] = value;
	}

	/* Get New HTML from Server */
    jQuery.post(jQueryURL, postvars,
	    function(data, status)
	    {
			trolleyTargetElem = jQuery("#stageshow-trolley-trolley-std");
			
	    	if ((status != 'success') || (data.length == 0))
	    	{
				StageShowLib_SetBusy(false, "stageshow-trolley-ui");
				
	    		/* Fall back to HTML Post Method */
				return true;
			}

			/* Apply translations to any message */
			for (var index=0; index<tl8_srch.length; index++)
			{
				var srchFor = tl8_srch[index];
				var repWith = tl8_repl[index];
				data = StageShowLib_replaceAll(srchFor, repWith, data);
			}

			targetElemID = "#stageshow-trolley-container" + inst;
			divElem = jQuery(targetElemID);
			divElem.html(data);
	
			/* Copy New Trolley HTML */
			trolleyUpdateElem = jQuery("#stageshow-trolley-trolley-jquery");
			
			/* Get updated trolley (which is not visible) */
			trolleyHTML = trolleyUpdateElem[0].innerHTML;

			/* Copy New Trolley HTML */
			trolleyTargetElem.html(trolleyHTML);
			
			/* Now delete the downloaded HTML */
			trolleyUpdateElem.remove();
			
			/* Set Cursor to Normal and Enable All UI Buttons */
			StageShowLib_SetBusy(false, "stageshow-trolley-ui");
	    }
    );
    
    return false;
}
				
function stageshow_OnClickAdd(obj, inst)
{
	if (typeof stageshowCustom_OnClickAdd == 'function') 
	{ 
  		return stageshowCustom_OnClickAdd(obj, inst); 
	}	
	
	return stageshowJQuery_OnClickTrolleyButton(obj, inst); 
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

function stageshow_OnClickRemove(obj, inst)
{
	if (typeof stageshowCustom_OnClickRemove == 'function') 
	{ 
  		return stageshowCustom_OnClickRemove(obj, inst); 
	}
	
	return stageshowJQuery_OnClickTrolleyButton(obj, inst); 
}
