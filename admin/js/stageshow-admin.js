
function stageshow_OnClickSeatingID(obj)
{
	var selectId = obj.id;
	var selectedIndex = obj.selectedIndex;
	if (typeof(selectedIndex) == 'undefined')
	{
		selectedIndex = obj.value;
	}
	var elemId = selectId.replace('perfSeatingID', '');
	var showMaxSeats = (selectedIndex == 0);
	var seatsObjId = 'perfSeats' + elemId;
	var seatsObj = document.getElementById(seatsObjId);
	if (showMaxSeats)
	{
		seatsObj.style.display = '';
	}
	else
	{
		seatsObj.style.display = 'none';
	}
}

function stageshow_TestClickSeat(obj)
{
	var seatId, hiddenSeatsElem, hiddenZonesElem;
	
	seatIdParts = obj.id.split("-");
	seatId = seatIdParts[seatIdParts.length-1];
		
	seatsElem = document.getElementById("stageshow-boxoffice-layout-seatdef");

	var className = obj.className;
	var classPosn = className.search(SeatRequestedClassText);
	
	/* Remove existing class specifier */
	className  = className.replace(SeatAvailableClassText, ' ');
	className  = className.replace(SeatRequestedClassText, ' ');
	className  = className.replace(SeatUnknownClassText, ' ');
	className  = className.replace('  ', ' ');
	
	seatName = 'Row ' + seatId.replace('_', ' Seat ');
	seatName = obj.title;
	
	if (classPosn < 0)
	{
		className = SeatRequestedClassText + ' ' + className;
		seatsElem.innerHTML = seatName + ' Changed to Booked';		
	}
	else
	{
		className = SeatAvailableClassText + ' ' + className;
		seatsElem.innerHTML = seatName + ' Changed to Available';		
	}
	obj.className = className;
	
}

function stageshow_DisableLinkButton(buttonId)
{
	buttonObj = document.getElementById(buttonId);
	buttonObj.removeAttribute('href');
}

function stageshow_OnChangeZoneRef(obj)
{
	return stageshow_OnChangeZoneEntry(obj, "zoneRef");
}

function stageshow_OnChangeZoneSpec(obj)
{
	return stageshow_OnChangeZoneEntry(obj, "zoneSpec");
}

function stageshow_OnChangeZoneDecode(obj)
{
	return stageshow_OnChangeZoneEntry(obj, "seatingDecodeTable");
}

function stageshow_OnChangeZoneEntry(obj, elemRootId)
{
	changedElemName = obj.name;
	changedElemValue = obj.value;
	
	/* Extract SeatingID and Zone ID from name */
	ids = changedElemName.replace(elemRootId, "");
	idParts = ids.split("_");
	seatingId = idParts[0];
	
	/* Get View Template Link Button Object */
	buttonId = "stageshow-viewtemplate-" + seatingId;
	buttonObj = document.getElementById(buttonId);
		
	/* Get Link URL from object */
	buttonHref = buttonObj.getAttribute('href')

	/* Remove this Zone Spec from URL */
	HrefUrlAndParams = buttonHref.split("?");
	buttonHref = HrefUrlAndParams[0] + "?";
	params = HrefUrlAndParams[1].split("&");
	
	/* Add new value of Zone Spec to URL */
	for (var index=0; index<params.length; index++) 
	{
		var i = params[index].indexOf('=');
		var paramId = params[index].slice(0, i);
		var paramValue = params[index].slice(i + 1);

		if (paramId == changedElemName)
		{
			paramValue = changedElemValue;
		}
		buttonHref += paramId + "=" + paramValue + "&";			
	}
	
	/* Update button URL */
	buttonObj.setAttribute('href', buttonHref);
	
	return true;
}