
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
	var classPosn = className.search('stageshow-boxoffice-seat-requested');
	
	/* Remove existing class specifier */
	className  = className.replace('stageshow-boxoffice-seat-available', ' ');
	className  = className.replace('stageshow-boxoffice-seat-requested', ' ');
	className  = className.replace('stageshow-boxoffice-seat-unknown', ' ');
	className  = className.replace('  ', ' ');
	
	seatName = 'Row ' + seatId.replace('_', ' Seat ');
	seatName = obj.title;
	
	if (classPosn < 0)
	{
		className = 'stageshow-boxoffice-seat-requested ' + className;
		seatsElem.innerHTML = seatName + ' Changed to Booked';		
	}
	else
	{
		className = 'stageshow-boxoffice-seat-available ' + className;
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
		paramNameAndValue = params[index].split("=");
		if (paramNameAndValue.length < 2)
			continue;
		
		paramId = paramNameAndValue[0];
		if (paramNameAndValue[0] == changedElemName)
		{
			paramNameAndValue[1] = obj.value;
		}
		buttonHref += paramNameAndValue[0] + "=" + paramNameAndValue[1] + "&";			
	}
	
	/* Update button URL */
	buttonObj.setAttribute('href', buttonHref);
	
	return true;
}