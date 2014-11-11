
function StageShowLib_addWindowsLoadHandler(newHandler)
{
	var oldonload = window.onload;
	if (typeof window.onload != "function") 
	{
		window.onload = newHandler;
	} 
	else 
	{
		window.onload = function() 
		{
          oldonload();
          newHandler();
        }
	}
}

function StageShowLib_OnChangeDonation(obj)
{
	var seatId = obj.id;
	var saleDonation = parseFloat(obj.value);
	if (isNaN(saleDonation))
	{
		saleDonation = 0;
	}
	else
	{
		saleDonation = Math.abs(saleDonation);
	}
	
	var subTotalObj = document.getElementById("saleTrolleyTotal");
	var finalTotalObj = document.getElementById("stageshow-trolley-totalval");
	var subTotal = subTotalObj.value;
	
	var newTotalVal = parseFloat(subTotal);
	newTotalVal += saleDonation;
	newTotalVal += 0.00001; /* To force rounding error ... then it is corrected below */
		 	
	var newTotal = newTotalVal.toString();

	var origDps = StageShowLib_NumberOfDps(subTotal);
	var newDps = StageShowLib_NumberOfDps(newTotal);
	while (newDps < origDps)
	{
		if (newDps == 0) newTotal += '.';
		newTotal += '0';
		newDps++;
	}

	if (newDps > origDps)
	{
		/* Limit the number of decimal points */
		newTotal = newTotal.substr(0, newTotal.length + origDps - newDps);
	}
	
	finalTotalObj.innerHTML = newTotal;
}

function StageShowLib_NumberOfDps(price)
{
	var priceFormat, dpLen;
	var dpPosn = price.indexOf('.');
	if (dpPosn < 0)
	{
		dpLen = 0;
	}
	else
	{
		dpLen = price.length-dpPosn-1;
	}
	
	return dpLen;
}

function StageShowLib_HideElement(obj)
{
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(obj.id);
	
	// Hide the settings row
	tabElem.style.display = 'none';
}
