<?php
/* 
Description: Code for Table Management Class
 
Copyright 2012 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

include 'mjslib_admin.php';
 
if (!class_exists('MJSLibTableClass')) 
{
	if (!defined('MJSLIB_EVENTS_PER_PAGE'))
		define('MJSLIB_EVENTS_PER_PAGE', 20);
	
	class MJSLibTableClass // Define class
	{
		const HEADERPOSN_TOP = 1;
		const HEADERPOSN_BOTTOM = 2;
		const HEADERPOSN_BOTH = 3;

		const TABLEENTRY_ARRAY = 'array';
		const TABLEENTRY_BUTTON = 'button';
		const TABLEENTRY_CHECKBOX = 'checkbox';
		const TABLEENTRY_FUNCTION = 'function';
		const TABLEENTRY_SELECT = 'select';
		const TABLEENTRY_TEXT = 'text';
		const TABLEENTRY_TEXTBOX = 'textbox';
		const TABLEENTRY_VIEW = 'view';
		const	TABLEENTRY_VALUE = 'value';
		const	TABLEENTRY_COOKIE = 'cookie';
		
		var $tableContents = array();
		var $rowAttr = array();
		var $tableName = '';
		var $tableTags;
		var $divClass;
		var $colId;
		var $rowsPerPage;
		var $columnHeadersId = '';
		var $HeadersPosn;
		
		var $colWidth = array();
		var $colAlign = array();
		var $colClass = array();
		var $columns;
		var $bulkActions;
		var $hideEmptyRows;
		var $spanEmptyCells;
		var $useTHTags;
		var $noAutoComplete;
		var $ignoreEmptyCells;
		
		var $detailsRowsDef;
		var $moreText;
		var $lessText;
		var $hiddenRowsButtonId;
		var $hiddenRowStyle = 'style="display: none;"';
		
		var $currRow;
		var $currCol;
		var $maxCol;
		var $rowActive = array();
		var $currentPage;
		var $totalRows;
		var $firstRowShown;
		var $maxRowsShown;
		
		var $rowCount = 0;
		
		var $scriptsOutput;
		var $moreScriptsOutput;
		
		var $tableType;
		
		function __construct($newTableType = 'html') //constructor
		{
			$this->tableType = $newTableType;
			switch ($this->tableType)
			{
				case 'html':
				case 'RTF':
				case 'text':
					break;
					
				default:
					MJSLibUtilsClass::ShowCallStack();
					echo "<strong><br>Invalid table type ($newTableType) ".get_class($this)." class<br></strong>\n";
					die;
					break;
			}
			
			$this->currRow = 1;
			$this->currCol = 0;
			$this->maxCol = 0;
			$this->rowActive[$this->currRow] = false;
			$this->hideEmptyRows = true;
			$this->spanEmptyCells = false;
			$this->divClass = '';
			$this->colId = '';
			$this->divClass = '';
			$this->tableTags = '';
			$this->colId = '';
			$this->totalRows = 0;
			$this->rowsPerPage = 0;
			$this->useTHTags = false;
			$this->noAutoComplete = true;
			$this->ignoreEmptyCells = true;
			$this->scriptsOutput = false;
			$this->moreScriptsOutput = false;
			
			$this->detailsRowsDef = array_merge($this->GetDetailsRowsDefinition(), $this->GetDetailsRowsFooter());
				
			$this->moreText = __('Show');
			$this->lessText = __('Hide');
			
		}
		
		function SetRowsPerPage($rowsPerPage)
		{
			$this->rowsPerPage = $rowsPerPage;
			
			$this->currentPage = MJSLibUtilsClass::GetArrayElement($_REQUEST, 'paged', 1);
			$this->currentPage = MJSLibUtilsClass::GetArrayElement($_GET, 'paged', $this->currentPage);
			
			$this->firstRowShown = 1 + (($this->currentPage - 1) * $this->rowsPerPage);
		}

		function AddHiddenRows($result, $hiddenRowsID, $hiddenRows)
		{
			$this->NewRow($result, 'id="'.$hiddenRowsID.'" '.$this->hiddenRowStyle.' class="hiddenRow"');
			$this->AddToTable($result, $hiddenRows);	
		}

		function GetMainRowsDefinition()
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetMainRowsDefinition');
		}
		
		function GetDetailsRowsDefinition()
		{
			return array();
		}
		
		function GetDetailsRowsFooter()
		{
			return array();
		}
		
		function HasHiddenRows()
		{
			// No extended settings
			return (count($this->detailsRowsDef) > 0);
		}
		
		function ExtendedSettingsDBOpts()
		{
			return array();
		}
		
		function NewRow($result, $rowAttr = '')
		{
			// Increment Row ... but only if the current row has data
			if ($this->rowActive[$this->currRow]) 
				$this->currRow++;
				
			$this->currCol = 0;
			$this->rowActive[$this->currRow] = false;
			$this->rowAttr[$this->currRow] = $rowAttr;
		}

		function SetColWidths($newColWidths)
		{
			$this->colWidth = split(',', ','.$newColWidths);
		}

		function SetColAlign($newColAlign)
		{			
			$this->colAlign = split(',', ','.$newColAlign);
		}

		function SetColClass($newColClass)
		{			
			$this->colClass = split(',', ','.$newColClass);
		}

		function SetListHeaders($headerId, $columns = null, $headerPosn = MJSLibTableClass::HEADERPOSN_BOTH)
		{
			// Save the settings, the headers are actually set by the EnableListHeaders function			
			$this->columnHeadersId = $headerId;

			if ($columns != null)
				$this->columns = $columns;	// Save for possible next call
				
			$this->HeadersPosn = $headerPosn;
		}

		function EnableListHeaders()
		{
			if ($this->columnHeadersId === '') return;
			if ($this->columns === null) return;
			
			$columns = $this->columns;	// Use columns from last call
				
			if ($this->showDBIds)
			{
				// Add the ID column
				$columns = array_merge(array('eventID' => 'ID'), $columns); 
			}
				
			if (isset($this->bulkActions))
			{
				// Add the Checkbox column
				$columns = array_merge(array('eventCb' => '<input name="checkall" id="checkall" type="checkbox"  onClick="updateCheckboxes(this)" />'), $columns); 
			}
			
			if ($this->HasHiddenRows() && ($this->hiddenRowsButtonId !== ''))
			{
				$columns = array_merge($columns, array('eventOptions' => $this->hiddenRowsButtonId)); 
			}
				
			register_column_headers($this->columnHeadersId, $columns);	
		}
		
		function AddCheckBoxToTable($result, $inputName, $col=0, $value='checked', $checked=false, $label='', $newRow = false)
		{
			$checkedTag = $checked ? ' checked="yes"' : '';
			
			$content = "$label<input name=\"$inputName\" id=\"$inputName\" type=\"checkbox\" value=\"$value\" $checkedTag/>";
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddInputToTable($result, $inputName, $maxlength, $value, $col=0, $newRow = false)
		{
			$inputName .= $this->GetRecordID($result);

			$size = $maxlength+1;
			
			$content  = "name=$inputName ";
			$content .= "id=$inputName ";
			$content .= "maxlength=\"$maxlength\" ";
			$content .= "size=\"$size\" ";
			$content .= "value=\"$value\" ";
			
			if ($this->noAutoComplete)
				$content .= "autocomplete=\"off\" "; 
			
			$content = "<input type=text $content />";
			
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddSelectToTable($result, $inputName, $options, $value='', $col=0, $newRow = false)
		{
			$inputName .= $this->GetRecordID($result);
			
			$content = "<select name=$inputName>"."\n";
			foreach ($options as $index => $option)
			{
				$selected = ($index == $value) ? ' selected=""' : '';
				$content .= '<option value="'.$index.'"'.$selected.'>'.$option.'&nbsp;&nbsp;</option>'."\n";
			}
			$content .= "</select>"."\n";

			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddLinkToTable($result, $content, $link, $col=0, $newRow = false)
		{
			$content = '<a href="'.$link.'">'.$content.'</a>';
			$this->AddToTable($result, $content, $col, $newRow);
		}
	
		function AddShowOrHideButtonToTable($result, $tableId, $rowId, $content, $col=0, $newRow = false)
		{
			$this->OutputMoreButtonScript();
			
			$recordID = $this->GetRecordID($result);
			$moreName = 'more'.$recordID;
			
			$content = '<a id="'.$moreName.'" class="button-secondary Xmore-button" onClick="HideOrShowRows(\''.$moreName.'\', \''.$rowId.'\')">'.$content.'</a>';
			$this->AddToTable($result, $content, $col, $newRow);
		}

		function AddToTable($result, $content, $col=0, $newRow = false)
		{
			if ($this->ignoreEmptyCells)
			{
			if (!isset($content) || (strlen($content) == 0)) return;
			}
			
			// Increment Row ... but only if the current row has data
			if ($newRow) 
				$this->NewRow($result);
			
			if ($col <= 0) 
				$col = ++$this->currCol;
			else
				$this->currCol = $col;
				
			$this->tableContents[$this->currRow][$col] = $content;
			$this->rowActive[$this->currRow] = true;
			$this->maxCol = max($col, $this->maxCol);
		}
		
		function ColumnHeaders($atTop = true)
		{
			if (!isset($this->columnHeadersId)) 
				return;

			if ($this->columnHeadersId === '') 
				return;

			if ( !function_exists( 'print_column_headers' ) )
				return;
			
			if ($atTop)
			{
				if ($this->HeadersPosn === MJSLibTableClass::HEADERPOSN_BOTTOM) 
					return;
					
				echo "<thead>\n";
				echo "<tr>\n";
				print_column_headers($this->columnHeadersId);
				echo "</tr>\n";
				echo "</thead>\n";
			}
			else
			{
				if ($this->HeadersPosn === MJSLibTableClass::HEADERPOSN_TOP) 
					return;
					
				echo "<tfoot>\n";
				echo "<tr>\n";
				print_column_headers($this->columnHeadersId, false);
				echo "</tr>\n";
				echo "</tfoot>\n";
				echo "<tbody>\n";
			}
		}
		
		function Header()
		{
			switch ($this->tableType)
			{
				case 'html':
					if ($this->divClass)
						echo "<div class=$this->divClass>\n";
						
					echo "<table ";
					if ($this->tableName !== '')
						echo 'id="'.$this->tableName.'" ';
					echo "$this->tableTags>\n";
					
					echo "<tbody>\n";
					break;
				case 'RTF':
				case 'text':
				default:
					break;
			}
			$this->ColumnHeaders();
			$this->ColumnHeaders(false);
		}
		
		function Footer()
		{
			switch ($this->tableType)
			{
				case 'html':
					echo "</tbody></table>\n";		
					if ($this->divClass)
						echo "</div>\n";		
					break;
				case 'RTF':
				case 'text':
				default:
					break;
			}
		}

		function ShowBulkActions( $which = 'top' ) 
		{	
			if (!isset($this->bulkActions)) return '';
			
			$this->OutputCheckboxScript();
			
			$tagNo = $which === 'top' ? '' : '2';
			
			$output  = "<div class='alignleft actions'>\n";
			$output .= "<select name='action$tagNo'>\n"; 
			$output .= "<option value='-1' selected='selected'>Bulk Actions&nbsp;&nbsp;</option>\n"; 
			foreach ($this->bulkActions as $action => $actionID)
				$output .= "<option value='$action'>$actionID</option>\n"; 
			$output .= "</select>\n"; 
			$output .= "<input type='submit' name='' id='doaction' class='button-secondary action' value='Apply'  />\n"; 
			$output .= "</div>\n"; 
			
			return $output;
		}
		
		function OutputMoreButtonScript()
		{
			if ($this->moreScriptsOutput) return;
			$this->moreScriptsOutput = true;
			
			$moreText = $this->moreText;
			$lessText = $this->lessText;
			
			echo "
<script>

function HideOrShowRows(buttonId, rowId)
{
	var rowObj = document.getElementById(rowId);
	var buttonObj = document.getElementById(buttonId);

	// Toggle display state
	if (rowObj.style.display === '')
	{
		rowObj.style.display = 'none';	
		buttonObj.innerHTML = '$moreText';
		rowsVisible = false;
	}
	else
	{
		rowObj.style.display = '';
		buttonObj.innerHTML = '$lessText';
		rowsVisible = true;	
	}
	
}

</script>
			";
		}
		
		function OutputCheckboxScript()
		{
			if ($this->scriptsOutput) return;
			$this->scriptsOutput = true;
			
			echo "
<script>

function getParentNode(obj, nodeName)
{
	var pobj = obj;
	while (pobj !== null)
	{
		pobj = pobj.parentNode;
		if (pobj === null)
			break;
		pName = pobj.nodeName;
		if (pName === nodeName)
			break;
	}
	
	return pobj;
}

function updateCheckboxes(obj)
{
	var boxid = 'rowSelect[]';
	
	var elem = getParentNode(obj, 'FORM');
	elem = elem.elements;
	
	var newState = obj.checked;				
	for(var i = 0; i < elem.length; i++)
	{
		if (elem[i].name == boxid) 
			elem[i].checked = newState;
		
		if (elem[i].name == obj.name)
			elem[i].checked = newState;
	} 
		
	//var eventtype = event.type;
}

</script>
			";
		}
		
		function ShowPageNavigation( $which = 'top' ) 
		{			
			if ($this->rowsPerPage <= 0) 
				return;
			
			// $which is 'top' or 'bottom'
			$output = '';
			
			if ( $this->totalRows <= $this->rowsPerPage ) 
				return;
				
			$totalPages = (int)(($this->totalRows-1)/$this->rowsPerPage) + 1;
			
			$output .= '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $this->totalRows ), number_format_i18n( $this->totalRows ) ) . '</span>';

			$current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

			$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

			$page_links = array();

			$disable_first = $disable_last = '';
			if ( $this->currentPage == 1 )
				$disable_first = ' disabled';
			if ( $this->currentPage == $totalPages )
				$disable_last = ' disabled';

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'first-page' . $disable_first,
				$disable_first === '' ? esc_attr__('Go to the first page') : '',
				$disable_first === '' ? 'href='.esc_url( remove_query_arg( 'paged', $current_url ) ) : '',
				'&laquo;'
			);

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'prev-page' . $disable_first,
				$disable_first === '' ? esc_attr__('Go to the previous page') : '',
				$disable_first === '' ? 'href='.esc_url( add_query_arg( 'paged', max( 1, $this->currentPage-1 ), $current_url ) ) : '',
				'&lsaquo;'
			);

			if ( 'bottom' == $which )
				$html_current_page = $this->currentPage;
			else
				$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
					esc_attr__( 'Current page' ),
					esc_attr( 'paged' ),
					$this->currentPage,
					strlen( $totalPages )
				);

			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $totalPages ) );
			$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'next-page' . $disable_last,
				$disable_last === '' ? esc_attr__('Go to the next page') : '',
				$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', min( $totalPages, $this->currentPage+1 ), $current_url ) ) : '',
				'&rsaquo;'
			);

			$page_links[] = sprintf( "<a class='%s' title='%s' %s>%s</a>",
				'last-page' . $disable_last,
				$disable_last === '' ? esc_attr__('Go to the last page') : '',
				$disable_last === '' ? 'href='.esc_url( add_query_arg( 'paged', $totalPages, $current_url ) ) : '',
				'&raquo;'
			);

			$output .= "\n" . join( "\n", $page_links );

			$page_class = $totalPages < 2 ? ' one-page' : '';

			return "<div class='tablenav-pages{$page_class}'>$output</div>";
		}

		function ShowPageControls($which = 'top')
		{
			switch ($this->tableType)
			{
				case 'html':
					break;
				case 'RTF':
				case 'text':
				default:
					return;
			}
						
			$pageControls  = $this->ShowBulkActions($which);
			$pageControls .= $this->ShowPageNavigation($which);
			if ($pageControls != '') 
			{
				echo "<!-- ShowPageControls - START -->\n";
				echo "<div class='tablenav $which actions'>\n$pageControls</div>\n";
				echo "<!-- ShowPageControls - END -->\n";
			}
		}
		
		function Display()
		{
			$colTag = $this->useTHTags ? 'th' : 'td';
			
			$this->ShowPageControls();
			$this->Header();

			for ($row = 1; $row <= $this->currRow; $row++)
			{
				if ($this->hideEmptyRows && !$this->rowActive[$row]) continue;
				switch ($this->tableType)
				{
					case 'html':
						if (isset($this->rowAttr[$row]) && ($this->rowAttr[$row] != ''))
							echo "<tr ".$this->rowAttr[$row].">\n";
						else
							echo "<tr>\n";
						break;
					case 'RTF':
						break;
					case 'text':
					default:
						break;
				}
								
				for ($col = 1; $col <= $this->maxCol; $col++)
				{
					$setWidth = '';
					$setAlign = '';
					$setId = '';
					
					if ($row == 1)
					{
						$setWidth = isset($this->colWidth[$col]) ? ' width="'.$this->colWidth[$col].'"' : '';
						$setAlign = isset($this->colAlign[$col]) ? ' align="'.$this->colAlign[$col].'"' : '';
						$setId = ($this->colId !== '') ? ' id="'.$this->colId.$col.'"' : '';
					}
					
					$setClass = (isset($this->colClass[$col]) && $this->colClass[$col] != '') ? ' class="'.$this->colClass[$col].'"' : '';
					
					$colSpan = '';
					$colSpanCount = 1;
					if ($this->spanEmptyCells)
					{
						for ($nextCol = $col+1; $nextCol <= $this->maxCol; $nextCol++, $colSpanCount++)
						{
							if (isset($this->tableContents[$row][$nextCol])) break;
						}
						if ($colSpanCount > 1) 
							$colSpan = ' colspan="'.$colSpanCount.'"';
						}							
					switch ($this->tableType)
					{
						case 'html':
							echo '<'.$colTag.$colSpan.$setWidth.$setAlign.$setId.$setClass.'>';
							break;
						case 'RTF':
							if ($col > 1) echo '\tab ';
						case 'text':
						default:
							break;
					}
					$tableContents = isset($this->tableContents[$row][$col]) ? $this->tableContents[$row][$col] : "";
					$tableContents = trim($tableContents);
					echo (strlen($tableContents) > 0) ? $tableContents : "&nbsp;";
					
					switch ($this->tableType)
					{
						case 'html':
							echo "</$colTag>\n";
							break;
						case 'RTF':
							break;
						case 'text':
						default:
							echo "\t";
							break;
					}
					
					// Skp "Spanned" cells
					$col += ($colSpanCount - 1);
				}			
					
				switch ($this->tableType)
				{
					case 'html':
						echo "</tr>\n";
						break;
					case 'RTF':
						echo '\par '."\n";
						break;
					case 'text':
					default:
						echo "\n";
						break;
				}
			}
			
			$this->Footer();
			$this->ShowPageControls('bottom');
		}
	}
}

if (!class_exists('MJSLibAdminListClass')) 
{
	class MJSLibAdminListClass extends MJSLibTableClass // Define class
	{		
		const VIEWMODE = false;
		const EDITMODE = true;
		
		var $env;
		var $caller;
		var $results;
		var $myPluginObj;
		var $myDBaseObj;
		var $pluginName;
		var $rowNo;
		var $rowCount;
		var $filterRowCounts;
		var $defaultFilterIndex;
		var $showDBIds;
		var $lastCBId;
		var $currResult;
		var $enableFilter;
		
		var $editMode;
		
		var $updateFailed;
		
		function __construct($env, $editMode /* = false */, $newTableType = 'html') //constructor
		{
			$this->editMode = $editMode;
			
			// Call base constructor
			parent::__construct($newTableType);
			
			$this->ignoreEmptyCells = false;
			
			$this->env = $env;
			
			if (is_array($env))
			{
				$this->caller = $env['caller'];
				$this->myPluginObj = $env['PluginObj'];
				$this->myDBaseObj = $env['DBaseObj'];
			}
			else
				$this->caller = $env;
				
			$this->enableFilter = true;
			
			$callerFolders = explode("/", plugin_basename($this->caller));
			$this->pluginName = $callerFolders[0];

			$this->tableTags = 'class="widefat" cellspacing="0"';
			
			if (isset($this->myDBaseObj->adminOptions['PageLength']))
				$this->SetRowsPerPage($this->myDBaseObj->adminOptions['PageLength']);
			else
				$this->SetRowsPerPage(MJSLIB_EVENTS_PER_PAGE);
				
			$this->useTHTags = true;
			$this->showDBIds = $this->myDBaseObj->getOption('Dev_ShowDBIds');					
			$this->lastCBId = '';
			
			$this->defaultFilterIndex = 0;	
			$this->updateFailed = false;
			
			$this->columnDefs = $this->GetMainRowsDefinition();			
			
			if (!isset($this->HeadersPosn)) $this->HeadersPosn = MJSLibTableClass::HEADERPOSN_BOTH;
			if (!isset($this->hiddenRowsButtonId)) 
			{
				if (!$this->editMode)
					$this->hiddenRowsButtonId = 'Details';
				else
				{
					$this->hiddenRowStyle = '';
					$this->hiddenRowsButtonId = '';
					$this->moreText = '';
				}
			}
		}
		
		function NewRow($result, $rowAttr = '')
		{
			MJSLibTableClass::NewRow($result, $rowAttr);
			
			$col=1;
			
			$recordID = $this->GetRecordID($result);
			$isFirstLine = ($this->lastCBId !== $recordID);
			$this->lastCBId = $recordID;
			
			if ($this->showDBIds)
			{
				if ($isFirstLine)
					$this->AddToTable($result, $recordID, $col++);
				else	
					$this->AddToTable($result, ' ', $col++);
			}
			
			if (isset($this->bulkActions))
			{
				//echo "Adding Checkbox - Col = $col<br>";				
				if ($isFirstLine)
					$this->AddCheckBoxToTable($result, 'rowSelect[]', $col++, $recordID);
				else	
					$this->AddToTable($result, ' ', $col++);
			}
		}
		
		function GetTableID($result)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetTableID');
		}
		
		function GetRecordID($result)
		{
			MJSLibUtilsClass::UndefinedFuncCallError($this, 'GetRecordID');
		}
		
		function GetRowClass($result)
		{
			return '';
		}
		
		function IsRowInView($result, $rowFilter)
		{
			return true;
		}
		
		function ShowRow($result, $rowFilter)
		{
			$rtnVal = true;

			if (!$this->enableFilter) 
				return $rtnVal;
			
			if ($this->rowNo < $this->firstRowShown) 
			{				
				$rtnVal = false;
			}	
			else if (($this->rowCount >= $this->rowsPerPage) && ($this->rowsPerPage > 0))
			{
				$rtnVal = false;
			}
				
			return $rtnVal;
		}

		function OutputFilterLinks($results, $rowFilter = '')
		{
			$current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$current_url = remove_query_arg( 'filter', $current_url);
			$current_url = remove_query_arg( 'paged', $current_url);
				
			// Loop through all entries to get row counts for each filter
			$filter_links = '';
			foreach ($this->filterRowCounts as $filterId => $filterCount)
			{
				$rowCount = 0;
					
				foreach($results as $result)
				{
					if ($this->IsRowInView($result, $filterId))
						$rowCount++;
				}						
				$this->filterRowCounts[$filterId] = $rowCount;
								
				if ($filter_links != '')
					$filter_links .= ', ';

				$filterClass = strtolower($filterId);
				
				$filterClass = ($rowFilter == $filterId) ? 'selected' : strtolower($filterId);
				$filterURL = esc_url( add_query_arg( 'filter', $filterId, $current_url ) );
						
				$filter_links .= sprintf( "<a class='%s' title='%s' %s>%s</a>",
					$filterClass,
					$rowCount > 0 ? esc_attr__('Show all Events') : '',
					$rowCount > 0 ? 'href='.$filterURL : '',
					"$filterId ($rowCount)"
				);
			}
				
			echo "<div class=filter-links>\n";
			echo $filter_links;					
			echo "</div>\n";
		}
		
		function AddResultFromTable($result)
		{		
			$canDisplayTable = true;
			
			// Check if this row CAN be output using data from the columnDefs table
			foreach ($this->columnDefs as $key => $columnDef)
			{
				if (!isset($columnDef['Type']))
					return true;
				
				switch ($columnDef['Type'])
				{
					//case MJSLibTableClass::TABLEENTRY_CHECKBOX:
					case MJSLibTableClass::TABLEENTRY_TEXT:
					//case MJSLibTableClass::TABLEENTRY_TEXTBOX:
					case MJSLibTableClass::TABLEENTRY_SELECT:
					case MJSLibTableClass::TABLEENTRY_VALUE:
					case MJSLibTableClass::TABLEENTRY_VIEW:
					case MJSLibTableClass::TABLEENTRY_COOKIE:
						break;
						
					default:
						$canDisplayTable = false;
echo "Can't display this table - Label:".$columnDef['Label']." Id:".$columnDef['Id']." Column Type:".$columnDef['Type']."<br>\n";						
						break 2;
				}
			}
			
			if ($canDisplayTable)
			{
				$rowClass = $this->GetRowClass($result);
				$rowAttr = ($rowClass != '') ? 'class="'.$rowClass.'"' : '';
				$this->NewRow($result, $rowAttr);
				
				foreach ($this->columnDefs as $columnDef)
				{
					$columnId = $columnDef['Id'];
					
					if ($this->updateFailed)
					{
						// Error updating values - Get value(s) from form controls
						$recId = $this->GetRecordID($result);
						$currVal = stripslashes($_POST[$columnId.$recId]);
					}
					else
					{
						// Get value(s) from database
						$currVal = $result->$columnId;
					}

					if (isset($columnDef['Decode']))
					{
						$funcName = $columnDef['Decode'];
						$currVal = $this->$funcName($result);
					}
					
					if ($this->editMode)
						$columnType = $columnDef['Type'];
					else
						$columnType = MJSLibTableClass::TABLEENTRY_VIEW;
						
					switch ($columnType)
					{
						//case MJSLibTableClass::TABLEENTRY_CHECKBOX:
						//case MJSLibTableClass::TABLEENTRY_TEXTBOX:
						
						case MJSLibTableClass::TABLEENTRY_SELECT:
							if (isset($columnDef['Items']))
								$options = $columnDef['Items'];
							else
							{
								$functionId = $columnDef['Func'];
								$options = $this->$functionId($result);
							}
							
							$this->AddSelectToTable($result, $columnId, $options, $currVal);
							break;
						
						case MJSLibTableClass::TABLEENTRY_COOKIE:
							$coolieID = $columnDef['Id'];
							if (isset($_COOKIE[$coolieID]))
								$currVal = $_COOKIE[$coolieID];
							else
								$currVal = '';
							// Fall into next case ...
							
						case MJSLibTableClass::TABLEENTRY_TEXT:
							if (!isset($columnDef['Len']))
							{
								echo "No Len entry in Column Definition<br>\n";
								MJSLibUtilsClass::print_r($columnDef, 'columnDef');
							}
							
							$this->AddInputToTable($result, $columnId, $columnDef['Len'], $currVal);
							break;

						case MJSLibTableClass::TABLEENTRY_VALUE:
						case MJSLibTableClass::TABLEENTRY_VIEW:
							$recId = $this->GetRecordID($result);
							$hiddenTag = '<input type="hidden" name="'.$columnId.$recId.'" value="'.$currVal.'"/>';
							if (isset($columnDef['Link']))
							{
								$currValLink = $columnDef['Link'];
								if (isset($columnDef['LinkTo']))
								{
									$currValLink .= "http://";		// Make link absolute
									$currValLink .= $result->$columnDef['LinkTo'];
									$target = 'target="_blank"';
								}
								else
								{
									$currValLink .= $recId;
									$currValLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($currValLink, plugin_basename($this->caller)) : $currValLink;
									$target = '';
								}
								$currVal = '<a href="'.$currValLink.'" '.$target.'>'.$currVal.'</a>';
							}
							$this->AddToTable($result, $currVal.$hiddenTag);
							break;
							
						default:
							break;
					}
				}
			}
						
			return $canDisplayTable;
		}
		
		function AddOptions($result, $optionDetails = array())
		{
			$hiddenRowsID = 'record'.$this->GetRecordID($result).'options';
			
			if (count($this->detailsRowsDef) > 0)
			{
				if ($this->moreText != '')
					$this->AddShowOrHideButtonToTable($result, $this->tableName, $hiddenRowsID, $this->moreText);
				else
					$this->AddToTable($result, '');
				
				$colClassList = '';
				for ($c=1; $c<$this->maxCol; $c++)
					$colClassList .= ',';
				$colClassList .= 'optionsCol';
				$this->SetColClass($colClassList);
												
				$hiddenRowsColId = $this->GetTableID($result).'-hiddenCol';
				
				$hiddenRows = "<table width=\"100%\">\n";
				foreach ($this->detailsRowsDef as $option)
				{
					switch ($option['Type'])
					{
						case MJSLibTableClass::TABLEENTRY_FUNCTION:
							$functionId = $option['Func'];
							$content = $this->$functionId($result, $optionDetails);
							$hiddenRows .= '<tr>'."\n";
							$colSpan = ' class='.$hiddenRowsColId.'2';
							if (isset($option['Label']))
								$hiddenRows .= '<td class='.$hiddenRowsColId.'1>'.$option['Label']."</td>\n";
							else
								$colSpan = " colspan=2";
								
							$hiddenRows .= '<td'.$colSpan.'>'.$content."</td>\n";
							$hiddenRows .= "</tr>\n";
							break;
							
						case MJSLibTableClass::TABLEENTRY_ARRAY:
							if (isset($option['Label']))
							{
								$hiddenRows .= '<tr>'."\n";
								$hiddenRows .= '<td colspan=2>'.$option['Label']."</td>\n";
								$hiddenRows .= "</tr>\n";
							}
							$arrayId = $option['Id'];
							foreach ($result->$arrayId as $elemId => $elemValue)
							{
								$hiddenRows .= '<tr>'."\n";
								$hiddenRows .= '<td class='.$hiddenRowsColId.'1>'.$elemId."</td>\n";
								$hiddenRows .= '<td class='.$hiddenRowsColId.'2>'.$elemValue."</td>\n";
								$hiddenRows .= "</tr>\n";
							}
							break;
							
						default:
							$optionId = $option['Id'];
							$option['Id'] = $option['Id'].$this->GetRecordID($result);
											
							$hiddenRows .= '<tr>'."\n";
							$hiddenRows .= '<td class='.$hiddenRowsColId.'1>'.$option['Label']."</td>\n";
							$hiddenRows .= '<td class='.$hiddenRowsColId.'2>'.SettingsAdminClass::GetHTMLTag($option, $result->$optionId, $this->editMode)."</td>\n";
							$hiddenRows .= "</tr>\n";
							break;
					}
				}
				$hiddenRows .= "</table>\n";
				
				$this->spanEmptyCells = true;
				$this->AddHiddenRows($result, $hiddenRowsID, $hiddenRows);					
			}			
		}
		
		function GetListDetails($result)
		{
			return array();
		}
		
		function OutputList($results)
		{
			if (count($results) == 0) return;
			
			$tableId = $this->GetTableID($results[0]);
			
			$headerColumns = array();
			foreach ($this->columnDefs as $column)
				$headerColumns = array_merge($headerColumns, array($column['Id'] => $column['Label']));
			$this->SetListHeaders($tableId, $headerColumns, $this->HeadersPosn);
			
			$this->results = $results;
			
			$this->EnableListHeaders();
			
			if (isset($this->filterRowCounts))
			{
				$filterKeys = array_keys($this->filterRowCounts);
				$defaultFilter = $filterKeys[$this->defaultFilterIndex];
				
				// Get the filter requested in the HTTP request 
				$rowFilter = isset($_GET['filter']) ? $_GET['filter'] : $defaultFilter;
				
				// Check that the selected filter is defined ... or use default
				$rowFilter = isset($this->filterRowCounts[$rowFilter]) ? $rowFilter : $defaultFilter;
						
				// Calculate and output filter links
				$this->OutputFilterLinks($results, $rowFilter);
				
				// Get the row count for the selected filter
				$this->totalRows = isset($this->filterRowCounts[$rowFilter]) ? $this->filterRowCounts[$rowFilter] : 0;
			}
			else
			{
				$this->totalRows = count($results);
				$rowFilter = '';
			}
										
			$this->rowNo = 0;
			$this->rowCount = 0;
			
			if (count($results) > 0)
				$this->tableName = $this->GetTableID($results[0]);			
	
			foreach($results as $result)
			{
				if (!$this->IsRowInView($result, $rowFilter))
					continue;
				
				$this->rowNo++;
				
				if (!$this->ShowRow($result, $rowFilter))
					continue;
				
				if (!$this->AddResultFromTable($result))
				{
					if (!isset($this->usedAddResult))
					{
						$this->usedAddResult = true;
						echo "<br>Error returned by AddResultFromTable function in ".get_class($this)." class<br>\n";
						MJSLibUtilsClass::ShowCallStack();
					}
				}
				$resultDetails = $this->GetListDetails($result);
				$this->AddOptions($result, $resultDetails);
				$this->rowCount++;
			}
			
			$this->Display();
		}
		
	}
}
		 

if (!class_exists('Template_For_ClassDerivedFrom_MJSLibAdminListClass')) 
{
	class Template_For_ClassDerivedFrom_MJSLibAdminListClass extends MJSLibAdminListClass // Define class
	{		
		function __construct($env) //constructor
		{
		}
		
		function GetRecordID($result)
		{
		}
		
		function IsRowInView($result, $rowFilter)
		{
		}

		function ShowRow($result, $rowFilter)
		{
		}
		
	}
}

?>