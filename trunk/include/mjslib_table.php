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

		var $tableContents = array();
		var $rowAttr = array();
		var $tableTags;
		var $divClass;
		var $colId;
		var $rowsPerPage;
		var $columnHeadersId;
		var $HeadersPosn;
		
		var $colWidth = array();
		var $colAlign = array();
		var $bulkActions;
		var $hideEmptyRows;
		var $spanEmptyCells;
		var $useTHTags;
		var $noAutoComplete;
		
		var $currRow;
		var $currCol;
		var $maxCol;
		var $rowActive = array();
		var $currentPage;
		var $totalRows;
		var $firstRowShown;
		var $maxRowsShown;
		
		var $rowCount = 0;
		
		var $tableType;
		
		function __construct($newTableType = 'html') //constructor
		{
			$this->tableType = $newTableType;
			switch ($this->tableType)
			{
				case 'html':
				case 'RTF':
				case 'text':
				default:
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
		}
		
		function SetRowsPerPage($rowsPerPage)
		{
			$this->rowsPerPage = $rowsPerPage;
			
			$this->currentPage = MJSLibUtilsClass::GetArrayElement($_REQUEST, 'paged', 1);
			$this->currentPage = MJSLibUtilsClass::GetArrayElement($_GET, 'paged', $this->currentPage);
			
			$this->firstRowShown = 1 + (($this->currentPage - 1) * $this->rowsPerPage);
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

		function SetListHeaders($headerId, $columns, $headerPosn = MJSLibTableClass::HEADERPOSN_BOTH)
		{
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
				
			$this->columnHeadersId = $headerId;
			$this->HeadersPosn = $headerPosn;
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

			$content  = "name=$inputName ";
			$content .= "id=$inputName ";
			$content .= "maxlength=\"$maxlength\" ";
			$content .= "size=\"$maxlength+1\" ";
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
				$selected = ($option == $value) ? ' selected=""' : '';
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

		function AddToTable($result, $content, $col=0, $newRow = false)
		{
			if (!isset($content) || (strlen($content) == 0)) return;
			
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
					echo "<table $this->tableTags>\n";
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
			$output .= "<option value='-1' selected='selected'>Bulk Actions&nbsp&nbsp</option>\n"; 
			foreach ($this->bulkActions as $action => $actionID)
				$output .= "<option value='$action'>$actionID</option>\n"; 
			$output .= "</select>\n"; 
			$output .= "<input type='submit' name='' id='doaction' class='button-secondary action' value='Apply'  />\n"; 
			$output .= "</div>\n"; 
			
			return $output;
		}
		
		function OutputCheckboxScript()
		{
			echo "
<script>

function getParentNode(obj, nodeName)
{
	var pobj = obj;
	while (pobj != null)
	{
		pobj = pobj.parentNode;
		if (pobj == null)
			break;
		pName = pobj.nodeName;
		if (pName == nodeName)
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
			$rowTag = $this->useTHTags ? 'th' : 'td';
			
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
					
					$colSpan = '';
					$colSpanCount = 1;
					if ($this->spanEmptyCells)
					{
						for ($nextCol = $col+1; $nextCol <= $this->maxCol; $nextCol++, $colSpanCount++)
						{
							if (isset($this->tableContents[$row][$nextCol])) break;
						}
						if ($colSpanCount > 1) 
						{
							$colSpan = ' colspan="'.$colSpanCount.'"';
						}							
					}
					switch ($this->tableType)
					{
						case 'html':
							echo '<'.$rowTag.$colSpan.$setWidth.$setAlign.$setId.'>';
							break;
						case 'RTF':
							if ($col > 1) echo '\tab ';
						case 'text':
						default:
							break;
					}
					echo isset($this->tableContents[$row][$col]) ? $this->tableContents[$row][$col] : "&nbsp;";
					switch ($this->tableType)
					{
						case 'html':
							echo "</$rowTag>\n";
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
		var $caller;
		var $myPluginObj;
		var $myDBaseObj;
		var $pluginName;
		var $rowNo;
		var $rowCount;
		var $filterRowCounts;
		var $defaultFilterIndex;
		var $showDBIds;
		var $currResult;
		
		function __construct($env, $newTableType = 'html') //constructor
		{
			// Call base constructor
			parent::__construct($newTableType);
			
			if (is_array($env))
			{
				$this->caller = $env['caller'];
				$this->myPluginObj = $env['PluginObj'];
				$this->myDBaseObj = $env['DBaseObj'];
			}
			else
				$this->caller = $env;
				
			$callerFolders = explode("/", plugin_basename($this->caller));
			$this->pluginName = $callerFolders[0];

			$this->tableTags = 'class="widefat" cellspacing="0"';
			$this->SetRowsPerPage(MJSLIB_EVENTS_PER_PAGE);
			$this->useTHTags = true;
			$this->showDBIds = false;
			
			$this->defaultFilterIndex = 0;			
		}
		
		function NewRow($result, $rowAttr = '')
		{
			MJSLibTableClass::NewRow($result, $rowAttr);
			
			$col=1;
			
			if ($this->showDBIds)
				$this->AddToTable($result, $this->GetRecordID($result), $col++);

			if (isset($this->bulkActions))
			{
				//echo "Adding Checkbox - Col = $col<br>";				
				$recordID = $this->GetRecordID($result);	
				$this->AddCheckBoxToTable($result, 'rowSelect[]', $col++, $recordID);
			}
		}
		
		function GetRecordID($result)
		{
			echo "function GetRecordID() must be defined in MJSLibAdminListClass derived class<br>\n";
			die;
		}
		
		function IsRowInView($result, $rowFilter)
		{
			return true;
		}
		
		function ShowRow($result, $rowFilter)
		{
			$rtnVal = true;
			
			if ($this->rowNo < $this->firstRowShown) 
			{				
				$rtnVal = false;
			}	
			else if (($this->rowCount >= $this->rowsPerPage) && ($this->rowsPerPage > 0))
			{
				$rtnVal = false;	// TODO - Return tri-state output? ... this should terminate the loop
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
		
		function OutputList($results)
		{
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
			foreach($results as $result)
			{
				if (!$this->IsRowInView($result, $rowFilter))
					continue;
				
				$this->rowNo++;
				
				if (!$this->ShowRow($result, $rowFilter))
					continue;
					
				$this->AddResult($result);
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
		
		function AddResult($result)
		{
		}
		
	}
}

?>