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

global $scid;
$scid = md5($_SERVER['SCRIPT_FILENAME']);
global $proto;

if($_SERVER['REMOTE_ADDR'] == "127.0.0.1" || substr($_SERVER['REMOTE_ADDR'], 0, 10) == "192.168.0.")
	$proto = "http";
else 
	$proto = "https";
	
global $dbbrowser_version;
global $host;
global $user;
global $pass;
global $base;
global $logtable;

$dbbrowser_version = "0.3";

include 'settings.php';
include 'dbbrowser.class.php';

?>
<?php
session_start();
/* Saved (serialized) session restoration */

if(isset($_GET['restore']) && !empty($_GET['restore']) && is_file("./sessions/".$_GET['restore'])) {
	//unset($_SESSION);
	$sdata = file_get_contents("./sessions/".$_GET['restore']);
	$_SESSION = unserialize($sdata);
}
else if(isset($_GET['save']) && !empty($_GET['save'])) {
	if(!empty($_GET['save'])) {
		$tables = file("./db_sessions.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		$hash = md5($_GET['save']);
		$found = false;
		foreach ($tables as $t) {
			$line = explode("|", $t, 2);
			if($hash == $line[0]) {
				$found = true;
			}
		}
		if(!$found)
			file_put_contents("./db_sessions.txt", $hash."|".$_GET['save']."\n", FILE_APPEND);
	
		file_put_contents("./sessions/".$hash, serialize($_SESSION));
	}
}

$browser = new DBBrowser();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
        <title>dbBrowser v<?php echo $dbbrowser_version; ?></title>
	<link rel="stylesheet" href="./css/style.css" type="text/css">
	<script type="text/javascript" src="js/tooltips.js"></script>
	<script type="text/javascript" src="js/helpers.js"></script>
	<script type="text/javascript" src="js/scriptaculous/lib/prototype.js"></script>
	<script type="text/javascript" src="js/scriptaculous/src/scriptaculous.js"></script>
	

</head>
<body onload="">
<!-- Div pour le logo -->
<div id="logo" style="float: right; width: 160px; height: 160px; border-style: none; border-width: 1px; background-image: url('./img/logo-v9-medaille.png'); background-repeat: no-repeat;">
	&nbsp;
</div>

<!-- Div pour l'affichage du décodage d'un accessmask -->
<div id="decodedAccessMask" style="display: none;">
	<ul id="decodedAccessMask_ul" class="tooltip">
		<li>none</li>
	</ul>
</div>
<!-- Formulaire d'ajout de GUID' -->
<div id="guid_div" style="display: none;">
	<h3>Add ObjectType (GUID) description (<a href="#" onclick="javascript:document.getElementById('guid_div').style.display='none';">close</a>)</h3>
	<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
		<input id="guid_value" name="guid_value" type="text" style="width: 350px; font-family: 'Courier New', 'Sans serif'; background-color: #CCC;" value="GUID"><br>
		<input id="guid_text" name="guid_text" type="text" style="width: 350px; font-family: 'Courier New', 'Sans serif'; background-color: #CFC;" value="NAME" onclick="javascript:this.value=''"><br>
		<input type="submit" value="ok"> <a href="#" onclick="javascript:document.getElementById('guid_div').style.display='none';">close</a>
	</form>
</div>
<!-- GUID Inherited Object Type -->
<div id="guid_iot_div" style="display: none;">
	<h3>Add InheritedObjectType (GUID iot) description (<a href="#" onclick="javascript:document.getElementById('guid_iot_div').style.display='none';">close</a>)</h3>
	<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
		<input id="guid_iot_value" name="guid_iot_value" type="text" style="width: 350px; font-family: 'Courier New', 'Sans serif'; background-color: #CCC;" value="GUID"><br>
		<input id="guid_iot_text" name="guid_iot_text" type="text" style="width: 350px; font-family: 'Courier New', 'Sans serif'; background-color: #CFC;" value="NAME" onclick="javascript:this.value=''"><br>
		<input type="submit" value="ok"> <a href="#" onclick="javascript:document.getElementById('guid_iot_div').style.display='none';">close</a>
	</form>
</div>
<!-- AccessMask handling form -->
<div id="accessmaskexch_div" style="display: none;">
	<h3>Flag filter (<a href="#" onclick="javascript:document.getElementById('accessmaskexch_div').style.display='none';">close</a>)</h3>
	<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>?global_filter_field=AccessMask">
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_1" name="mask_exch_value_1" type="checkbox" value="1">RIGHT_DS_MAILBOX_OWNER (0x1)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_2" name="mask_exch_value_2" type="checkbox" value="2">RIGHT_DS_SEND_AS (0x2)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_3" name="mask_exch_value_3" type="checkbox" value="4">RIGHT_DS_PRIMARY_OWNER (0x4)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_4" name="mask_exch_value_4" type="checkbox" value="65536">RIGHT_DS_DELETE (0x10000)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_5" name="mask_exch_value_5" type="checkbox" value="131072">RIGHT_DS_READ (0x20000)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_6" name="mask_exch_value_6" type="checkbox" value="262144">RIGHT_DS_CHANGE (0x40000)<br>
		<input onclick="javascript:updateAccessMaskExch();" id="mask_exch_value_7" name="mask_exch_value_7" type="checkbox" value="524288">RIGHT_DS_TAKE_OWNERSHIP (0x80000)<br>
		<input type="text" id="mask_exch_final_value" name="global_filter_value" value="0x0"><br>
		<input type="radio" name="global_filter_operator" value="is"> Has flags<br>
		<input type="radio" name="global_filter_operator" value=">" checked> Include flags<br>
		<input type="radio" name="global_filter_operator" value="="> Exclude flags<br>
		<input type="hidden" name="global_filter_field" value="AccessMask">
		<input type="submit" name="global_filter_type" value="mask"> <a href="#" onclick="javascript:document.getElementById('accessmaskexch_div').style.display='none';">close</a>
</form>

</div>
<div id="accessmask_div" style="display: none;">
	<h3>Flag filter (<a href="#" onclick="javascript:document.getElementById('accessmask_div').style.display='none';">close</a>)</h3>
	<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>?global_filter_field=AccessMask">
		<input onclick="javascript:updateAccessMask();" id="mask_value_1" name="mask_value_1" type="checkbox" value="1">ADS_RIGHT_DS_CREATE_CHILD (0x1)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_2" name="mask_value_2" type="checkbox" value="2">ADS_RIGHT_DS_DELETE_CHILD (0x2)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_3" name="mask_value_3" type="checkbox" value="4">ADS_RIGHT_ACTRL_DS_LIST (0x4)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_4" name="mask_value_4" type="checkbox" value="8">ADS_RIGHT_DS_SELF (0x8)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_5" name="mask_value_5" type="checkbox" value="16">ADS_RIGHT_DS_READ_PROP (0x10)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_6" name="mask_value_6" type="checkbox" value="32">ADS_RIGHT_DS_WRITE_PROP (0x20)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_7" name="mask_value_7" type="checkbox" value="64">ADS_RIGHT_DS_DELETE_TREE (0x40)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_8" name="mask_value_8" type="checkbox" value="128">ADS_RIGHT_DS_LIST_OBJECT (0x80)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_9" name="mask_value_9" type="checkbox" value="256">ADS_RIGHT_DS_CONTROL_ACCESS (0x100)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_10" name="mask_value_10" type="checkbox" value="65536">ADS_RIGHT_DELETE (0x10000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_11" name="mask_value_11" type="checkbox" value="131072">ADS_RIGHT_READ_CONTROL (0x20000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_12" name="mask_value_12" type="checkbox" value="262144">ADS_RIGHT_WRITE_DAC (0x40000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_13" name="mask_value_13" type="checkbox" value="524288">ADS_RIGHT_WRITE_OWNER (0x80000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_14" name="mask_value_14" type="checkbox" value="1048576">ADS_RIGHT_SYNCHRONIZE (0x100000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_15" name="mask_value_15" type="checkbox" value="16777216">ADS_RIGHT_ACCESS_SYSTEM_SECURITY (0x1000000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_16" name="mask_value_16" type="checkbox" value="2147483648">ADS_RIGHT_GENERIC_READ (0x80000000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_17" name="mask_value_17" type="checkbox" value="1073741824">ADS_RIGHT_GENERIC_WRITE (0x40000000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_18" name="mask_value_18" type="checkbox" value="536870912">ADS_RIGHT_GENERIC_EXECUTE (0x20000000)<br>
		<input onclick="javascript:updateAccessMask();" id="mask_value_19" name="mask_value_19" type="checkbox" value="268435456">ADS_RIGHT_GENERIC_ALL (0x10000000)<br>
		<input type="text" id="mask_final_value" name="global_filter_value" value="0x0"><br>
		<input type="radio" name="global_filter_operator" value="is"> Has flags<br>
		<input type="radio" name="global_filter_operator" value=">" checked> Include flags<br>
		<input type="radio" name="global_filter_operator" value="="> Exclude flags<br>
		<input type="hidden" name="global_filter_field" value="AccessMask">
		<input type="submit" name="global_filter_type" value="mask"> <a href="#" onclick="javascript:document.getElementById('accessmask_div').style.display='none';">close</a>
</form>
</div>
<?php

/* Page handling */

$prev = ($browser->getStart()-$browser->getLimit());
$next = ($browser->getStart()+$browser->getLimit());
if($prev < 0)					$prev = 0;
if($browser->getLimit() > $browser->numrows)	$next = 0;

/* For debug purposes */
$saved = base64_encode(serialize($browser));
$_SESSION[$scid.'_saved_queries'][] = $saved;

?>
<div style="border-style: solid; border-width: 1px; background-color: #EFE; padding: 8px;">
<!-- Display preferences forms -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	<span style="font-weight: bold; font-size: 13pt;">Data</span>
	Header every <input style="width: 32px;" type="text" name="repeatheader" value="<?php echo $browser->getRepeatHeader(); ?>"> lines
	<input type="submit" name="submit" value="change"> 
	Results per page: <input style="width: 32px;" type="text" name="limit" value="<?php echo $browser->getLimit(); ?>">
	<input type="submit" name="submit" value="change"> 
</form>

<!-- Shown table form (by description - set at import) -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	Current data (select by description): 
	<select name="logtable">
<?php
$tables = file("./db_tables.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($tables as $t) {
	$line = explode("|", $t, 2);
	if($line[0] == $logtable) {
		echo '<option value="'.$line[0].'" selected="selected">'.$line[1].'</option>';
	} else {
		echo '<option value="'.$line[0].'">'.$line[1].'</option>';
	}
}

?>
	</select>
	<input type="submit" name="submit" value="change"> 
</form>

<!-- Shown table form (by name) -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	Current data (select by table name): 
	<select name="logtable">
<?php
//$tables = file("./db_join_tables.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$result = mysql_query("SHOW TABLES;");
if (!$result) {
	echo 'Could not run query: ' . mysql_error();
	exit;
}

while ($row = mysql_fetch_row($result)) {
	if($row[0] == $logtable) {
		echo '<option value="'.$row[0].'" selected="selected">'.$row[0].'</option>';
	} else {
		echo '<option value="'.$row[0].'">'.$row[0].'</option>';
	}
}

?>
	</select>
	<input type="submit" name="submit" value="change"> 
</form>

<!-- Joined table selection form -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	<select name="joinmethod">
		<option value="NATURAL JOIN" <?php if($browser->joinmethod == "NATURAL JOIN") echo 'selected="selected"'; ?>>NATURAL JOIN</option>
		<option value="NATURAL LEFT OUTER JOIN" <?php if($browser->joinmethod == "NATURAL LEFT OUTER JOIN") echo 'selected="selected"'; ?>>NATURAL LEFT OUTER JOIN</option>
	</select>
	with table: 
	<select name="jointable">
<?php
$tables = file("./db_tables.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$result = mysql_query("SHOW TABLES;");
if (!$result) {
	echo 'Could not run query: ' . mysql_error();
	exit;
}

echo '<option value="NULL" selected="selected">NONE</option>';
while ($row = mysql_fetch_row($result)) {
	if($row[0] == $browser->jointable) {
		echo '<option value="'.$row[0].'" selected="selected">'.$row[0].'</option>';
	} else {
		echo '<option value="'.$row[0].'">'.$row[0].'</option>';
	}
}

?>
	</select>
	<input type="submit" name="submit" value="change">
</form>
<!-- Joined table selection form #2-->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	<select name="joinmethod2">
		<option value="NATURAL JOIN" <?php if($browser->joinmethod2 == "NATURAL JOIN") echo 'selected="selected"'; ?>>NATURAL JOIN</option>
		<option value="NATURAL LEFT OUTER JOIN" <?php if($browser->joinmethod2 == "NATURAL LEFT OUTER JOIN") echo 'selected="selected"'; ?>>NATURAL LEFT OUTER JOIN</option>
	</select>
	with table: 
	<select name="jointable2">
<?php
$tables = file("./db_tables.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$result = mysql_query("SHOW TABLES;");
if (!$result) {
	echo 'Could not run query: ' . mysql_error();
	exit;
}

echo '<option value="NULL" selected="selected">NONE</option>';
while ($row = mysql_fetch_row($result)) {
	if($row[0] == $browser->jointable2) {
		echo '<option value="'.$row[0].'" selected="selected">'.$row[0].'</option>';
	} else {
		echo '<option value="'.$row[0].'">'.$row[0].'</option>';
	}
}

?>
	</select>
	<input type="submit" name="submit" value="change">
</form>

<!-- COUNT()ed field selection form -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	COUNT(*) and GROUP BY field: 
	<select name="groupby">
		<option value="NONE">NONE</option>
<?php
	foreach($browser->table_fields as $field) {
		if($field == $browser->groupby)
			echo '<option value="'.$field.'" selected="selected">'.$field.'</option>';
		else
			echo '<option value="'.$field.'">'.$field.'</option>';
	}
?>
	</select>
	<input type="submit" name="submit" value="change">
</form>

</div>
<br>
<div style="border-style: solid; border-width: 1px; background-color: #EEF; padding: 8px;">
<!-- Search form (global filter generation) -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	<span style="font-weight: bold; font-size: 13pt;">Search</span>

	<select name="global_filter_field">
<?php 
	foreach($browser->table_fields as $field) {
		if($field == "filename")
			echo '<option value="'.$field.'" selected="selected">'.$field.'</option>';
		else
			echo '<option value="'.$field.'">'.$field.'</option>';
	}
?>
	</select>
	<select name="global_filter_operator">
		<option selected="selected" value="LIKE">LIKE</option>
		<option value="LIKE2">LIKE STRICT (no auto wildcard)</option>
		<option value="NOT LIKE">NOT LIKE</option>
		<option value="NOT LIKE2">NOT LIKE STRICT (no auto wildcard)</option>
		<option value="&gt;">&gt;</option>
		<option value="&gt;=">&gt;=</option>
		<option value="&lt;">&lt;</option>
		<option value="&lt;=">&lt;=</option>
		<option value="=">=</option>
		<option value="!=">!=</option>
	</select>
	<input style="width: 200px;" type="text" name="global_filter_value" value="">
	<select name="global_filter_value_field">
		<option value="" selected="selected">*text search (use text field)*</option>
<?php 
	foreach($browser->table_fields as $field) {
		echo '<option value="'.$field.'">'.$field.'</option>';
	}
?>
	</select>
	<input type="submit" name="global_filter_type" value="search"><br>(for LIKE queries, spaces are replaced by '%' wildcards by default; choose STRICT "no auto wildcard" modes to override)
</form>
</div><br>
<?php

/*********
 * Control display before results
 *********/
if(false) {
if ($browser->getStart() != 0)
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?start='.$prev.'">';
echo '&lt;prev';
if ($browser->getStart() != 0)
	echo '</a>';
echo ' -- ';
echo ' -- ';
if(isset($_SESSION[$scid.'_hide_garbage'])) {
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_garbage=false"> SHOW GLOBAL FILTER CONTROLS </a>';
}
else {
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_garbage=true"> HIDE GLOBAL FILTER CONTROLS </a>';
}
echo ' -- ';
echo ' -- ';

echo '<a href="#" onclick="javascript:document.getElementById(\'accessmask_div\').style.display=\'block\';"> SHOW FLAG FILTERS </a>';

echo ' -- ';
echo ' -- ';

echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true"> DUMP CSV </a>';
//echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;noheader=true"> (NO HEADER) </a>';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;hexa=true"> (HEXA</a> ';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;hexa=true&amp;resolve=true">+DISPLAYNAMES</a> ';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;resolve=true&amp;decode=true">+DECODEMASK) </a>';

echo ' -- ';
echo ' -- ';


if ($next != 0)
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?start='.$next.'">';
echo 'next&gt;';
if ($next != 0)
	echo '</a>';
echo "<br><br>\n";
}

// Result table display
$browser->printResult();

/*********
 * Control display after results (ugly copy/paste)
 *********/

if ($browser->getStart() != 0)
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?start='.$prev.'">';
echo '&lt;prev';
if ($browser->getStart() != 0)
	echo '</a>';
echo ' -- ';
echo ' -- ';
if(isset($_SESSION[$scid.'_hide_garbage'])) {
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_garbage=false"> SHOW GLOBAL FILTER CONTROLS </a>';
}
else {
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?hide_garbage=true"> HIDE GLOBAL FILTER CONTROLS </a>';
}
echo ' -- ';
echo ' -- ';

echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true"> DUMP CSV </a>';
//echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;noheader=true"> (NO HEADER) </a>';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;hexa=true"> (HEXA</a> ';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;hexa=true&amp;resolve=true">+DISPLAYNAMES</a> ';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?csvdump=true&amp;resolve=true&amp;decode=true">+DECODEMASK) </a>';

echo ' -- ';
echo ' -- ';

if ($next != 0)
	echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?start='.$next.'">';
echo 'next&gt;';
if ($next != 0)
	echo '</a>';
echo "<br><br>\n";


/* EXECUTED QUERY
echo "<pre>";
echo $browser->executed;
echo "</pre>";
*/


/*********
 * Active quick filters
 *********/


echo '<table style="border-style: solid; border-width: 1px; background-color: #FEE;"><tr><td>';

echo "<h2>Fields</h2>";
echo $browser->printFields();

echo '</td>';

/*********
 * Active global filters
 *********/


echo '<td align="center">';

echo "<h2>Quick filter</h2>";
echo $browser->getQuickFilterStr() . "<br><br>";
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?clear_quick_filter=true">CLEAR FILTER</a> - ';
echo '<a href="'.$proto.'://'.$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"].'?save_quick_filter=true">SAVE AS GLOBAL</a>';

echo '</td></tr><tr><td colspan="2">';
echo "<h2>Global filters</h2>";
echo $browser->printGlobalFilters();
echo '</td></tr></table>';


/*********
 * Auxiliary tables display (GUID...)
 *********/

/*
echo "<h2>ObjectType GUID descriptions</h2>";
$browser->printGUID();

echo "<h2>InheritedObjectType GUID descriptions</h2>";
$browser->printGUIDiot();
*/
?>
<div style="border-style: solid; border-width: 1px; background-color: #FEE; padding: 8px;">
<h4>Sessions</h4>

<!-- Session saving form -->
<form action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>" method="GET">
Session description: <input type="text" name="save"> <input type="submit" value="save"></form>

<!-- Session restore form -->
<form method="GET" action="<?php echo $proto; ?>://<?php echo $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]; ?>">
	Restore saved session: 
	<select name="restore">
<?php
$tables = file("./db_sessions.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($tables as $t) {
	$line = explode("|", $t, 2);
	echo '<option value="'.$line[0].'">'.$line[1].'</option>';
}

?>
	</select>
	<input type="submit" value="Restore"> 
	<input type="submit" name="csvdump" value="Dump as CSV">
</form>


</div>
<?php
echo '<br><div style="width: 512px;">';
echo "</div>";
?>
</body>
</html>
