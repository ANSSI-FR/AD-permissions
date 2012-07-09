<?php
/*
Copyright or © or Copr. Pierre Capillon, 2012.

pierre.capillon@ssi.gouv.fr

This software is a computer program whose purpose is to retrieve Active
Directory objects permissions from an ESENT database file.

This software is governed by the CeCILL license under French law and
abiding by the rules of distribution of free software.  You can  use, 
modify and/ or redistribute the software under the terms of the CeCILL
license as circulated by CEA, CNRS and INRIA at the following URL
"http://www.cecill.info". 

As a counterpart to the access to the source code and  rights to copy,
modify and redistribute granted by the license, users are provided only
with a limited warranty  and the software's author,  the holder of the
economic rights,  and the successive licensors  have only  limited
liability. 

In this respect, the user's attention is drawn to the risks associated
with loading,  using,  modifying and/or developing or reproducing the
software by the user in light of its specific status of free software,
that may mean  that it is complicated to manipulate,  and  that  also
therefore means  that it is reserved for developers  and  experienced
professionals having in-depth computer knowledge. Users are therefore
encouraged to load and test the software's suitability as regards their
requirements in conditions enabling the security of their systems and/or 
data to be ensured and,  more generally, to use and operate it in the 
same conditions as regards security. 

The fact that you are presently reading this means that you have had
knowledge of the CeCILL license and that you accept its terms.
*/

class DBBrowser 
{
	
	/* DB connection */
	private $cid;
	
	/* DB context */
	private $logtable;
	public $jointable;
	public $joinmethod;
	public $jointable2;
	public $joinmethod2;	
	
	/* Preferences */
	private	$limit = 100;
	private $start = 0;
	private	$repeatheader = 20;
	
	/* Queries */
	public $sql;
	private $res;
	private $numfields;
	public $numrows;
	public $count;
	public $groupby;
	public $records;
	public $executed;
	public $table_fields = array();
	public $shown_fields = array();
	public $shown_fields_tmp = array();
	public $fields_str;
	public $sort_str;
	
	/* Filters */
	private $filter;
	private $global_filters = array();
	private $global_filters_str;
	private $quick_filter;
	private $quick_value;
	private $guid = array();
	private $guid_iot = array();
	
	function DBBrowser() 
	{
		global $scid;
		global $host;
		global $user;
		global $pass;
		global $base;
		global $logtable;

		// session_start();
		
		/* previous session parameter restoration, if available */
		if(isset($_SESSION[$scid.'_limit']))
			$this->setLimit($_SESSION[$scid.'_limit']);
		if(isset($_SESSION[$scid.'_start']))
			$this->setStart($_SESSION[$scid.'_start']);
		if(isset($_SESSION[$scid.'_repeatheader']))
			$this->setRepeatHeader($_SESSION[$scid.'_repeatheader']);
		
		/* updating user-changed preferences */
		if(isset($_GET['limit']))
			$this->setLimit($_GET['limit']);
		if(isset($_GET['start']))
			$this->setStart($_GET['start']);
		if(isset($_GET['repeatheader']))
			$this->setRepeatHeader($_GET['repeatheader']);
		
		
		if(isset($_GET['hide_garbage']) && $_GET['hide_garbage'] == "true")
			$_SESSION[$scid.'_hide_garbage'] = true;
		if(isset($_GET['hide_garbage']) && $_GET['hide_garbage'] == "false")
			unset($_SESSION[$scid.'_hide_garbage']);

		/* DB connection */
		$this->cid = mysql_connect($host, $user, $pass) or die(mysql_error());
		mysql_select_db($base) or die(mysql_error());
		
		$this->processDbChoice();	// Table selection
		
					
		/* for global stats (record count) */
		/*$sql = "SELECT COUNT(*) FROM " . $logtable;
		$req = mysql_query($sql, $this->cid) or die(mysql_error());
		$res = mysql_fetch_row($req);*/
		$this->records = 0;
		
		
		$this->processGroupBy();
		$this->processSort();
		$this->processTags();
		$this->processJoinTable();
		$this->processTableFields();
		$this->processQuickFilter();
		$this->processGlobalFilter();
		$this->processGUID();
		$this->processGUIDiot();
		
		if(isset($_GET['csvdump']))
			$this->csvDump();
		
		
		$this->query();
	}
	
	function processGroupBy()
	{
		global $scid;
		
		if(isset($_GET['groupby']) && !empty($_GET['groupby'])) {
			if($_GET['groupby'] == "NONE") {
				$this->groupby = "";
				unset($_SESSION[$scid.'_groupby']);
			}
			else {
				$this->groupby = $_GET['groupby'];
				$_SESSION[$scid.'_groupby'] = $this->groupby;
			}
		}
		else if(isset($_SESSION[$scid.'_groupby'])) {
			$this->groupby = $_SESSION[$scid.'_groupby'];
		}
		else
			$this->groupby = "";
	}

	function processSort()
	{
		global $scid;
		$prefix = '';
		if(isset($_GET['orderby']) && !empty($_GET['orderby'])
		&& isset($_GET['order']) && !empty($_GET['order'])) {
			if($_GET['orderby'] == "tag_type")
				$prefix = '';
			$this->sort_str = " ORDER BY ".$prefix.$_GET['orderby']." ".$_GET['order'] . " ";
			$_SESSION[$scid.'_sort_str'] = $this->sort_str;
		}
		else if(isset($_SESSION[$scid.'_sort_str'])) {
			$this->sort_str = $_SESSION[$scid.'_sort_str'];
			
			if(empty($this->groupby))
				if(strstr($this->sort_str, "count_") !== FALSE)
					$this->sort_str = "";
		}
		else
			$this->sort_str = "";
		
		$_SESSION[$scid.'_sort_str'] = $this->sort_str;
	}	
	
	function getSortStr()
	{
		return $this->sort_str;
	}
	
	function processJoinTable()
	{
		global $scid;
		/* Did it change from saved session? */
		if(
		(isset($_GET['jointable']) && !empty($_GET['jointable'])
		&& isset($_GET['joinmethod']) && !empty($_GET['joinmethod']))
		||
		(isset($_GET['jointable2']) && !empty($_GET['jointable2'])
		&& isset($_GET['joinmethod2']) && !empty($_GET['joinmethod2']))
		) {
			if(
			(isset($_GET['jointable']) && !empty($_GET['jointable'])
			&& isset($_GET['joinmethod']) && !empty($_GET['joinmethod']))
			) {
				if($_GET['jointable'] == "NULL") {
					$this->jointable = "";
					$this->joinmethod = "";
				}
				else {
					$this->jointable = $_GET['jointable'];
					$this->joinmethod = $_GET['joinmethod'];
				}
			}
			else if (isset($_SESSION[$scid.'_jointable']) && isset($_SESSION[$scid.'_joinmethod'])) {
				$this->jointable = $_SESSION[$scid.'_jointable'];
				$this->joinmethod = $_SESSION[$scid.'_joinmethod'];
			}

			if(
			(isset($_GET['jointable2']) && !empty($_GET['jointable2'])
			&& isset($_GET['joinmethod2']) && !empty($_GET['joinmethod2']))
			) {
				if($_GET['jointable2'] == "NULL") {
					$this->jointable2 = "";
					$this->joinmethod2 = "";
				}
				else {
					$this->jointable2 = $_GET['jointable2'];
					$this->joinmethod2 = $_GET['joinmethod2'];
				}
			}
			else if (isset($_SESSION[$scid.'_jointable2']) && isset($_SESSION[$scid.'_joinmethod2'])) {
				$this->jointable2 = $_SESSION[$scid.'_jointable2'];
				$this->joinmethod2 = $_SESSION[$scid.'_joinmethod2'];
			}
			
			$this->sort_str = "";
			$this->shown_fields = "";
			$this->global_filters = "";
			$this->quick_filter = "";
			$this->quick_value = "";
			$this->sql = "";
			unset($_SESSION[$scid.'_sort_str']);
			unset($_SESSION[$scid.'_shown_fields']);
			unset($_SESSION[$scid.'_global_filters']);
			unset($_SESSION[$scid.'_quick_filter']);
			unset($_SESSION[$scid.'_quick_value']);
			unset($_SESSION[$scid.'_sql']);
			
			$this->groupby = "";
			unset($_SESSION[$scid.'_groupby']);
		}
		/* no change from saved session */
		else if(
		(isset($_SESSION[$scid.'_jointable']) && isset($_SESSION[$scid.'_joinmethod']))
		|| 
		(isset($_SESSION[$scid.'_jointable2']) && isset($_SESSION[$scid.'_joinmethod2']))
		) {
			if (isset($_SESSION[$scid.'_jointable']) && isset($_SESSION[$scid.'_joinmethod'])) {
				$this->jointable = $_SESSION[$scid.'_jointable'];
				$this->joinmethod = $_SESSION[$scid.'_joinmethod'];
			}
			
			if (isset($_SESSION[$scid.'_jointable2']) && isset($_SESSION[$scid.'_joinmethod2'])) {
				$this->jointable2 = $_SESSION[$scid.'_jointable2'];
				$this->joinmethod2 = $_SESSION[$scid.'_joinmethod2'];
			}
			
		}
		
		$_SESSION[$scid.'_jointable'] = $this->jointable;
		$_SESSION[$scid.'_joinmethod'] = $this->joinmethod;
		$_SESSION[$scid.'_jointable2'] = $this->jointable2;
		$_SESSION[$scid.'_joinmethod2'] = $this->joinmethod2;

	}
	
	function processDbChoice() 
	{
		global $scid, $logtable;
		
		//$tables = file("./db_join_tables.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		$result = mysql_query("SHOW TABLES;");
		if (!$result) {
			echo 'Could not run query: ' . mysql_error();
			exit;
		}
		
		if(isset($_GET['logtable']) && !empty($_GET['logtable'])) {
			while ($row = mysql_fetch_row($result)) {
				if($row[0] == $_GET['logtable']) {
					$logtable = $_GET['logtable'];
					$this->logtable = $logtable;
					$_SESSION[$scid.'_logtable'] = $logtable;
				}
			}
		}
		else if(isset($_SESSION[$scid.'_logtable'])) {
			$logtable = $_SESSION[$scid.'_logtable'];
			$this->logtable = $logtable;
		}
		else {
			$row = mysql_fetch_row($result);
			$logtable = $row[0];
			$this->logtable = $logtable;
			$_SESSION[$scid.'_logtable'] = $logtable;
		}
		//*
		if(isset($_GET['logtable']) && !empty($_GET['logtable'])) {
			$this->jointable = "";
			$this->joinmethod = "";
			unset($_SESSION[$scid.'_jointable']);
			unset($_SESSION[$scid.'_joinmethod']);
			
			$this->jointable2 = "";
			$this->joinmethod2 = "";
			unset($_SESSION[$scid.'_jointable2']);
			unset($_SESSION[$scid.'_joinmethod2']);
			
			$this->groupby = "";
			unset($_SESSION[$scid.'_groupby']);
			
			$this->sort_str = "";
			$this->shown_fields = "";
			$this->global_filters = "";
			$this->quick_filter = "";
			$this->quick_value = "";
			$this->sql = "";
			unset($_SESSION[$scid.'_sort_str']);
			unset($_SESSION[$scid.'_shown_fields']);
			unset($_SESSION[$scid.'_global_filters']);
			unset($_SESSION[$scid.'_quick_filter']);
			unset($_SESSION[$scid.'_quick_value']);
			unset($_SESSION[$scid.'_sql']);
		}
		//*/			
	}
	
	function processTableFields() 
	{
		global $scid, $logtable;
		
		$result = mysql_query("SHOW COLUMNS FROM " . $logtable);
		if (!$result) {
			echo 'Could not run query: ' . mysql_error();
			exit;
		}
		
		while ($row = mysql_fetch_row($result)) {
			$this->table_fields[] = $row[0];
		}
		
		if(isset($this->jointable) && !empty($this->jointable)) {
			$result = mysql_query("SHOW COLUMNS FROM " . $this->jointable);
			if (!$result) {
				echo 'Could not run query: ' . mysql_error();
				exit;
			}
		
			while ($row = mysql_fetch_row($result)) {
				if($row[0] != "id" && $row[0] != "host_id")
					$this->table_fields[] = $row[0];
			}
		}
		if(isset($this->jointable2) && !empty($this->jointable2)) {
			$result = mysql_query("SHOW COLUMNS FROM " . $this->jointable2);
			if (!$result) {
				echo 'Could not run query: ' . mysql_error();
				exit;
			}
		
			while ($row = mysql_fetch_row($result)) {
				if($row[0] != "id" && $row[0] != "host_id")
					$this->table_fields[] = $row[0];
			}
		}
		
		if(isset($this->groupby) && !empty($this->groupby))
		{
			$this->table_fields[] = "COUNT(*) as count_".$this->groupby;
		}
		
		if(empty($this->groupby))
		{
			
			foreach($this->table_fields as $field) {
				if(strstr($field, "COUNT(*) as count_") !== FALSE)
					unset($this->table_fields[$field]);
			}
						
			foreach($this->shown_fields as $field) {
				if(strstr($field, "COUNT(*) as count_") !== FALSE)
					unset($this->shown_fields[$field]);
			}

			if(isset($_SESSION[$scid.'_shown_fields'])) {			
				foreach($_SESSION[$scid.'_shown_fields'] as $field) {
					if(strstr($field, "COUNT(*) as count_") !== FALSE)
						unset($_SESSION[$scid.'_shown_fields'][$field]);
				}
			}
			
			if(isset($_SESSION[$scid.'_table_fields'])) {
				foreach($_SESSION[$scid.'_table_fields'] as $field) {  
					if(strstr($field, "COUNT(*) as count_") !== FALSE)
						unset($_SESSION[$scid.'_table_fields'][$field]);
				}
			}

		}
		
		
		if(isset($_GET['show_all_fields']))
			unset($_SESSION[$scid.'_shown_fields']);
		
		if(isset($_SESSION[$scid.'_shown_fields']))
			$this->shown_fields = $_SESSION[$scid.'_shown_fields'];
		else {
			foreach($this->table_fields as $field) {
			/* Field names to be hidden by default */
				if($field != "TrusteeCN"
				&& $field != "sd_id")
				$this->shown_fields[$field] = $field;
			}
		}
		
		if(isset($this->groupby) && !empty($this->groupby))
		{
			$field = "COUNT(*) as count_".$this->groupby;
			$this->shown_fields[$field] = $field;
		}

				
		if(isset($_GET['show_field']) && !empty($_GET['show_field']))
			$this->shown_fields[$_GET['show_field']] = $_GET['show_field'];

		if(isset($_GET['hide_field']) && !empty($_GET['hide_field']) && isset($this->shown_fields[$_GET['hide_field']])) {
			unset($this->shown_fields[$_GET['hide_field']]);
		}
		
		if(isset($_GET['hide_all_fields'])) {
			foreach($this->shown_fields as $field) {
				unset($this->shown_fields[$field]);
			}
		}
		
		$_SESSION[$scid.'_shown_fields'] = $this->shown_fields;
		foreach(array_keys($this->shown_fields) as $key) {
			$this->shown_fields_tmp[$key] = '`' . $this->shown_fields[$key] . '`';
		}
		$this->fields_str = implode(',',$this->shown_fields_tmp);
	}
	
	function getFieldsStr()
	{
		if(empty($this->fields_str))
			return '';//'tags.tag_type';
		return $this->fields_str; // . ',tags.tag_type';
	}
	
	function printFields()
	{
		global $proto;
		$html = '<table>';
		foreach($this->table_fields as $field) {
			if(!isset($this->shown_fields[$field])) {
				$html .= "<tr> <th>".$field.'</th> <td><a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?show_field='.$field.'">SHOW</a></td> </tr>';
			} 
			//else {
			//	$html .= "<tr> <th>".$field.'</th> <td><a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_field='.$field.'">HIDE</a></td> </tr>';
			//}
		}
		$html .= '</table><br>';
		$html .= '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?show_all_fields=true">RESET ALL FIELDS</a><br>';
		$html .= '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_all_fields=true">HIDE ALL FIELDS</a><br>';
		echo $html;
	}
	
	function setLimit($newlimit = 500)
	{
		global $scid;
		if(is_numeric($newlimit) && $newlimit > 0) {
			$_SESSION[$scid.'_limit'] = $newlimit;
			$this->limit = $newlimit;
		}
			
	}
	
	function setRepeatHeader($newrepeat = 100)
	{
		global $scid;
		if(is_numeric($newrepeat) && $newrepeat > 0) {
			$_SESSION[$scid.'_repeatheader'] = $newrepeat;
			$this->repeatheader = $newrepeat;
		}
			
	}
	
	function setStart($newstart = 0)
	{
		global $scid;
		if(is_numeric($newstart) && $newstart >= 0) {
			$_SESSION[$scid.'_start'] = $newstart;
			$this->start = $newstart;
		}
	}
	
	function getLimit()
	{
		return $this->limit;
	}
	
	function getRepeatHeader()
	{
		return $this->repeatheader;
	}
	
	function getStart()
	{
		return $this->start;
	}
	
	function getLimitStr()
	{
		return " LIMIT " . $this->start . "," . $this->limit . ";";
	}
	
	function processTags()
	{
		if(isset($_GET['drop_tag']) && is_numeric($_GET['drop_tag'])) {
			$this->query("DELETE FROM tags WHERE tag_id = '".$_GET['drop_tag']."';");
		}
		
		if(isset($_GET['add_tag_type']) && !empty($_GET['add_tag_type'])
		&& isset($_GET['add_tag_value']) && !empty($_GET['add_tag_value'])
		&& isset($_GET['add_tag_field']) && !empty($_GET['add_tag_field'])) {
			if(isset($_GET['add_tag_reason']) && !empty($_GET['add_tag_reason']))
				$sql = "INSERT INTO tags_view_".strtolower($_GET['add_tag_field'])."(tag_type,reason,".$_GET['add_tag_field'].") VALUES('".$_GET['add_tag_type']."', '".$_GET['add_tag_reason']."', '".str_replace('\\', '\\\\', $_GET['add_tag_value'])."');";
			else
				$sql = "INSERT INTO tags_view_".strtolower($_GET['add_tag_field'])."(tag_type,".$_GET['add_tag_field'].") VALUES('".$_GET['add_tag_type']."', '".str_replace('\\', '\\\\', $_GET['add_tag_value'])."');";
			
			$req = mysql_query($sql, $this->cid) or die(mysql_error());
		}
		
	}
	
	function printTags()
	{
		global $proto;
		$this->query("SELECT * FROM tags ORDER BY tag_id ASC");
		$this->printResult(true);
		
		$sql = "SELECT tag_id FROM tags ORDER BY tag_id ASC;";
		$req = mysql_query($sql, $this->cid) or die(mysql_error());
		$html = '<form method="get" action="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'"><select name="drop_tag">';
		while ($res = mysql_fetch_array($req)) {
			$html.= '<option value="'.$res['tag_id'].'">' . $res['tag_id'] . '</option>';
		}
		$html.= '</select><input type="submit" value="Delete"></form><br><br>';
		
		echo $html;
	}
	
	
	function processGlobalFilter()
	{
		global $scid;
		if(isset($_GET['clear_global_filters'])) {
			unset($this->global_filters);
			$this->global_filters = array();
			unset($_SESSION[$scid.'_global_filters']);
		}
		
		if(isset($_SESSION[$scid.'_global_filters']) && (count($_SESSION[$scid.'_global_filters']) > 0))
			$this->global_filters = $_SESSION[$scid.'_global_filters'];
		
		/* Comment for genericity */
//*
		else {
			// HERE can be put default global filters
			
			//$this->global_filters[] = "(AccessMask & 0x710D016B > 0)";
			//$this->global_filters[] = "(AceFlags & 0x10 = 0)";
			
		}
//*/		
		if(isset($_GET['load_global_filter'])) {

                        if (!$_FILES['up_file']['error']) {
        	                unset($this->global_filters);
				$this->global_filters = array();
				unset($_SESSION[$scid.'_global_filters']);
				
        	                $tmpfile = file($_FILES['up_file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				foreach($tmpfile as $filterline)
					$this->global_filters[] = $filterline;
                                unlink($file_tmp);
                        }
	
		}
		
		
		if(isset($_GET['drop_global_filter']) && is_numeric($_GET['drop_global_filter'])) {
			unset($this->global_filters[$_GET['drop_global_filter']]);
		}
		
		if(isset($_GET['global_filter_field']) && !empty($_GET['global_filter_field'])
		&& (isset($_GET['global_filter_value']) || (isset($_GET['global_filter_value_field']) && !empty($_GET['global_filter_value_field'])))
		&& isset($_GET['global_filter_type']) && !empty($_GET['global_filter_type'])) {
			$prefix = '';
			
			if(!empty($_GET['global_filter_value']))
				$wildchar = '%';
			else
				$wildchar = '';
			
			if ($_GET['global_filter_type'] == 'is') {
				$type = '=';
				$wildchar = '';
				
			}
			if($_GET['global_filter_type'] == 'islike') {
				$type = "LIKE";
				if($_GET['global_filter_field'] == "user") {
					$type = '=';
					$wildchar = '';
				}
			}

			if ($_GET['global_filter_type'] == 'isnot') {
				$type = '!=';
				$wildchar = '';
			}
			if($_GET['global_filter_type'] == 'isnotlike') {
				$type = "NOT LIKE";
				if($_GET['global_filter_field'] == "user") {
					$type = '!=';
					$wildchar = '';
				}
				if($_GET['global_filter_field'] == "tag_type") {
					$type = '!=';
					$wildchar = '';
				}
			}
			if($_GET['global_filter_type'] == 'search') {
				if(isset($_GET['global_filter_operator']) && !empty($_GET['global_filter_operator'])) {
					$type = $_GET['global_filter_operator'];
					$_GET['global_filter_value'] = addslashes($_GET['global_filter_value']);
					if($type == "LIKE" || $type == "NOT LIKE") {
						$wildchar = '%';
						$_GET['global_filter_value'] = str_replace(' ', '%', $_GET['global_filter_value']);
						$_GET['global_filter_value'] = str_replace('_', '\_', $_GET['global_filter_value']);
					}
					else if($type == "LIKE2" || $type == "NOT LIKE2") {
						$wildchar = '';
						$type = str_replace('2', '', $type);
					}
					else {
						$wildchar = '';
					}
				}
				
			}
			
			if($_GET['global_filter_type'] == 'find') {
				if(isset($_GET['global_filter_operator']) && !empty($_GET['global_filter_operator'])) {
					$type = $_GET['global_filter_operator'];
					$_GET['global_filter_value'] = addslashes(substr($_GET['global_filter_value'], 0, 5));
					$wildchar = '';
					
					$_GET['global_filter_field'] = 'SUBSTRING(' . $prefix . $_GET['global_filter_field'] . ',12,5)';
					$prefix = '';
				}
				
			}
			
			if($_GET['global_filter_field'] == "tag_type") {
				$prefix = '';
			}
			
			if(isset($_GET['global_filter_value_field']) && !empty($_GET['global_filter_value_field']))
				$this->global_filters[] = "`".$prefix.$_GET['global_filter_field'] . "` " . $type . " `" . $_GET['global_filter_value_field']."`";
			else if($_GET['global_filter_field'] == "filename")
				$this->global_filters[] = "`".$prefix.$_GET['global_filter_field'] . "` " . $type . " '" . $wildchar . basename(str_replace('\\', '/', $_GET['global_filter_value'])) . $wildchar . "'";
			else if($_GET['global_filter_field'] == "tag_type" && $_GET['global_filter_type'] == 'isnot' && !empty($_GET['global_filter_value']))
				$this->global_filters[] = '(tag_type IS NULL OR `'.$prefix.$_GET['global_filter_field'] . "` " . $type . " '" . $wildchar . $_GET['global_filter_value'] . $wildchar . "')";
			else if($_GET['global_filter_field'] == "AccessMask" && !empty($_GET['global_filter_value']) && isset($_GET['global_filter_operator']) && !empty($_GET['global_filter_operator']) && ($_GET['global_filter_operator'] == '>'))
				$this->global_filters[] = "( ".$prefix."AccessMask & " . $_GET['global_filter_value'] . " > 0)";
			else if($_GET['global_filter_field'] == "AccessMask" && !empty($_GET['global_filter_value']) && isset($_GET['global_filter_operator']) && !empty($_GET['global_filter_operator']) && ($_GET['global_filter_operator'] == 'is'))
				$this->global_filters[] = "( ".$prefix."AccessMask & " . $_GET['global_filter_value'] . " = " . $_GET['global_filter_value'] . ")";
			else if($_GET['global_filter_field'] == "AccessMask" && !empty($_GET['global_filter_value']) && isset($_GET['global_filter_operator']) && !empty($_GET['global_filter_operator']))
				$this->global_filters[] = "( ".$prefix."AccessMask & " . $_GET['global_filter_value'] . " " . $_GET['global_filter_operator'] . " 0)";
			else
				$this->global_filters[] = "`".$prefix.$_GET['global_filter_field'] . "` " . $type . " '" . $wildchar . $_GET['global_filter_value'] . $wildchar . "'";
			
		}
		
		$qstr = $this->getQuickFilterStr();
		if(isset($_GET['save_quick_filter']) && !empty($qstr)) {
			$this->global_filters[] = $qstr;
			$this->quick_filter = "";
			$this->quick_value = "";
			
			/* reprocess to empty filters */
			$_GET['clear_quick_filter'] = "true";
			$this->processQuickFilter();
		}
		
		$_SESSION[$scid.'_global_filters'] = $this->global_filters;
		$this->setGlobalFilterStr();
	}
	
	function printGUID()
	{
		global $proto;
		$this->query("SELECT * FROM GUID ORDER BY id ASC");
		$this->printResult(true);
		
		$sql = "SELECT id FROM GUID ORDER BY id ASC;";
		$req = mysql_query($sql, $this->cid) or die(mysql_error());
		$html = '<form method="get" action="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'"><select name="drop_guid">';
		while ($res = mysql_fetch_array($req)) {
			$html.= '<option value="'.$res['id'].'">' . $res['id'] . '</option>';
		}
		$html.= '</select><input type="submit" value="Delete"></form><br><br>';
		
		// $html.= '<pre>'.print_r($this->guid, true).'</pre>';
		
		echo $html;
	}
	
	function processGUID()
	{
		
		if(isset($_GET['drop_guid']) && is_numeric($_GET['drop_guid'])) {
			$this->query("DELETE FROM GUID WHERE id = '".$_GET['drop_guid']."';");
		}		
		
		if(isset($_GET['guid_value']) && !empty($_GET['guid_value'])
		&& isset($_GET['guid_text']) && !empty($_GET['guid_text'])) {
			$sql = "INSERT INTO GUID(value,text) VALUES('".$_GET['guid_value']."', '".$_GET['guid_text']."');";
			$req = mysql_query($sql, $this->cid) or die(mysql_error() . " : " . $sql);
		}
		
		$sql = "SELECT * FROM GUID;";
		$req = mysql_query($sql, $this->cid) or die(mysql_error() . " : " . $sql);
		while($res = mysql_fetch_array($req)) {
			$this->guid[strtoupper($res['value'])] = $res['text'];
		}
		
		return;
	}
	
	function printGUIDiot()
	{
		global $proto;
		$this->query("SELECT * FROM GUID ORDER BY id ASC");
		$this->printResult(true);
		
		$sql = "SELECT id FROM GUID ORDER BY id ASC;";
		$req = mysql_query($sql, $this->cid) or die(mysql_error() . " : " . $sql);
		$html = '<form method="get" action="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'"><select name="drop_guid_iot">';
		while ($res = mysql_fetch_array($req)) {
			$html.= '<option value="'.$res['id'].'">' . $res['id'] . '</option>';
		}
		$html.= '</select><input type="submit" value="Delete"></form><br><br>';
		
		// $html.= '<pre>'.print_r($this->guid, true).'</pre>';
		
		echo $html;
	}
	
	/* It was thought Inherited Object Type and Object Type GUIDs could collide
	while having different meanings. This is still unclear but does not seem to 
	be possible, therefore separate functions and tables were made. */
	function processGUIDiot()
	{
		
		if(isset($_GET['drop_guid_iot']) && is_numeric($_GET['drop_guid_iot'])) {
			$this->query("DELETE FROM GUID WHERE id = '".$_GET['drop_guid_iot']."';");
		}		
		
		if(isset($_GET['guid_iot_value']) && !empty($_GET['guid_iot_value'])
		&& isset($_GET['guid_iot_text']) && !empty($_GET['guid_iot_text'])) {
			$sql = "INSERT INTO GUID(value,text) VALUES('".$_GET['guid_iot_value']."', '".$_GET['guid_iot_text']."');";
			$req = mysql_query($sql, $this->cid) or die(mysql_error() . " : " . $sql);
		}
		
		$sql = "SELECT * FROM GUID;";
		$req = mysql_query($sql, $this->cid) or die(mysql_error() . " : " . $sql);
		while($res = mysql_fetch_array($req)) {
			$this->guid[strtoupper($res['value'])] = $res['text'];
		}
		
		return;
	}
	
	function setGlobalFilterStr()
	{
		if(isset($this->global_filters) && is_array($this->global_filters))
			$this->global_filters_str = implode(" AND ", $this->global_filters);
	}
	
	function getGlobalFilterStr()
	{
		return $this->global_filters_str;
	}
	
	function processQuickFilter()
	{
		global $scid;
		if(isset($_GET['clear_quick_filter'])) {
			$this->quick_filter = "";
			$this->quick_value = "";
			unset($_SESSION[$scid.'_quick_filter']);
			unset($_SESSION[$scid.'_quick_value']);
			
			$this->setLimit();
			$this->setStart();
		}
		else if(isset($_GET['quick_filter']) && isset($_GET['quick_value'])) {
			$this->quick_filter = "`".$_GET['quick_filter']."`";
			$this->quick_value = $_GET['quick_value'];
			$_SESSION[$scid.'_quick_filter'] = "`".$_GET['quick_filter']."`";
			$_SESSION[$scid.'_quick_value'] = $_GET['quick_value'];
			
			$this->setLimit();
			$this->setStart();
		} 
		else if(isset($_SESSION[$scid.'_quick_filter']) && isset($_SESSION[$scid.'_quick_value'])) {
			$this->quick_filter = $_SESSION[$scid.'_quick_filter'];
			$this->quick_value = $_SESSION[$scid.'_quick_value'];
		}
		
		
		unset($_SESSION[$scid.'_sql']);
		unset($this->sql);
		
		$this->setQuickFilterStr();
	}

	function setQuickFilterStr()
	{
		if(isset($this->quick_filter) && isset($this->quick_value) && !empty($this->quick_filter)/* && !empty($this->quick_value)*/) {
			if(!empty($this->quick_value))
				$wildchar = '%';
			else
				$wildchar = '';
				


			$type = "LIKE";
			$result = mysql_query("SELECT COLUMN_TYPE FROM information_schema.columns WHERE TABLE_NAME = ' " . $logtable . "' AND COLUMN_NAME = '" . $this->quick_filter . "'");
			if (!$result) {
				echo 'Could not run query: ' . mysql_error();
				exit;
			}
			if ($result != "varchar(255)")
			{
				$type = '=';
				$wildchar = '';
			}

			if($this->quick_filter == "fname" || $this->quick_filter == "path") {
				$type = 'LIKE';
				$wildchar = '%';
			}
			
			$prefix = '';
			if($this->quick_filter == "tag_type")
				$prefix = '';
			
				
			if($this->quick_filter == "filename")
				$this->filter = $prefix.mysql_real_escape_string($this->quick_filter, $this->cid) . " ".$type." '". $wildchar . basename(str_replace('\\', '/', $this->quick_value)) . $wildchar."'";
			else if($this->quick_filter == "fname" || $this->quick_filter == "path")
				$this->filter = $prefix.mysql_real_escape_string($this->quick_filter, $this->cid) . " ".$type." '". $wildchar . basename(str_replace('\\', '%', $this->quick_value)) . $wildchar."'";
			else
				$this->filter = $prefix.mysql_real_escape_string($this->quick_filter, $this->cid) . " ".$type." '".$wildchar . str_replace(':', '%', mysql_real_escape_string($this->quick_value, $this->cid)) . $wildchar."'";
		}
		else
			$this->filter = "";
	}
	
	function getQuickFilterStr()
	{
		return $this->filter;
	}
	
	function getFilterStr()
	{
		$s1 = $this->getQuickFilterStr();
		$s2 = $this->getGlobalFilterStr();
		$f = array();
		if(!empty($s1))
			$f[] = $s1;
		if(!empty($s2))
			$f[] = $s2;
		
		$s3 = implode(" AND ", $f);
		
		if(!empty($s3))
			return " WHERE " . $s3 . " ";
		else
			return " ";
	}
	
	function printGlobalFilters()
	{
		global $proto, $scid;
		$html = '<table>';
		//for($i = 0; $i < count($this->global_filters); $i++) {
		if(isset($this->global_filters) && is_array($this->global_filters)) {
			foreach(array_keys($this->global_filters) as $key) {
				$html .= '<tr><th>'.$this->global_filters[$key].'</th><td><a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?drop_global_filter='.$key.'">DROP</a></td></tr>';
			}
		}
		$html.= '</table><br>';
		$html.= '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?clear_global_filters=true"> RESET GLOBAL FILTERS </a><br>';
		
		$html.= '<form enctype="multipart/form-data" action="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?&amp;load_global_filter=upload" method="POST">';
		$html.= 'Select filter file: <input type="file" name="up_file"> <input type="submit" value="upload"></form>';
		
		$html.= '<a href="global_filters.php?scid='.$scid.'"> SAVE CURRENT GLOBAL FILTERS </a><br>';

		
		echo $html;
	}
	
	/* generate SQL query and execute it */
	function query($sql = "", $dump = false)
	{
		global $scid, $logtable;
		if($sql != "")
			$special = true;
		else $special = false;
		
		$join_str = "";
		if(!empty($this->jointable) && !empty($this->joinmethod))
			$join_str .= " ".$this->joinmethod." " . $this->jointable . " ";
		if(!empty($this->jointable2) && !empty($this->joinmethod2))
			$join_str .= " ".$this->joinmethod2." " . $this->jointable2 . " ";
		
		$groupby_str = "";
		if(!empty($this->groupby))
			$groupby_str = " GROUP BY " . $this->groupby . " ";
		
		if(!isset($sql) || empty($sql)) {

				$fields = $this->getFieldsStr(); // fields chosen by the user
				if(empty($fields)) { // dirty hack to modify query if no fields where selected: just show count(*)
					echo "DEBUG: Count not possible due to too big data";
					/* $fields = "*";
					$sql = "SELECT COUNT(*) FROM " . $logtable;
					$special = true;*/
				}
				else
					$sql = "SELECT DISTINCT " . $fields . " FROM " . $logtable . " T "; // LEFT OUTER JOIN tags ON (T.hostname = tags.hostname OR T.filename = tags.filename OR T.filesize = tags.filesize OR T.filemd5 = tags.filemd5)";
				$_SESSION[$scid.'_sql'] = $sql;
			//}
		}
		
		if(isset($sql) && !empty($sql)) {
			if($special) {
				$append = "";
				$append2 = "";
			} else {
				//$having = " HAVING (SUBSTRING(win32_ctime,12,2) BETWEEN '00' AND '06') OR (SUBSTRING(mft_ctime,12,2) BETWEEN '00' AND '06') ";
				$having = "";
				$append = $join_str . $this->getFilterStr() . $groupby_str . $having . $this->getSortStr();
				$append2 = $join_str . $this->getFilterStr() . $groupby_str . $having . $this->getSortStr() . $this->getLimitStr();
			}
			
			if($dump === true) {
				$append2 = $append;
			}
			
			/* Once for the stats */
			$this->sql = $sql;
			$this->count = 0;
						
			/* Not to be done if the table is too large
			$this->res = $this->pg_query_wrapper($this->cid, $sql . $append) or die(pg_last_error() . "<br>" . $sql);
			$this->count = pg_num_rows($this->res);
			*/
			
			/* Once for actual rendering of results records */
			$this->executed = $sql . $append2;
			$this->res = mysql_query($this->executed, $this->cid) or die(mysql_error() . " : " . $sql);
			$this->numfields = mysql_num_fields($this->res);
			$this->numrows = mysql_num_rows($this->res);
		}
	}
	
	/* Should do the same as printResult() but generate a downloadable TSV file
	FIXME: is not sync'ed with current decoding features */
	function csvDump()
	{
		global $logtable;
		
		$ace[0x1]          = "ADS_RIGHT_DS_CREATE_CHILD";
		$ace[0x2]          = "ADS_RIGHT_DS_DELETE_CHILD";
		$ace[0x4]          = "ADS_RIGHT_ACTRL_DS_LIST";
		$ace[0x8]          = "ADS_RIGHT_DS_SELF";
		$ace[0x10]         = "ADS_RIGHT_DS_READ_PROP";
		$ace[0x20]         = "ADS_RIGHT_DS_WRITE_PROP";
		$ace[0x40]         = "ADS_RIGHT_DS_DELETE_TREE";
		$ace[0x80]         = "ADS_RIGHT_DS_LIST_OBJECT";
		$ace[0x100]        = "ADS_RIGHT_DS_CONTROL_ACCESS";
		$ace[0x10000]      = "ADS_RIGHT_DELETE";
		$ace[0x20000]      = "ADS_RIGHT_READ_CONTROL";
		$ace[0x40000]      = "ADS_RIGHT_WRITE_DAC";
		$ace[0x80000]      = "ADS_RIGHT_WRITE_OWNER";
		$ace[0x100000]     = "ADS_RIGHT_SYNCHRONIZE";
		$ace[0x1000000]    = "ADS_RIGHT_ACCESS_SYSTEM_SECURITY";
		$ace[0x10000000]   = "ADS_RIGHT_GENERIC_ALL";
		$ace[0x20000000]   = "ADS_RIGHT_GENERIC_EXECUTE";
		$ace[0x40000000]   = "ADS_RIGHT_GENERIC_WRITE";
		$ace[0x80000000]   = "ADS_RIGHT_GENERIC_READ";
		
		$exch[0x1]          = "RIGHT_DS_MAILBOX_OWNER";
		$exch[0x2]          = "RIGHT_DS_SEND_AS";
		$exch[0x4]          = "RIGHT_DS_PRIMARY_OWNER";
		$exch[0x10000]      = "RIGHT_DS_DELETE";
		$exch[0x20000]      = "RIGHT_DS_READ";
		$exch[0x40000]      = "RIGHT_DS_CHANGE";
		$exch[0x80000]      = "RIGHT_DS_TAKE_OWNERSHIP";

		//header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: inline; filename=dump.tsv');
		//header('Content-Disposition: attachment; filename=' . basename($file));
		//header('Content-Transfer-Encoding: binary');
		//header('Expires: 0');
		//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		//header('Pragma: public');
                //header('Content-Length: ' . $file_size);

		$this->query("", true);
		
		if(!isset($_GET['noheader']) && isset($_GET['decode']))
			echo str_replace("AccessMask", "AccessMask\tHexAccessMask\tDecodedAccessMask", implode("\t", $this->shown_fields)) . "\n";
		else if(!isset($_GET['noheader']))
			echo implode("\t", $this->shown_fields) . "\n";
		
		
		while($row = mysql_fetch_array($this->res))
		{
			$val = array();
			for($j = 0; $j < $this->numfields; $j++) {
				if(isset($_GET['hexa']) && (array_search(mysql_field_name($this->res,$j), array("AccessMask")) !== FALSE))
					$val[] = sprintf("0x%x", $row[$j]);
				else if(isset($_GET['resolve']) && (array_search(mysql_field_name($this->res,$j), array("OwnerSID","GroupSID","SID","PrimaryOwner","PrimaryGroup")) !== FALSE)) {
					if(isset($this->sid[$row[$j]]))
						$val[] .= $this->sid[$row[$j]];
					else {
						$sidsql = "SELECT * FROM SID WHERE ObjectSID = '".$row[$j]."' AND LDAPDisplayName != '' AND LDAPDisplayName IS NOT NULL;";
						$sidreq = mysql_query($sidsql, $this->cid) or die(mysql_error() . " : " . $sql);
						if(mysql_num_rows($sidreq)) {
							$sidres = mysql_fetch_row($sidreq);
							if(!empty($sidres[0])) {
								$this->sid[$row[$j]] = $sidres[0];
								$val[] .= $this->sid[$row[$j]];
							}
							else
								$val[] .= $row[$j];
						}
						else
							$val[] .= $row[$j];
					}
				}
				else if(isset($_GET['resolve']) && (array_search(mysql_field_name($this->res,$j), array("ObjectCategory")) !== FALSE)) {
					if(isset($this->sid[$row[$j]]))
						$val[] .= $this->sid[$row[$j]];
					else {
						$sidsql = "SELECT * FROM ObjectCategory WHERE ObjDistName = '".$row[$j]."' AND LdapDisplayName != '' AND LDAPDisplayName IS NOT NULL;";
						$sidreq = mysql_query($sidsql, $this->cid) or die(mysql_error() . " : " . $sql);
						if(mysql_num_rows($sidreq)) {
							$sidres = mysql_fetch_row($sidreq);
							if(!empty($sidres[0])) {
								$this->sid[$row[$j]] = $sidres[0];
								$val[] .= $this->sid[$row[$j]];
							}
							else
								$val[] .= $row[$j];
						}
						else
							$val[] .= $row[$j];
					}
				}
				else if(isset($_GET['decode']) && (array_search(mysql_field_name($this->res,$j), array("AccessMask")) !== FALSE)) {
					$tval = array();
					for ($i = 0; $i < 32; $i++)
					{
						if((((int) $row[$j]) & (1<<$i)) > 0) {
							//echo " " . $row[$j] . " (match: ".(1<<$i)." = ".$ace[(1<<$i)].")";
							if(strstr($logtable,"EXCH") !== FALSE) {
								if(isset($exch[(1<<$i)]) && !empty($exch[(1<<$i)])) {
									$tval[] = $exch[(1<<$i)];
								}
							}
							else {
								if(isset($ace[(1<<$i)]) && !empty($ace[(1<<$i)])) {
									$tval[] = $ace[(1<<$i)];
								}
							}
						}
					}
					
					$val[] = $row[$j];
					$val[] = sprintf("0x%x", $row[$j]);
					$val[] = implode(';', $tval);
					unset($tval);
				}
				else
					$val[] = $row[$j];
			}
			echo implode("\t", $val) . "\n";
			unset($val);
		}
		/*
		foreach($this->shown_fields as $field) {
			$this->shown_fields[$field] = $field;
		}
		*/
		die();
	}
	
	/* generate table of results with filter controls, and on-the-fly decoding and caching of SIDs/AccessMask/GUIDs */
	function printResult($hide = false)
	{
		global $scid, $proto, $logtable;
		$result = "<table>\n";
		$debug = "";
		$header = "<tr>\n";
		for($i = 0; $i < $this->numfields; $i++) {
			if(isset($this->shown_fields[mysql_field_name($this->res, $i)]) && strstr(mysql_field_name($this->res, $i), "count_" . $this->groupby) === FALSE && $hide !== true) {
				// $debug .= "<pre>".print_r($this->shown_fields, true)."</pre><pre>".mysql_field_name($this->res, $i)."</pre>\n";
				$hidelink = ' (<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_field='.mysql_field_name($this->res, $i).'">H</a>)';
				$sortal = ' (<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?order=ASC&amp;orderby='.mysql_field_name($this->res, $i).'">A</a>/';
				$sortdl = '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?order=DESC&amp;orderby='.mysql_field_name($this->res, $i).'">D</a>)';
			} 
			else if ((mysql_field_name($this->res, $i) == "count_" . $this->groupby) && $hide !== true) {
				$hidelink = '';
				$sortal = ' (<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?order=ASC&amp;orderby='.mysql_field_name($this->res, $i).'">A</a>/';
				$sortdl = '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?order=DESC&amp;orderby='.mysql_field_name($this->res, $i).'">D</a>)';
			}
			else {
				$hidelink = "";
				$sortal = "";
				$sortdl = "";
			}
			
			// handling hardcoded cases depending on column name
			if(array_search(mysql_field_name($this->res,$i), array("AccessMask")) !== FALSE)
				if((strstr($logtable,"EXCH") !== FALSE) && (array_search(mysql_field_name($this->res,$i), array("AccessMask")) !== FALSE))
					$header .= "<th>" . '<a href="#" onclick="javascript:document.getElementById(\'accessmaskexch_div\').style.display=\'block\';">'.mysql_field_name($this->res, $i)."</a>" . $hidelink . $sortal . $sortdl . "</th>\n";
				else
					$header .= "<th>" . '<a href="#" onclick="javascript:document.getElementById(\'accessmask_div\').style.display=\'block\';">'.mysql_field_name($this->res, $i)."</a>" . $hidelink . $sortal . $sortdl . "</th>\n";
			else
				$header .= "<th>" . mysql_field_name($this->res, $i) . $hidelink . $sortal . $sortdl . "</th>\n";
		}
		$header .= "</tr>\n";
		
		// for each returned record
		for($row = mysql_fetch_array($this->res), $i = 0; $row; $row = mysql_fetch_array($this->res), $i++) {
			if(!($i%$this->repeatheader))
				$result .= $header;
			
			$result .= "<tr>\n";
			
			// for each field
			for($j = 0; $j < $this->numfields; $j++) {
				// handling hardcoded cases depending on column name
				if((array_search(mysql_field_name($this->res,$j), array("AccessMask")) !== FALSE))
					// decode AccessMask differently for exchange (table name contains EXCH)
					if(strstr($logtable,"EXCH") !== FALSE)
						$result .= '<td onmouseover="decodeAccessMaskExch('.$row[$j].'); tooltip.show(document.getElementById(\'decodedAccessMask\').innerHTML, 384);" onmouseout="tooltip.hide();">';
					else
						$result .= '<td onmouseover="decodeAccessMask('.$row[$j].'); tooltip.show(document.getElementById(\'decodedAccessMask\').innerHTML, 384);" onmouseout="tooltip.hide();">';
				else if((array_search(mysql_field_name($this->res,$j), array("ObjectCategory")) !== FALSE))
						$result .= '<td onmouseover="tooltip.show(document.getElementById(\'objCat_'.$row[$j].'\').innerHTML, 384);" onmouseout="tooltip.hide();">';
				else if((array_search(mysql_field_name($this->res,$j), array("OwnerSID","ObjectSID","GroupSID","SID","PrimaryOwner","PrimaryGroup","TrusteeSID")) !== FALSE))
						$result .= '<td onmouseover="tooltip.show(document.getElementById(\'objSid_'.$row[$j].'\').innerHTML, 448);" onmouseout="tooltip.hide();">';
				else
					$result .= '<td>';
				
				// if controls shall be displayed (garbage)
				if(!isset($_SESSION[$scid.'_hide_garbage']) && $hide !== true)
				{
					$result .= ' |';
					$result .= '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?global_filter_field='.mysql_field_name($this->res,$j).'&amp;global_filter_value='.addslashes($row[$j]).'&amp;global_filter_type=isnot">!=</a>';
					$result .= '|';
					$result .= '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?global_filter_field='.mysql_field_name($this->res,$j).'&amp;global_filter_value='.addslashes($row[$j]).'&amp;global_filter_type=is">==</a>';
					$result .= '| ';
					
				}
				// handling hardcoded cases depending on column name
				// WARNING! this is not hidden by the "hide garbage" feature
				if((array_search(mysql_field_name($this->res,$j), array("ObjectType")) !== FALSE) && !empty($row[$j]) && !isset($this->guid[strtoupper($row[$j])])) {
					$result .= '|<a href="#" onclick="javascript:displayGuidForm(\''.$row[$j].'\')">?</a>';
					
					$result .= '| ';
				}
				if((array_search(mysql_field_name($this->res,$j), array("InheritedObjectType")) !== FALSE) && !empty($row[$j]) && !isset($this->guid[strtoupper($row[$j])])) {
					$result .= '|<a href="#" onclick="javascript:displayGuidIotForm(\''.$row[$j].'\')">?</a>';
					
					$result .= '| ';
				}
				if((array_search(mysql_field_name($this->res,$j), array("samAccountName","username","name","user")) !== FALSE) && !empty($row[$j])) {
					$result .= '|<a href="#" onclick="javascript:displayTagForm(\''.mysql_field_name($this->res,$j).'\',\''.str_replace('\\', '\\\\', $row[$j]).'\')">Tag</a>';
					
					$result .= '| ';
				}
				
				// quick filter link (with onclick for quick filter)
//				$result .= '<span class="tdtext" onclick="javascript:window.location=\''.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?quick_filter='.mysql_field_name($this->res,$j).'&amp;quick_value='.addslashes(str_replace('\\','%25',$row[$j])).'\';">';
				$result .= '<span class="tdtext" onclick="javascript:window.location=\''.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?quick_filter='.mysql_field_name($this->res,$j).'&amp;quick_value='.addslashes(/*str_replace('\\','%25',*/$row[$j]/*)*/).'\';">';
				
				// data insertion
				if((array_search(mysql_field_name($this->res,$j), array("ObjectType")) !== FALSE) && !empty($row[$j]) && isset($this->guid[strtoupper($row[$j])])) {
					$result .= $this->guid[strtoupper($row[$j])];
				}
				else if((array_search(mysql_field_name($this->res,$j), array("InheritedObjectType")) !== FALSE) && !empty($row[$j]) && isset($this->guid[strtoupper($row[$j])])) {
					$result .= $this->guid[strtoupper($row[$j])];
				}
				else if((array_search(mysql_field_name($this->res,$j), array("AccessMask")) !== FALSE)) {
					$result .= sprintf("0x%X", $row[$j]);
				}
				// Columns containing SIDs to be decoded on the fly
				else if((array_search(mysql_field_name($this->res,$j), array("OwnerSID","ObjectSID","GroupSID","SID","PrimaryOwner","PrimaryGroup","TrusteeSID")) !== FALSE)) {
					if(isset($this->sid[$row[$j]]))
						$result .= $this->sid[$row[$j]];
					else {
						$sidsql = "SELECT * FROM SID WHERE ObjectSID LIKE '".$row[$j]."' AND LDAPDisplayName != '' AND LDAPDisplayName IS NOT NULL;";
						$sidreq = mysql_query($sidsql, $this->cid) or die(mysql_error() . " : " . $sql);
						if(mysql_num_rows($sidreq)) {
							$sidres = mysql_fetch_row($sidreq);
							if(!empty($sidres[0])) {
								$this->sid[$row[$j]] = $sidres[0];
								$result .= $this->sid[$row[$j]];
								$this->objsid[$row[$j]][] = $row[$j];
							}
							else
								$result .= $row[$j];
						}
						else
							$result .= $row[$j];
					}
				}
				
				else if((array_search(mysql_field_name($this->res,$j), array("ObjectCategory")) !== FALSE)) {
					if(!isset($this->objcat[$row[$j]])) {
						$sidsql = "SELECT * FROM ObjectCategory WHERE ObjDistName = '".$row[$j]."' AND LdapDisplayName != '' AND LDAPDisplayName IS NOT NULL;";
						$sidreq = mysql_query($sidsql, $this->cid) or die(mysql_error() . " : " . $sql);
						if(mysql_num_rows($sidreq)) {
							$this->objcat[$row[$j]][] = $row[$j];
							while($sidres = mysql_fetch_array($sidreq)) {
								if(!empty($sidres['LdapDisplayName'])) {
									$this->objcat[$row[$j]][] = $sidres['LdapDisplayName'];
									$last = $sidres['LdapDisplayName'];
								}
							}
							if(isset($last))
								$result .= $last;
							else 
								$result .= $row[$j];
						}
						else
							$result .= $row[$j];
					} 
					else 
						$result .= $this->objcat[$row[$j]][count($this->objcat[$row[$j]]) - 1];
					
				}
				else	
					$result .= $row[$j];
				
				// end of quick filter link
				$result .= "</span></td>\n";
			}
			$result .= "</tr>\n";
		}
		
		$result .= "</table><br>\n".$debug;
		
		if(isset($this->objsid)) {
			foreach ($this->objsid as $sid) {
				$result .= "<!-- Div pour l'affichage du decodage d'un ObjectCategory -->\n";
				$result .= "<div id=\"objSid_".$sid[0]."\" style=\"display: none;\">\n";
				$result .= "\t<ul id=\"objSid_".$sid[0]."_ul\" class=\"tooltip2\">\n";
				for ($sid_i = 0; isset($sid[$sid_i]); $sid_i++) {
					$result .= "\t\t<li>".$sid[$sid_i]."</li>\n";
				}
				$result .= "\t</ul>\n";
				$result .= "</div>\n";
			}
		}
		
		if(isset($this->objcat)) {
			foreach ($this->objcat as $cat) {
				$result .= "<!-- Div pour l'affichage du decodage d'un ObjectCategory -->\n";
				$result .= "<div id=\"objCat_".$cat[0]."\" style=\"display: none;\">\n";
				$result .= "\t<ul id=\"objCat_".$cat[0]."_ul\" class=\"tooltip\">\n";
				for ($cat_i = 1; isset($cat[$cat_i]); $cat_i++) {
					$result .= "\t\t<li>".$cat[$cat_i]."</li>\n";
				}
				$result .= "\t</ul>\n";
				$result .= "</div>\n";
			}
		}
		
		// $result .= print_r($sidres, true);
		//if($this->numrows >= $this->limit)
		//	$countstr = "More than " . $this->limit;
		//else
		//	$countstr = $this->numrows;
		$countstr = $this->count;	
		
		//echo $countstr . " results (". $this->records . " database records".") [shown: ".$this->numrows."]" . "<br>\n";
		echo "[shown: ".$this->numrows." <b>start=".$this->getStart()."</b>]" . "<br>\n";
		echo $result;
		echo "[shown: ".$this->numrows." <b>start=".$this->getStart()."</b>]" . "<br>\n";
		//echo $countstr . " results (". $this->records . " database records".") [shown: ".$this->numrows."]" . "<br>\n";
	}
	
}
?>
