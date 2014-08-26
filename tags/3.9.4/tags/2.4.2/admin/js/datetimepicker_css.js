//Javasript name: My Date Time Picker
//Date created: 16-Nov-2003 23:19
//Creator: TengYong Ng
//Website: http://www.rainforestnet.com
//Copyright (c) 2003 TengYong Ng
//FileName: DateTimePicker_css.js
//Version: 2.2.4
// Note: Permission given to use and modify this script in ANY kind of applications if
//       header lines are left unchanged.
//Permission is granted to redistribute and modify this javascript under a FreeBSD License.
//New Css style version added by Yvan Lavoie (Québec, Canada) 29-Jan-2009
//Formatted for JSLint compatibility by Labsmedia.com (30-Dec-2010)

//Extensively Modified to use CSS classes ... Malcolm Shergold 30-Dec-2013

//Global variables

var winCal;
var dtToday;
var Cal;
var exDateTime;//Existing Date and Time
var selDate;//selected date. version 1.7
var calSpanID = "calBorder"; // span ID
var domStyle = null; // span DOM object with style
var cnLeft = "0";//left coordinate of calendar span
var cnTop = "0";//top coordinate of calendar span
var xpos = 0; // mouse x position
var ypos = 0; // mouse y position
var calHeight = 0; // calendar height
var TimeMode = 24;// TimeMode value. 12 or 24
var StartYear = 1940; //First Year in drop down year selection
var EndYear = 5; // The last year of pickable date. if current year is 2011, the last year that still picker will be 2016 (2011+5)
var CalPosOffsetX = -1; //X position offset relative to calendar icon, can be negative value
var CalPosOffsetY = 0; //Y position offset relative to calendar icon, can be negative value

//Configurable parameters start
var SelDateColor = "#8DD53C"; 		//Backgrond color of selected date in textbox.

var HoverColor = "#E0FF38"; 		//color when mouse move over.

var WeekChar = 2;					//number of character for week day. if 2 then Mo,Tu,We. if 3 then Mon,Tue,Wed.
var DateSeparator = "-";			//Date Separator, you can change it to "-" if you want.
var ShowLongMonth = true;			//Show long month name in Calendar header. example: "January".
var ShowMonthYear = true;			//Show Month and Year in Calendar header.
var PrecedeZero = true;				//Preceding zero [true|false]
var MondayFirstDay = true;			//true:Use Monday as first day; false:Sunday as first day. [true|false]  //added in version 1.7
var UseImageFiles = true;			//Use image files with "arrows" and "close" button
var UseTimeDropdown = false;
var TimeDropdownIncrements = 1;		//Time Increments in Smallest Time Dropdown box 
//Configurable parameters end

//use the Month and Weekday in your preferred language.
var MonthName = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
var WeekDayName1 = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
var WeekDayName2 = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

var DTP_PageHeaderHeight = 40;

//end Configurable parameters

//end Global variable


// Calendar prototype
function Calendar(pDate, pCtrl)
{
	//Properties
	this.Date = pDate.getDate();//selected date
	this.Month = pDate.getMonth();//selected month number
	this.Year = pDate.getFullYear();//selected year in 4 digits
	this.Hours = pDate.getHours();

	if (pDate.getMinutes() < 10)
	{
		this.Minutes = "0" + pDate.getMinutes();
	}
	else
	{
		this.Minutes = pDate.getMinutes();
	}

	if (pDate.getSeconds() < 10)
	{
		this.Seconds = "0" + pDate.getSeconds();
	}
	else
	{
		this.Seconds = pDate.getSeconds();
	}
	this.MyWindow = winCal;
	this.Ctrl = pCtrl;
	this.Format = "ddMMyyyy";
	this.Separator = DateSeparator;
	this.ShowTime = false;
	this.Scroller = "DROPDOWN";
	if (pDate.getHours() < 12)
	{
		this.AMorPM = "AM";
	}
	else
	{
		this.AMorPM = "PM";
	}
	this.ShowSeconds = false;
	this.EnableDateMode = "";
}

Calendar.prototype.GetMonthIndex = function (shortMonthName)
{
	for (var i = 0; i < 12; i += 1)
	{
		if (MonthName[i].substring(0, 3).toUpperCase() === shortMonthName.toUpperCase())
		{
			return i;
		}
	}
	return -1;
};

Calendar.prototype.IncYear = function () {
    if (Cal.Year <= dtToday.getFullYear()+EndYear){
	    Cal.Year += 1;
	}
};

Calendar.prototype.DecYear = function () {
    if (Cal.Year > StartYear){
	    Cal.Year -= 1;
	}
};

Calendar.prototype.IncMonth = function() {
    if (Cal.Year <= dtToday.getFullYear() + EndYear) {
        Cal.Month += 1;
        if (Cal.Month >= 12) {
            Cal.Month = 0;
            Cal.IncYear();
        }
    }
};

Calendar.prototype.DecMonth = function() {
    if (Cal.Year >= StartYear) {
        Cal.Month -= 1;
        if (Cal.Month < 0) {
            Cal.Month = 11;
            Cal.DecYear();
        }
    }
};

Calendar.prototype.SwitchMth = function (intMth)
{
	Cal.Month = parseInt(intMth, 10);
};

Calendar.prototype.SwitchYear = function (intYear)
{
	Cal.Year = parseInt(intYear, 10);
};

Calendar.prototype.SetHour = function(intHour) {
    var MaxHour,
	MinHour,
	HourExp = new RegExp("^\\d\\d"),
	SingleDigit = new RegExp("^\\d{1}$");

    if (TimeMode === 24) {
        MaxHour = 23;
        MinHour = 0;
    }
    else if (TimeMode === 12) {
        MaxHour = 12;
        MinHour = 1;
    }
    else {
        alert("TimeMode can only be 12 or 24");
    }

    if ((HourExp.test(intHour) || SingleDigit.test(intHour)) && (parseInt(intHour, 10) > MaxHour)) {
        intHour = MinHour;
    }

    else if ((HourExp.test(intHour) || SingleDigit.test(intHour)) && (parseInt(intHour, 10) < MinHour)) {
        intHour = MaxHour;
    }

    intHour = parseInt(intHour, 10);
    if (SingleDigit.test(intHour)) {
        intHour = "0" + intHour;
    }

    if (HourExp.test(intHour) && (parseInt(intHour, 10) <= MaxHour) && (parseInt(intHour, 10) >= MinHour)) {
        if ((TimeMode === 12) && (Cal.AMorPM === "PM")) {
            if (parseInt(intHour, 10) === 12) {
                Cal.Hours = 12;
            }
            else {
                Cal.Hours = parseInt(intHour, 10) + 12;
            }
        }

        else if ((TimeMode === 12) && (Cal.AMorPM === "AM")) {
            if (intHour === 12) {
                intHour -= 12;
            }

            Cal.Hours = parseInt(intHour, 10);
        }

        else if (TimeMode === 24) {
            Cal.Hours = parseInt(intHour, 10);
        }
    }

};

Calendar.prototype.SetMinute = function (intMin)
{
	var MaxMin = 59,
	MinMin = 0,

	SingleDigit = new RegExp("\\d"),
	SingleDigit2 = new RegExp("^\\d{1}$"),
	MinExp = new RegExp("^\\d{2}$"),

	strMin = 0;

	if ((MinExp.test(intMin) || SingleDigit.test(intMin)) && (parseInt(intMin, 10) > MaxMin))
	{
		intMin = MinMin;
	}

	else if ((MinExp.test(intMin) || SingleDigit.test(intMin)) && (parseInt(intMin, 10) < MinMin))
	{
		intMin = MaxMin;
	}

	strMin = intMin + "";
	if (SingleDigit2.test(intMin))
	{
		strMin = "0" + strMin;
	}

	if ((MinExp.test(intMin) || SingleDigit.test(intMin)) && (parseInt(intMin, 10) <= 59) && (parseInt(intMin, 10) >= 0))
	{
		Cal.Minutes = strMin;
	}
};

Calendar.prototype.SetSecond = function (intSec)
{
	var MaxSec = 59,
	MinSec = 0,

	SingleDigit = new RegExp("\\d"),
	SingleDigit2 = new RegExp("^\\d{1}$"),
	SecExp = new RegExp("^\\d{2}$"),

	strSec = 0;

	if ((SecExp.test(intSec) || SingleDigit.test(intSec)) && (parseInt(intSec, 10) > MaxSec))
	{
		intSec = MinSec;
	}

	else if ((SecExp.test(intSec) || SingleDigit.test(intSec)) && (parseInt(intSec, 10) < MinSec))
	{
		intSec = MaxSec;
	}

	strSec = intSec + "";
	if (SingleDigit2.test(intSec))
	{
		strSec = "0" + strSec;
	}

	if ((SecExp.test(intSec) || SingleDigit.test(intSec)) && (parseInt(intSec, 10) <= 59) && (parseInt(intSec, 10) >= 0))
	{
		Cal.Seconds = strSec;
	}

};

Calendar.prototype.SetAmPm = function (pvalue)
{
	this.AMorPM = pvalue;
	if (pvalue === "PM")
	{
		this.Hours = parseInt(this.Hours, 10) + 12;
		if (this.Hours === 24)
		{
			this.Hours = 12;
		}
	}

	else if (pvalue === "AM")
	{
		this.Hours -= 12;
	}
};

Calendar.prototype.getShowHour = function() {
    var finalHour;

    if (TimeMode === 12) {
        if (parseInt(this.Hours, 10) === 0) {
            this.AMorPM = "AM";
            finalHour = parseInt(this.Hours, 10) + 12;
        }

        else if (parseInt(this.Hours, 10) === 12) {
            this.AMorPM = "PM";
            finalHour = 12;
        }

        else if (this.Hours > 12) {
            this.AMorPM = "PM";
            if ((this.Hours - 12) < 10) {
                finalHour = "0" + ((parseInt(this.Hours, 10)) - 12);
            }
            else {
                finalHour = parseInt(this.Hours, 10) - 12;
            }
        }
        else {
            this.AMorPM = "AM";
            if (this.Hours < 10) {
                finalHour = "0" + parseInt(this.Hours, 10);
            }
            else {
                finalHour = this.Hours;
            }
        }
    }

    else if (TimeMode === 24) {
        if (this.Hours < 10) {
            finalHour = "0" + parseInt(this.Hours, 10);
        }
        else {
            finalHour = this.Hours;
        }
    }

    return finalHour;
};

Calendar.prototype.getShowAMorPM = function ()
{
	return this.AMorPM;
};

Calendar.prototype.GetMonthName = function (IsLong)
{
	var Month = MonthName[this.Month];
	if (IsLong)
	{
		return Month;
	}
	else
	{
		return Month.substr(0, 3);
	}
};

Calendar.prototype.GetMonDays = function() { //Get number of days in a month

    var DaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if (Cal.IsLeapYear()) {
        DaysInMonth[1] = 29;
    }

    return DaysInMonth[this.Month];
};

Calendar.prototype.IsLeapYear = function ()
{
	if ((this.Year % 4) === 0)
	{
		if ((this.Year % 100 === 0) && (this.Year % 400) !== 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	else
	{
		return false;
	}
};

Calendar.prototype.FormatDate = function (pDate)
{
	var MonthDigit = this.Month + 1;
	if (PrecedeZero === true)
	{
		if ((pDate < 10) && String(pDate).length===1) //length checking added in version 2.2
		{		
			pDate = "0" + pDate;
		}
		if (MonthDigit < 10)
		{
			MonthDigit = "0" + MonthDigit;
		}
	}

	switch (this.Format.toUpperCase())
	{
		case "DDMMYYYY":
		return (pDate + DateSeparator + MonthDigit + DateSeparator + this.Year);
		case "DDMMMYYYY":
		return (pDate + DateSeparator + this.GetMonthName(false) + DateSeparator + this.Year);
		case "MMDDYYYY":
		return (MonthDigit + DateSeparator + pDate + DateSeparator + this.Year);
		case "MMMDDYYYY":
		return (this.GetMonthName(false) + DateSeparator + pDate + DateSeparator + this.Year);
		case "YYYYMMDD":
		return (this.Year + DateSeparator + MonthDigit + DateSeparator + pDate);
		case "YYMMDD":
		return (String(this.Year).substring(2, 4) + DateSeparator + MonthDigit + DateSeparator + pDate);
		case "YYMMMDD":
		return (String(this.Year).substring(2, 4) + DateSeparator + this.GetMonthName(false) + DateSeparator + pDate);
		case "YYYYMMMDD":
		return (this.Year + DateSeparator + this.GetMonthName(false) + DateSeparator + pDate);
		default:
		return (pDate + DateSeparator + (this.Month + 1) + DateSeparator + this.Year);
	}
};

// end Calendar prototype

function GenCell(pValue, pHighLight, pClassId, pClickable)
{ //Generate table cell with value
	var PValue,
	PCellStr,
	PClickable,
	vTimeStr;

	if (!pValue)
	{
		PValue = "";
	}
	else
	{
		PValue = pValue;
	}

	if (pClassId === undefined) {
	    pClassId = 'calBgClass';
	}
	
	if (pClickable !== undefined){
		PClickable = pClickable;
	}
	else{
		PClickable = true;
	}

	if (Cal.ShowTime)
	{
		vTimeStr = ' ' + Cal.Hours + ':' + Cal.Minutes;
		if (Cal.ShowSeconds)
		{
			vTimeStr += ':' + Cal.Seconds;
		}
		if (TimeMode === 12)
		{
			vTimeStr += ' ' + Cal.AMorPM;
		}
	}

	else
	{
		vTimeStr = "";
	}

	if (PValue !== "")
	{
		if (PClickable === true) 
		{
		    if (Cal.ShowTime === true)
		    { 
				PCellStr = "<td id='c" + PValue + "' class='calTD " + pClassId + "' onmouseover='highlightDate(this, 0);' onmouseout=\"highlightDate(this, 1);\" onmousedown='selectDate(this," + PValue + ");'>" + PValue + "</td>"; 
			}
		    else 
			{ 
				PCellStr = "<td class='calTD " + pClassId + "' onmouseover='highlightDate(this, 0);' onmouseout=\"highlightDate(this, 1);\" onClick=\"javascript:callback('" + Cal.Ctrl + "','" + Cal.FormatDate(PValue) + "');\">" + PValue + "</td>"; 
			}
		}
		else
		{ PCellStr = "<td class='calTD " + pClassId + "'>"+PValue+"</td>"; }
	}
	else
	{ PCellStr = "<td class='calTD " + pClassId + "'>&nbsp;</td>"; }

	return PCellStr;
}

function CreateSelectCtrl(selectName, selectMin, selectMax, selectInc, selectCurrent, selectEvent)
{
	selectCurrent -= selectCurrent % selectInc;
	if (selectInc != 1)
	{
		if (selectName == 'minute') Cal.SetMinute(selectCurrent);
		if (selectName == 'second') Cal.SetSecond(selectCurrent);
	}
	
	selectCtrl = '<select class="calSelectTime" id="' + selectName + '" onchange="javascript:' + selectEvent + '(this.value)">';
	
	for (selectVal = selectMin; selectVal <= selectMax; selectVal+=selectInc)
	{
		selectParam = '';
		if (selectVal >= selectCurrent) 
		{
			selectParam = ' selected="" ';
			selectCurrent = selectMax + 1;
		}
		selectValText = (selectVal <= 9) ? '0' + selectVal : selectVal;
		selectCtrl += '<option value="' + selectVal + '" ' + selectParam + '>' + selectValText + '</option>';		
	}
	selectCtrl += '</select>';
	return selectCtrl;
}

function RenderCssCal(bNewCal)
{
	if (typeof bNewCal === "undefined" || bNewCal !== true)
	{
		bNewCal = false;
	}
	var vCalHeader,
	vCalData,
	vCalTime = "",
	vCalClosing = "",
	winCalData = "",
	CalDate,

	i,
	j,

	SelectStr,
	vDayCount = 0,
	vFirstDay,

	WeekDayName = [],//Added version 1.7
	strCell,

	showHour,
	ShowArrows = false,
	HourCellWidth = "35px", //cell width with seconds.

	SelectAm,
	SelectPm,

	funcCalback,

	headID,
	e,
	cssStr,
	style,
	cssText,
	span;

	calHeight = 0; // reset the window height on refresh

	// Set the default cursor for the calendar

	winCalData = "<span class='calSpanAll'>";
	vCalHeader = "<table class='calTableAll calBgClass'><tbody>";

	//Table for Month & Year Selector

	vCalHeader += "<tr><td colspan='7'><table class='calTableMonthYear'><tr>";
	//******************Month and Year selector in dropdown list************************

	if (Cal.Scroller === "DROPDOWN")
	{
	    vCalHeader += "<td align='center'><select name='MonthSelector' onChange='javascript:Cal.SwitchMth(this.selectedIndex);RenderCssCal();'>";
		for (i = 0; i < 12; i += 1)
		{
			if (i === Cal.Month)
			{
				SelectStr = "Selected";
			}
			else
			{
				SelectStr = "";
			}
			vCalHeader += "<option " + SelectStr + " value=" + i + ">" + MonthName[i] + "</option>";
		}

		vCalHeader += "</select></td>";
		//Year selector

		vCalHeader += "<td align='center'><select name='YearSelector' size='1' onChange='javascript:Cal.SwitchYear(this.value);RenderCssCal();'>";
		for (i = StartYear; i <= (dtToday.getFullYear() + EndYear); i += 1)
		{
			if (i === Cal.Year)
			{
				SelectStr = 'selected="selected"';
			}
			else
			{
				SelectStr = '';
			}
			vCalHeader += "<option " + SelectStr + " value=" + i + ">" + i + "</option>\n";
		}
		vCalHeader += "</select></td>\n";
		calHeight += 30;
	}

	//******************End Month and Year selector in dropdown list*********************

	//******************Month and Year selector in arrow*********************************

	else /* if (Cal.Scroller === "ARROW") */
	{
		if (UseImageFiles)
		{
			vCalHeader += "<td><div class='calNavButton calButtonPrevYear' onmousedown='javascript:Cal.DecYear();RenderCssCal();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'><div></td>\n";//Year scroller (decrease 1 year)
			vCalHeader += "<td><div class='calNavButton calButtonPrevMonth' onmousedown='javascript:Cal.DecMonth();RenderCssCal();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'><div></td>\n"; //Month scroller (decrease 1 month)
			vCalHeader += "<td width='70%' class='calR calMonthName'>"+ Cal.GetMonthName(ShowLongMonth) + " " + Cal.Year + "</td>"; //Month and Year
			vCalHeader += "<td><div class='calNavButton calButtonNextMonth' onmousedown='javascript:Cal.IncMonth();RenderCssCal();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'><div></td>\n"; //Month scroller (increase 1 month)
			vCalHeader += "<td><div class='calNavButton calButtonNextYear' onmousedown='javascript:Cal.IncYear();RenderCssCal();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'><div></td>\n"; //Year scroller (increase 1 year)
			calHeight += 22;
		}
		else
		{
			vCalHeader += "<td><span class='calMonthNav' id='dec_year' title='reverse year' onmousedown='javascript:Cal.DecYear();RenderCssCal();' onmouseover='changeBorder(this, 0)' onmouseout='changeBorder(this, 1)'>-</span></td>";//Year scroller (decrease 1 year)
			vCalHeader += "<td><span class='calMonthNav' id='dec_month' title='reverse month' onmousedown='javascript:Cal.DecMonth();RenderCssCal();' onmouseover='changeBorder(this, 0)' onmouseout='changeBorder(this, 1)'>&lt;</span></td>\n";//Month scroller (decrease 1 month)
			vCalHeader += "<td width='70%' class='calR calMonthName'>" + Cal.GetMonthName(ShowLongMonth) + " " + Cal.Year + "</td>\n"; //Month and Year
			vCalHeader += "<td><span class='calMonthNav' id='inc_month' title='forward month' onmousedown='javascript:Cal.IncMonth();RenderCssCal();' onmouseover='changeBorder(this, 0)' onmouseout='changeBorder(this, 1)'>&gt;</span></td>\n";//Month scroller (increase 1 month)
			vCalHeader += "<td><span class='calMonthNav' id='inc_year' title='forward year' onmousedown='javascript:Cal.IncYear();RenderCssCal();'  onmouseover='changeBorder(this, 0)' onmouseout='changeBorder(this, 1)'>+</span></td>\n";//Year scroller (increase 1 year)
			calHeight += 22;
		}
	}

	vCalHeader += "</tr></table></td></tr>";

	//******************End Month and Year selector in arrow******************************

	//Calendar header shows Month and Year
	if (ShowMonthYear && Cal.Scroller === "DROPDOWN")
	{
	    vCalHeader += "<tr><td colspan='7' class='calR calMonthName'>" + Cal.GetMonthName(ShowLongMonth) + " " + Cal.Year + "</td></tr>";
		calHeight += 19;
	}

	//Week day header

	vCalHeader += "<tr><td colspan=\"7\"><table class='calTableDates'><tr>";
	if (MondayFirstDay === true)
	{
		WeekDayName = WeekDayName2;
	}
	else
	{
		WeekDayName = WeekDayName1;
	}
	for (i = 0; i < 7; i += 1)
	{
	    vCalHeader += "<td class='calTD calWeekHeadClass' >" + WeekDayName[i].substr(0, WeekChar) + "</td>";
	}

	calHeight += 19;
	vCalHeader += "</tr>";
	//Calendar detail
	CalDate = new Date(Cal.Year, Cal.Month);
	CalDate.setDate(1);

	vFirstDay = CalDate.getDay();

	//Added version 1.7
	if (MondayFirstDay === true)
	{
		vFirstDay -= 1;
		if (vFirstDay === -1)
		{
			vFirstDay = 6;
		}
	}

	//Added version 1.7
	vCalData = "<tr>";
	calHeight += 19;
	for (i = 0; i < vFirstDay; i += 1)
	{
		vCalData = vCalData + GenCell();
		vDayCount = vDayCount + 1;
	}

	//Added version 1.7
	for (j = 1; j <= Cal.GetMonDays(); j += 1)
	{
		if ((vDayCount % 7 === 0) && (j > 1))
		{
			vCalData = vCalData + "<tr>";
		}

		vDayCount = vDayCount + 1;
		//added version 2.1.2
		dayClass = 'calDayClass';
		cellEnabled = true;
		if (Cal.EnableDateMode === "future" && ((j < dtToday.getDate()) && (Cal.Month === dtToday.getMonth()) && (Cal.Year === dtToday.getFullYear()) || (Cal.Month < dtToday.getMonth()) && (Cal.Year === dtToday.getFullYear()) || (Cal.Year < dtToday.getFullYear())))
		{
			dayClass += ' calDisableClass'; //Before today's date is not clickable
			cellEnabled = false; 
        }
        else if (Cal.EnableDateMode === "past" && ((j >= dtToday.getDate()) && (Cal.Month === dtToday.getMonth()) && (Cal.Year === dtToday.getFullYear()) || (Cal.Month > dtToday.getMonth()) && (Cal.Year === dtToday.getFullYear()) || (Cal.Year > dtToday.getFullYear()))) {
            dayClass += ' calDisableClass'; //After today's date is not clickable
			cellEnabled = false; 
        }
		//if End Year + Current Year = Cal.Year. Disable.
		else if (Cal.Year > (dtToday.getFullYear()+EndYear))
		{
		    dayClass += ' calDisableClass';
			cellEnabled = false; 
		}
				
		if ((j === dtToday.getDate()) && (Cal.Month === dtToday.getMonth()) && (Cal.Year === dtToday.getFullYear()))
		{
			dayClass += ' calTodayClass';//Highlight today's date
		}
		
		if ((j === selDate.getDate()) && (Cal.Month === selDate.getMonth()) && (Cal.Year === selDate.getFullYear())){
		     //modified version 1.7
			dayClass += ' calSelDateClass';
        }
			
		if (MondayFirstDay === true)
		{
			if (vDayCount % 7 === 0)
			{
				dayClass += ' calSundayClass';
			}
			else if ((vDayCount + 1) % 7 === 0)
			{
				dayClass += ' calSaturdayClass';
			}
			else
			{
				dayClass += ' calWeekDayClass';
			}
		}
		else
		{
			if (vDayCount % 7 === 0)
			{
				dayClass += ' calSaturdayClass';
			}
			else if ((vDayCount + 6) % 7 === 0)
			{
				dayClass += ' calSundayClass';
			}
			else
			{
				dayClass += ' calWeekDayClass';
			}
		}

		strCell = GenCell(j, null, dayClass, cellEnabled);
		vCalData = vCalData + strCell;

		if ((vDayCount % 7 === 0) && (j < Cal.GetMonDays()))
		{
			vCalData = vCalData + "</tr>";
			calHeight += 19;
		}
	}

	// finish the table proper

	if (vDayCount % 7 !== 0)
	{
		while (vDayCount % 7 !== 0)
		{
			vCalData = vCalData + GenCell();
			vDayCount = vDayCount + 1;
		}
	}

	vCalData = vCalData + "</table></td></tr>";


	//Time picker
	if (Cal.ShowTime === true)
	{
		showHour = Cal.getShowHour();

		if (Cal.ShowSeconds === false && TimeMode === 24)
		{
			ShowArrows = true;
			HourCellWidth = "10px";
		}

		vCalTime = "<tr><td colspan='7'><table class='calTimeTable'><tbody><tr>";

		if (UseTimeDropdown)
		{
			vCalTime += '<td>';
			vCalTime += CreateSelectCtrl('hour', 0, 23, 1, showHour, 'Cal.SetHour');
			vCalTime += ":";
			if (Cal.ShowSeconds)
			{
				vCalTime += CreateSelectCtrl('minute', 0, 59, 1, Cal.Minutes, 'Cal.SetMinute');
				vCalTime += ":";
				vCalTime += CreateSelectCtrl('second', 0, 59, TimeDropdownIncrements, Cal.Seconds, 'Cal.SetSecond');
			}
			else
			{
				vCalTime += CreateSelectCtrl('minute', 0, 59, TimeDropdownIncrements, Cal.Minutes, 'Cal.SetMinute');
			}
			vCalTime += '</td>';
			vCalTime += '</tr>';
		}
		else
		{
			if (ShowArrows && UseImageFiles) //this is where the up and down arrow control the hour.
			{
			    vCalTime += "<td class='calTimeText'><table class='calTimeSpinButtons'><tr><td class='calTimeText'><div class='calNavButton calButtonUp' onclick='nextStep(\"Hour\", \"plus\");' onmousedown='startSpin(\"Hour\", \"plus\");' onmouseup='stopSpin();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'></div></td></tr><tr><td class='calTimeText'><div class='calNavButton calButtonDown' onclick='nextStep(\"Hour\", \"minus\");' onmousedown='startSpin(\"Hour\", \"minus\");' onmouseup='stopSpin();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'></div></td></tr></table></td>\n";
			}

			vCalTime += "<td class='calTimeEditCell'><input class='calTimeEdit' type='text' name='hour' maxlength=2 size=1 value=" + showHour + " onkeyup=\"javascript:Cal.SetHour(this.value)\"></td>";
			vCalTime += "<td class='calTimeText'>:</td>";
			vCalTime += "<td class='calTimeEditCell'><input class='calTimeEdit' type='text' name='minute' maxlength=2 size=1 value=" + Cal.Minutes + " onkeyup=\"javascript:Cal.SetMinute(this.value)\">";

			if (Cal.ShowSeconds)
			{
		    	vCalTime += "</td><td class='calTimeText'>:</td><td class='calTimeEditCell'>";
				vCalTime += "<input class='calTimeEdit' type='text' name='second' maxlength=2 size=1 value=" + Cal.Seconds + " onkeyup=\"javascript:Cal.SetSecond(parseInt(this.value,10))\">";
			}

			if (TimeMode === 12)
			{
				SelectAm = (Cal.AMorPM === "AM") ? "Selected" : "";
				SelectPm = (Cal.AMorPM === "PM") ? "Selected" : "";

				vCalTime += "</td><td>";
				vCalTime += "<select name=\"ampm\" onChange=\"javascript:Cal.SetAmPm(this.options[this.selectedIndex].value);\">\n";
				vCalTime += "<option " + SelectAm + " value=\"AM\">AM</option>";
				vCalTime += "<option " + SelectPm + " value=\"PM\">PM<option>";
				vCalTime += "</select>";
			}

			if (ShowArrows && UseImageFiles) //this is where the up and down arrow to change the "Minute".
			{
			    vCalTime += "</td>\n<td class='calTimeText'><table class='calTimeSpinButtons'><tr><td class='calTimeText'><div class='calNavButton calButtonUp' onclick='nextStep(\"Minute\", \"plus\");' onmousedown='startSpin(\"Minute\", \"plus\");' onmouseup='stopSpin();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'></div></td></tr><tr><td class='calTimeText'><div class='calNavButton calButtonDown' onclick='nextStep(\"Minute\", \"minus\");' onmousedown='startSpin(\"Minute\", \"minus\");' onmouseup='stopSpin();' onmouseover='highlightControl(this, 0)' onmouseout='highlightControl(this, 1)'></div></td></tr></table>";
			}
		}

		vCalTime += "</td>\n<td align='right' valign='bottom' width='" + HourCellWidth + "px'></td></tr>";
		vCalTime += "<tr><td colspan='8'>";
		vCalTime += "<input class='button-secondary calButton' onClick='javascript:closewin(\"" + Cal.Ctrl + "\");'  type=\"button\" value=\"OK\">&nbsp;";
		vCalTime += "<input class='button-secondary calButton' onClick='javascript: winCal.style.visibility = \"hidden\"' type=\"button\" value=\"Cancel\"></td></tr>";
	}
	else //if not to show time.
	{
	    vCalTime += "\n<tr>\n<td colspan='7' class='calCloseCell'>";
	    //close button
        vCalClosing += "<div class='calClose' onmousedown='javascript:closewin(\"" + Cal.Ctrl + "\"); stopSpin();' onmouseover='' onmouseout=''>X</div></td>";
	    vCalClosing += "</tr>";
	}
	vCalClosing += "</tbody></table></td></tr>";
	calHeight += 31;
	vCalClosing += "</tbody></table>\n</span>";

	//end time picker
	funcCalback = "function callback(id, datum) {";
	funcCalback += " var CalId = document.getElementById(id);if (datum=== 'undefined') { var d = new Date(); datum = d.getDate() + '/' +(d.getMonth()+1) + '/' + d.getFullYear(); } window.calDatum=datum;CalId.value=datum;";
	funcCalback += " if(Cal.ShowTime){";
	funcCalback += " CalId.value+=' '+Cal.getShowHour()+':'+Cal.Minutes;";
	funcCalback += " if (Cal.ShowSeconds)  CalId.value+=':'+Cal.Seconds;";
	funcCalback += " if (TimeMode === 12)  CalId.value+=''+Cal.getShowAMorPM();";
	funcCalback += "}if(CalId.onchange!=undefined) CalId.onchange();CalId.focus();winCal.style.visibility='hidden';}";


	// determines if there is enough space to open the cal above the position where it is called
	if (ypos > calHeight + DTP_PageHeaderHeight)
	{
		ypos = ypos - calHeight;
	}

	if (!winCal)
	{
		headID = document.getElementsByTagName("head")[0];

		// add javascript function to the span cal
		e = document.createElement("script");
		e.type = "text/javascript";
		e.language = "javascript";
		e.text = funcCalback;
		headID.appendChild(e);

/*		
		// add stylesheet to the span cal

		cssStr = ".calTD {font-family: verdana; font-size: 12px; text-align: center; border:0; }\n";
		cssStr += ".calR {font-family: verdana; font-size: 12px; text-align: center; font-weight: bold;}";

		style = document.createElement("style");
		style.type = "text/css";
		style.rel = "stylesheet";
		if (style.styleSheet)
		{ // IE
			style.styleSheet.cssText = cssStr;
		}
		else
		{ // w3c
			cssText = document.createTextNode(cssStr);
			style.appendChild(cssText);
		}
		headID.appendChild(style);
*/
		// create the outer frame that allows the cal. to be moved
		span = document.createElement("span");
		span.id = calSpanID;
		span.className = "calBgClass " + calSpanID;
		span.style.left = (xpos + CalPosOffsetX) + 'px';
		span.style.top = (ypos - CalPosOffsetY) + 'px';
		//span.style.width = CalWidth + 'px';
		span.style.zIndex = 100;
		document.body.appendChild(span);
		winCal = document.getElementById(calSpanID);
	}

	else
	{
		winCal.style.visibility = "visible";
		winCal.style.Height = calHeight;

		// set the position for a new calendar only
		if (bNewCal === true)
		{
			winCal.style.left = (xpos + CalPosOffsetX) + 'px';
			winCal.style.top = (ypos - CalPosOffsetY) + 'px';
		}
	}

	winCal.innerHTML = winCalData + vCalHeader + vCalData + vCalTime + vCalClosing;
	return true;
}


function NewCssCal(pSender, pFormat, pScroller, pShowTime, pTimeMode, pShowSeconds, pEnableDateMode)
{
	pCtrl = pSender.id;
	// get current date and time

	dtToday = new Date();
	Cal = new Calendar(dtToday);

	if (pShowTime !== undefined)
	{
	    if (pShowTime) {
	        Cal.ShowTime = true;
	    }
	    else {
	        Cal.ShowTime = false;
	    }

		if (pTimeMode)
		{
			pTimeMode = parseInt(pTimeMode, 10);
		}
		if (pTimeMode === 12 || pTimeMode === 24)
		{
			TimeMode = pTimeMode;
		}
		else
		{
			TimeMode = 24;
		}

		if (pShowSeconds !== undefined)
		{
			if (pShowSeconds)
			{
				Cal.ShowSeconds = true;
			}
			else
			{
				Cal.ShowSeconds = false;
			}
		}
		else
		{
			Cal.ShowSeconds = false;
		}

	}

	if (pCtrl !== undefined)
	{
		Cal.Ctrl = pCtrl;
	}

	if (pFormat!== undefined && pFormat !=="")
	{
		Cal.Format = pFormat.toUpperCase();
	}
	else
	{
		Cal.Format = "MMDDYYYY";
	}

	if (pScroller!== undefined && pScroller!=="")
	{
		ourScroller = pScroller.toUpperCase();
		switch (ourScroller)
		{
			case "ARROW":
			case "DROPDOWN":
				Cal.Scroller = ourScroller;
				break;
				
			default:
				Cal.Scroller = "DROPDOWN";
				break;
				
		}
    }

    if (pEnableDateMode !== undefined && (pEnableDateMode === "future" || pEnableDateMode === "past")) {
        Cal.EnableDateMode= pEnableDateMode;
    }

	exDateTime = document.getElementById(pCtrl).value; //Existing Date Time value in textbox.

	if (exDateTime)
	{ //Parse existing Date String
		var Sp1 = exDateTime.indexOf(DateSeparator, 0),//Index of Date Separator 1
		Sp2 = exDateTime.indexOf(DateSeparator, parseInt(Sp1, 10) + 1),//Index of Date Separator 2
		tSp1,//Index of Time Separator 1
		tSp2,//Index of Time Separator 2
		strMonth,
		strDate,
		strYear,
		intMonth,
		YearPattern,
		strHour,
		strMinute,
		strSecond,
		winHeight,
		offset = parseInt(Cal.Format.toUpperCase().lastIndexOf("M"), 10) - parseInt(Cal.Format.toUpperCase().indexOf("M"), 10) - 1,
		strAMPM = "";
		//parse month

		if (Cal.Format.toUpperCase() === "DDMMYYYY" || Cal.Format.toUpperCase() === "DDMMMYYYY")
		{
			if (DateSeparator === "")
			{
				strMonth = exDateTime.substring(2, 4 + offset);
				strDate = exDateTime.substring(0, 2);
				strYear = exDateTime.substring(4 + offset, 8 + offset);
			}
			else
			{
				if (exDateTime.indexOf("D*") !== -1)
				{   //DTG
					strMonth = exDateTime.substring(8, 11);
					strDate  = exDateTime.substring(0, 2);
					strYear  = "20" + exDateTime.substring(11, 13);  //Hack, nur für Jahreszahlen ab 2000
				}
				else
				{
					strMonth = exDateTime.substring(Sp1 + 1, Sp2);
					strDate = exDateTime.substring(0, Sp1);
					strYear = exDateTime.substring(Sp2 + 1, Sp2 + 5);
				}
			}
		}

		else if (Cal.Format.toUpperCase() === "MMDDYYYY" || Cal.Format.toUpperCase() === "MMMDDYYYY"){
			if (DateSeparator === ""){
				strMonth = exDateTime.substring(0, 2 + offset);
				strDate = exDateTime.substring(2 + offset, 4 + offset);
				strYear = exDateTime.substring(4 + offset, 8 + offset);
			}
			else{
				strMonth = exDateTime.substring(0, Sp1);
				strDate = exDateTime.substring(Sp1 + 1, Sp2);
				strYear = exDateTime.substring(Sp2 + 1, Sp2 + 5);
			}
		}

		else if (Cal.Format.toUpperCase() === "YYYYMMDD" || Cal.Format.toUpperCase() === "YYYYMMMDD")
		{
			if (DateSeparator === ""){
				strMonth = exDateTime.substring(4, 6 + offset);
				strDate = exDateTime.substring(6 + offset, 8 + offset);
				strYear = exDateTime.substring(0, 4);
			}
			else{
				strMonth = exDateTime.substring(Sp1 + 1, Sp2);
				strDate = exDateTime.substring(Sp2 + 1, Sp2 + 3);
				strYear = exDateTime.substring(0, Sp1);
			}
		}

		else if (Cal.Format.toUpperCase() === "YYMMDD" || Cal.Format.toUpperCase() === "YYMMMDD")
		{
			if (DateSeparator === "")
			{
				strMonth = exDateTime.substring(2, 4 + offset);
				strDate = exDateTime.substring(4 + offset, 6 + offset);
				strYear = exDateTime.substring(0, 2);
			}
			else
			{
				strMonth = exDateTime.substring(Sp1 + 1, Sp2);
				strDate = exDateTime.substring(Sp2 + 1, Sp2 + 3);
				strYear = exDateTime.substring(0, Sp1);
			}
		}

		if (isNaN(strMonth)){
			intMonth = Cal.GetMonthIndex(strMonth);
		}
		else{
			intMonth = parseInt(strMonth, 10) - 1;
		}
		if ((parseInt(intMonth, 10) >= 0) && (parseInt(intMonth, 10) < 12))	{
			Cal.Month = intMonth;
		}
		//end parse month

		//parse year
		YearPattern = /^\d{4}$/;
		if (YearPattern.test(strYear)) {
		    if ((parseInt(strYear, 10)>=StartYear) && (parseInt(strYear, 10)<= (dtToday.getFullYear()+EndYear))) {
		        Cal.Year = parseInt(strYear, 10);
			}
		}
		//end parse year
		
		//parse Date
		if ((parseInt(strDate, 10) <= Cal.GetMonDays()) && (parseInt(strDate, 10) >= 1)) {
			Cal.Date = strDate;
		}
		//end parse Date

		//parse time

		if (Cal.ShowTime === true)
		{
			//parse AM or PM
			if (TimeMode === 12)
			{
				strAMPM = exDateTime.substring(exDateTime.length - 2, exDateTime.length);
				Cal.AMorPM = strAMPM;
			}

			tSp1 = exDateTime.indexOf(":", 0);
			tSp2 = exDateTime.indexOf(":", (parseInt(tSp1, 10) + 1));
			if (tSp1 > 0)
			{
				strHour = exDateTime.substring(tSp1, tSp1 - 2);
				Cal.SetHour(strHour);

				strMinute = exDateTime.substring(tSp1 + 1, tSp1 + 3);
				Cal.SetMinute(strMinute);

				strSecond = exDateTime.substring(tSp2 + 1, tSp2 + 3);
				Cal.SetSecond(strSecond);

			}
			else if (exDateTime.indexOf("D*") !== -1)
			{   //DTG
				strHour = exDateTime.substring(2, 4);
				Cal.SetHour(strHour);
				strMinute = exDateTime.substring(4, 6);
				Cal.SetMinute(strMinute);

			}
		}

	}
	selDate = new Date(Cal.Year, Cal.Month, Cal.Date);//version 1.7
	RenderCssCal(true);
}

function closewin(id) {
    if (Cal.ShowTime === true) {
        var MaxYear = dtToday.getFullYear() + EndYear;
        var beforeToday =
                    (Cal.Date < dtToday.getDate()) &&
                    (Cal.Month === dtToday.getMonth()) &&
                    (Cal.Year === dtToday.getFullYear())
                    ||
                    (Cal.Month < dtToday.getMonth()) &&
                    (Cal.Year === dtToday.getFullYear())
                    ||
                    (Cal.Year < dtToday.getFullYear());

        if ((Cal.Year <= MaxYear) && (Cal.Year >= StartYear) && (Cal.Month === selDate.getMonth()) && (Cal.Year === selDate.getFullYear())) {
            if (Cal.EnableDateMode === "future") {
                if (beforeToday === false) {
                    callback(id, Cal.FormatDate(Cal.Date));
                }
            }
            else {
                callback(id, Cal.FormatDate(Cal.Date));
			}
        }
    }
    
	var CalId = document.getElementById(id);
	CalId.focus();
	winCal.style.visibility = 'hidden';
}

function highlightDate(element, col)
{
	if (col === 0)
	{
		element.className += ' calDayClassOver';
	}
	else
	{		
		element.className = element.className.replace(' calDayClassOver', '');
	}
}

function highlightControl(element, col)
{
	if (col === 0)
	{
		element.className += 'Over';
	}
	else
	{		
		element.className = element.className.replace('Over', '');
	}
}

function changeBorder(element, col, oldBgColor)
{
	if (col === 0)
	{
		element.style.background = HoverColor;
		element.style.borderColor = "black";
		element.style.cursor = "pointer";
	}

	else
	{
		if (oldBgColor)
		{
			element.style.background = oldBgColor;
		}
		else
		{
			element.style.background = "white";
		}
		element.style.borderColor = "white";
		element.style.cursor = "auto";
	}
}

function selectDate(element, date) {
    Cal.Date = date;
    selDate = new Date(Cal.Year, Cal.Month, Cal.Date);
    element.style.background = SelDateColor;
    RenderCssCal();
}

function pickIt(evt)
{
	var objectID,
	dom,
	de,
	b;
	// accesses the element that generates the event and retrieves its ID
	if (document.addEventListener)
	{ // w3c
		objectID = evt.target.id;
		if (objectID.indexOf(calSpanID) !== -1)
		{
			dom = document.getElementById(objectID);
			cnLeft = evt.pageX;
			cnTop = evt.pageY;

			if (dom.offsetLeft)
			{
				cnLeft = (cnLeft - dom.offsetLeft);
				cnTop = (cnTop - dom.offsetTop);
			}
		}

		// get mouse position on click
		xpos = (evt.pageX);
		ypos = (evt.pageY);
	}

	else
	{ // IE
		objectID = event.srcElement.id;
		cnLeft = event.offsetX;
		cnTop = (event.offsetY);

		// get mouse position on click
		de = document.documentElement;
		b = document.body;

		xpos = event.clientX + (de.scrollLeft || b.scrollLeft) - (de.clientLeft || 0);
		ypos = event.clientY + (de.scrollTop || b.scrollTop) - (de.clientTop || 0);
	}

	// verify if this is a valid element to pick
	if (objectID.indexOf(calSpanID) !== -1)
	{
		domStyle = document.getElementById(objectID).style;
	}

	if (domStyle)
	{
		domStyle.zIndex = 100;
		return false;
	}

	else
	{
		domStyle = null;
		return;
	}
}



function dragIt(evt)
{
	if (domStyle)
	{
		if (document.addEventListener)
		{ //for IE
			domStyle.left = (event.clientX - cnLeft + document.body.scrollLeft) + 'px';
			domStyle.top = (event.clientY - cnTop + document.body.scrollTop) + 'px';
		}
		else
		{  //Firefox
			domStyle.left = (evt.clientX - cnLeft + document.body.scrollLeft) + 'px';
			domStyle.top = (evt.clientY - cnTop + document.body.scrollTop) + 'px';
		}
	}
}

// performs a single increment or decrement
function nextStep(whatSpinner, direction)
{
	if (whatSpinner === "Hour")
	{
		if (direction === "plus")
		{
			Cal.SetHour(Cal.Hours + 1);
			RenderCssCal();
		}
		else if (direction === "minus")
		{
			Cal.SetHour(Cal.Hours - 1);
			RenderCssCal();
		}
	}
	else if (whatSpinner === "Minute")
	{
		if (direction === "plus")
		{
			Cal.SetMinute(parseInt(Cal.Minutes, 10) + 1);
			RenderCssCal();
		}
		else if (direction === "minus")
		{
			Cal.SetMinute(parseInt(Cal.Minutes, 10) - 1);
			RenderCssCal();
		}
	}

}

// starts the time spinner
function startSpin(whatSpinner, direction)
{
	document.thisLoop = setInterval(function ()
	{
		nextStep(whatSpinner, direction);
	}, 125); //125 ms
}

//stops the time spinner
function stopSpin()
{
	clearInterval(document.thisLoop);
}

function dropIt()
{
	stopSpin();

	if (domStyle)
	{
		domStyle = null;
	}
}

// Default events configuration

document.onmousedown = pickIt;
document.onmousemove = dragIt;
document.onmouseup = dropIt;
