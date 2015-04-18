
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
