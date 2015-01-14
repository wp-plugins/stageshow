
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

function StageShowLib_OnChangeTrolleyTotal(obj)
{
	var donationObj = document.getElementById('saleDonation');
	var saleDonation = 0;
	if (donationObj != null)
	{
		var saleDonation = parseFloat(donationObj.value);
		if (isNaN(saleDonation))
		{
			saleDonation = 0;
		}
		else
		{
			saleDonation = Math.abs(saleDonation);
		}		
	}

	var postValue = 0;
	var postTicketsObj = document.getElementById('salePostTickets');
	if (postTicketsObj != null)
	{
		var salePostageRowObj = document.getElementById('stageshow-trolley-postagerow');
		if (postTicketsObj.checked)
		{
			var salePostageObj = document.getElementById('salePostage');
			postValue = parseFloat(salePostageObj.value);
			
			salePostageRowObj.style.display = '';
		}
		else
		{			
			salePostageRowObj.style.display = 'none';
		}
	}

	var subTotalObj = document.getElementById("saleTrolleyTotal");
	var finalTotalObj = document.getElementById("stageshow-trolley-totalval");
	var subTotal = subTotalObj.value;
	
	var newTotalVal = parseFloat(subTotal);
	newTotalVal += saleDonation;
	newTotalVal += postValue;
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
