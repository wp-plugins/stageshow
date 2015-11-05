
/* Seat Selector class definitions - Redefined if STAGESHOW_CLASS_BOXOFFICE_***** values are defined */
var SeatUnknownClassText = 'stageshow-boxoffice-seat-unknown';
var SeatAvailableClassText = 'stageshow-boxoffice-seat-available';
var SeatRequestedClassText = 'stageshow-boxoffice-seat-requested';
var SeatReservedClassText = 'stageshow-boxoffice-seat-reserved';	// Used for Both Booked & Reserved Seats
var SeatAllocatedClassText = 'stageshow-boxoffice-seat-allocated';
var SeatBookedClassText = 'stageshow-boxoffice-seat-booked';
var SeatDisabledClassText = 'stageshow-boxoffice-seat-disabled';

var SeatLayoutClassText = 'stageshow-boxoffice-layout-seat-';

/* Seat Selector id definitions - Never Redefined */
var SeatCountBlockIdRoot = "stageshow-boxoffice-zoneSeatsBlock";
var SeatLayoutBlockId = "#stageshow-boxoffice-seats";
var SeatsLoadingBlockId = "#stageshow-boxoffice-loading";

var SeatStateInvalid = -1;
var SeatStateAvailable = 0;
var SeatStateRequested = 1;
var SeatStateReserved = 2;
var SeatStateAllocated = 3;
var SeatStateBooked = 4;
var SeatStateDisabled = 5;

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
	
	var reservedIndex = jQuery.inArray(seatId, reservedSeats);
	if (reservedIndex >= 0) 
	{
		return 'reserved';
	}
	
	var allocatedIndex = jQuery.inArray(seatId, allocatedSeats);
	if (allocatedIndex >= 0) 
	{
		return 'allocated';
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
	
	if (thisSeatClass.indexOf(SeatAllocatedClassText) > -1)
	{
		return SeatStateAllocated;
	}
	
	if (thisSeatClass.indexOf(SeatReservedClassText) > -1)
	{
		return SeatStateReserved;
	}
	
	if (thisSeatClass.indexOf(SeatBookedClassText) > -1)
	{
		return SeatStateBooked;
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
	var blockElem = document.getElementById(SeatCountBlockIdRoot);
	if (blockElem == null)
		return;
		
	var zoneElem = document.getElementById(SeatCountBlockIdRoot+zoneID);
	if (zoneElem == null)
		return;
		
	requestedElem = document.getElementById(SeatCountBlockIdRoot+"-requested"+zoneID);
	requestedElem.innerHTML = zoneCountRequested;
	
	zoneCountSelected = zoneCountRequested - zoneCountCurrent;
	selectedElem = document.getElementById(SeatCountBlockIdRoot+"-selected"+zoneID);
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
	
	hiddenSeatsElem = document.getElementById("stageshow-seatselected-seats");
	hiddenZonesElem = document.getElementById("stageshow-seatselected-zones");
	
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

function  stageshow_OnClickSeatsavailable(obj, inst)
{
	var buttonIdParts = obj.id.split("_");
	var perfId = parseInt(buttonIdParts[1]);
	
	var url = window.location.pathname;
	if (url.indexOf('?') > -1)
	{
		url = url.replace('?', '?seatsavailable=' + perfId + '&');
	}
	else if (url.indexOf('#') > -1)
	{
		url = url.replace('#', '?seatsavailable=' + perfId + '#');
	}
	else
	{
		url = url + '?seatsavailable=' + perfId;
	}

	window.open(url, '_blank');
	return false;
}

function  stageshow_OnClickClosewindow(obj, inst)
{
	window.close();
}

function stageshow_OnSeatsLoad()
{
	/* Check if Block End Markers are defined */
	elemsList = jQuery("."+SeatLeftEndClass);
	hasEndLimitTags = (elemsList.length > 0);	
	
	/* Clear hidden pass back values - Required if page is refreshed */
	document.getElementById("stageshow-seatselected-seats").value = '';
	document.getElementById("stageshow-seatselected-zones").value = '';
	
	seatsRequestedCount = 0;
	for (var zoneID in zones) 
	{
		zonesReq[zoneID] = zones[zoneID];
		seatsRequestedCount += zones[zoneID];
	}

	/* Note: Uses maxRows and maxCols which must be defined in template */
	var row, col;
	for (row=1; row<=maxRows; row++)
	{
		for (col=1; col<=maxCols; col++)
		{
			var seatId = row + '_' + col;
			var seatObj = document.getElementById(SeatLayoutClassText + seatId);
			
			if (seatObj != null)
			{
				var className  = seatObj.className.replace(SeatUnknownClassText, '');
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
							
						case 'allocated': 
							seatObj.className = SeatAllocatedClassText + ' ' + className;
							break;
							
						case 'booked': 
							seatObj.className = SeatBookedClassText + ' ' + className;
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
	
	if (seatsRequestedCount == 0)
	{
		jQuery('#' + SeatCountBlockIdRoot).hide();
	}
		
	jQuery(SeatsLoadingBlockId).hide();
	jQuery(SeatLayoutBlockId).show();
	jQuery('#trolley').css("visibility", "visible"); 
	jQuery('#stageshow-trolley-trolley-std').show();

}

function stageshow_OnClickAdd(obj, inst)
{
	if (typeof stageshowCustom_OnClickAdd == 'function') 
	{ 
  		return stageshowCustom_OnClickAdd(obj, inst); 
	}	

	rtnVal = StageShowLib_JQuery_OnClickTrolleyButton(obj, inst, "stageshow_JQuery_Callback"); 
	
	return rtnVal;
}

function stageshow_JQuery_Callback(data, inst, buttonId, qty)
{
	StageShowLib_JQuery_Callback(data, inst, buttonId, qty);
	
	stageshow_OnClickSelectshow(lastSelectShowObj);
	stageshow_OnClickSelectperf(lastSelectPerfObj);
	
}

var stageshow_scrollPosn;

function stageshow_OnClickSeatsSelectorButton(obj)
{
	var postvars = {
		jquery: "true"
	};
	
	switch(obj.id)
	{
		case "selectseats":		
			stageshow_scrollPosn = jQuery(window).scrollTop();
			jQuery('#trolley').css("visibility", "hidden"); 
			jQuery('#stageshow-trolley-trolley-std').hide();
			jQuery(SeatsLoadingBlockId).css("padding-top", "");
			break;
			
		case "seatsselected":
			postvars["PerfId"] = jQuery("#PerfId").val();
			postvars["stageshow-seatselected-seats"] = jQuery("#stageshow-seatselected-seats").val();
			postvars["stageshow-seatselected-zones"] = jQuery("#stageshow-seatselected-zones").val();		

			var seatSelectorHeight = jQuery('#stageshow-trolley-trolley-std').outerHeight();
			var loadingHeight = jQuery(SeatsLoadingBlockId).outerHeight();
			var padding = seatSelectorHeight - loadingHeight;
			
			jQuery(SeatsLoadingBlockId).css("padding-top", padding + "px");
			loadingHeight = jQuery(SeatsLoadingBlockId).outerHeight();

			jQuery('#stageshow-trolley-trolley-std').hide();
			break;
			
		default:
			break;
	}
	
	jQuery(SeatsLoadingBlockId).show();
			
	return StageShowLib_JQuery_ActionTrolleyButton(obj, 1, postvars, "stageshow_SeatsSelectorCallback");
}

function stageshow_SeatsSelectorCallback(data, inst, buttonId, qty)
{
	/* Call the standard callabck function */
	StageShowLib_JQuery_Callback(data, inst, buttonId, qty);
	
	switch(buttonId)
	{
		case "selectseats":
			stageshow_OnSeatsLoad();
			break;	
			
		case "seatsselected":
			stageshow_OnClickSelectshow(lastSelectShowObj);
			stageshow_OnClickSelectperf(lastSelectPerfObj);
		
			jQuery(SeatsLoadingBlockId).hide();
			jQuery(SeatLayoutBlockId).show();
			jQuery('#trolley').css("visibility", "visible"); 
			jQuery('#stageshow-trolley-trolley-std').show();
			
			/* Scroll back to the position before seats selection */
			jQuery(window).scrollTop(stageshow_scrollPosn);
			break;	
			
		default:
			break;	
	}
	
}

function stageshow_OnClickSelectseats(obj)
{
	if (typeof stageshowCustom_OnClickSelectseats == 'function') 
	{ 
  		return stageshowCustom_OnClickSelectseats(obj); 
	}	

	rtnVal = stageshow_OnClickSeatsSelectorButton(obj); 		
	return rtnVal;
}

function stageshow_OnClickSeatsselected(obj)
{
	if (typeof stageshowCustom_OnClickSeatsselected == 'function') 
	{ 
  		return stageshowCustom_OnClickSeatsselected(obj); 
	}	
	
	rtnVal = stageshow_OnClickSeatsSelectorButton(obj); 		
	return rtnVal;
}

function stageshow_OnClickReserve(obj)
{
	if (typeof stageshowCustom_OnClickReserve == 'function') 
	{ 
  		return stageshowCustom_OnClickReserve(obj); 
	}	
	StageShowLib_BeforeSubmit(obj, stageshowlib_cssDomain);
	return true;
}

function stageshow_OnClickCheckout(obj)
{
	if (typeof stageshowCustom_OnClickCheckout == 'function') 
	{ 
  		return stageshowCustom_OnClickCheckout(obj); 
	}
	StageShowLib_BeforeSubmit(obj, stageshowlib_cssDomain);
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
	
	return StageShowLib_JS_OnClickSubmitDetails(obj); 
}

function stageshow_OnClickRemove(obj, inst)
{
	if (typeof stageshowCustom_OnClickRemove == 'function') 
	{ 
  		return stageshowCustom_OnClickRemove(obj, inst); 
	}
	
	return StageShowLib_JQuery_OnClickTrolleyButton(obj, inst, "stageshow_JQuery_Callback"); 
}

function stageshow_PurgeDrilldownAtts(newAtts)
{
	for (var index=0; index<stageshowlib_attStrings.length; index++) 
	{
		var origAtts = stageshowlib_attStrings[index];
		origAtts = origAtts.split(",");
		
		for (var attId=0; attId<origAtts.length; attId++) 
		{
			var thisAtt = origAtts[attId].split("=");
			var key = thisAtt[0];
			
			if (key == "scatt_dd_id") continue;
			if (key == "scatt_dd_perf") continue;

			var attval = thisAtt[1];
			newAtts = newAtts + ',' + key + '=' + attval;
		}
		stageshowlib_attStrings[index] = newAtts;
	}
	
}

var	lastSelectShowObj = null;

function stageshow_OnClickSelectshow(obj, inst)
{
	if (obj == null) return;
	
	lastSelectShowObj = obj;
	
	jQuery(".stageshow-selector-showbutton").show();
	jQuery(".stageshow-selector-perfrow").hide();
	
	var ourName = obj.id;
	var perfRowClass = ourName.replace("stageshow-selbutton-show-", "stageshow-selector-perfrow-");
	jQuery("."+perfRowClass).show();
	jQuery("#"+obj.id).hide();
}

var	lastSelectPerfObj = null;

function stageshow_OnClickSelectperf(obj, inst)
{
	if (obj == null) return;
	
	lastSelectPerfObj = obj;
	
	var ourName = obj.id;
	var show_perf_parts = ourName.replace("stageshow-selbutton-perf-", "").split("-");;
	var showID = show_perf_parts[0];
	var perfID = show_perf_parts[1];
	
	var newAtts = "scatt_dd_id="+showID+",scatt_dd_perf="+perfID+",";
	stageshow_PurgeDrilldownAtts(newAtts);
	
	jQuery("#stageshow-selector-table").hide();

	jQuery(".stageshow-boxoffice-row").hide();	
	
	var rowsClassId = ".stageshow-boxoffice-row-perf" + perfID;	
	jQuery(rowsClassId).show();
	
	var showDivId = "#stageshow-boxoffice-body-" + showID;
	jQuery(showDivId).show();
	
	jQuery("#stageshow-selbutton-back-div").show();
}

function stageshow_OnClickSelectorback()
{
	lastSelectPerfObj = null;

	stageshow_PurgeDrilldownAtts('');
	
	/* Hide the button */
	jQuery("#stageshow-selbutton-back-div").hide();

	/* Hide all box-office ticket entries */
	jQuery(".stageshow-boxoffice-body").hide();

	/* Show the selector */
	jQuery("#stageshow-selector-table").show();
}
