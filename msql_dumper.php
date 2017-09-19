<?php
/*
* Database MySQLDump Class File
* Copyright (c) 2009 by James Elliott
* James.d.Elliott@gmail.com
* Update by George O. Davis
* GNU General Public License v3 http://www.gnu.org/licenses/gpl.html
*/

$version1 = '1.4.2'; //This Scripts Version.

class MySQLDump {
	var $tables = array();
	var $columns = array();
	var $connected = false;
	var $output;
	var $droptableifexists = false;
	var $mysql_error;
	var $isJoomla = false;
	var $suffix = '';
	
function connect($host,$user,$pass,$db,$isJoomla = false,$suffix = '') {	
	$return = true;
	$conn = @mysql_connect($host,$user,$pass);
	if (!$conn) { $this->mysql_error = mysql_error(); $return = false; }
	$seldb = @mysql_select_db($db);
	if (!$conn) { $this->mysql_error = mysql_error();  $return = false; }
	$this->connected = $return;
	
	$this->isJoomla = $isJoomla;
	$this->suffix = $suffix;
	
	return $return;
}

function list_tables() {
	$return = true;
	if (!$this->connected) { $return = false; }
	$this->tables = array();
	$sql = mysql_query("SHOW TABLES");
	while ($row = mysql_fetch_array($sql)) {
		array_push($this->tables,$row[0]);
	}
	return $return;
}

function list_values($tablename) {
	$sql = mysql_query("SELECT * FROM $tablename");
	$this->output .= "\n\n-- Dumping data for table: $tablename\n\n";
	while ($row = mysql_fetch_array($sql)) {
		$broj_polja = count($row) / 2;
		if($this->isJoomla && $this->suffix != '')
			$table = str_replace($this->suffix,'#_',$tablename);
		else
			$table = $tablename;
		
		$this->output .= "INSERT INTO `$table` VALUES(";
		
		$buffer = '';
		for ($i=0;$i < $broj_polja;$i++) {
			$vrednost = $row[$i];
			if (!is_integer($vrednost)) { $vrednost = "'".addslashes($vrednost)."'"; } 
			$buffer .= $vrednost.', ';
		}
		$buffer = substr($buffer,0,count($buffer)-3);
		$this->output .= $buffer . ");\n";
	}	
}

function listValues($tablename) {
	$sql = mysql_query("SELECT * FROM $tablename");
	
	
	if($this->isJoomla && $this->suffix != '')
		$table = str_replace($this->suffix,'#_',$tablename);
	else
		$table = $tablename;
		
	
	$this->getColumns($tablename);
	
	$cols = $this->columns;
	$col = '(';
	
	foreach($cols as $cl){$col.= "`".$cl['name']."`, ";}
	
	$col = trim($col,', ').')';
	
	$insert = "INSERT INTO `$table` $col VALUES(";
	
	$counter = 0;
	$out = '';
	$out .= "\n--\n-- Dumping data for table: $table\n--\n";
	
	while ($row = mysql_fetch_array($sql)) {
		$broj_polja = count($row) / 2;
		
		if($counter == 0)
		{
			$out .= $insert;
		}
		elseif($counter%3 == 0)
		{
			$out = trim($out,",\n").";\n".$insert;
		}
		else
		{
			$out .= "(";
		}
		$counter++;
		
		$buffer = '';
		for ($i=0;$i < $broj_polja;$i++) {
			$vrednost = $row[$i];
			if(substr($this->columns[$i]['type'],0,3) != 'int')
			{
				if (!is_integer($vrednost)) { $vrednost = "'".addslashes($vrednost)."'"; } 
			}
			$buffer .= $vrednost.', ';
		}
		$buffer = substr($buffer,0,count($buffer)-3);
		$out .= $buffer . "),\n";
	}
	
	if($tablename != $this->suffix.'_session' &&$tablename != $this->suffix.'_users' && $tablename != $this->suffix.'_core_acl_aro' && $tablename != $this->suffix.'_core_acl_groups_aro_map')
	{
		$this->output .= str_replace('--;','--',trim($out,",\n").";\n");
	}
}

function dump_table($tablename, $clear = false) {
	if($clear)
		$this->output = "";
		
	//$this->get_table_structure($tablename);	
	$this->getTableStructure($tablename);	
	$this->listValues($tablename);
}

function get_table_structure($tablename) {
	$this->output .= "\n\n-- Dumping structure for table: $tablename\n\n";
	if ($this->droptableifexists) { $this->output .= "DROP TABLE IF EXISTS `$tablename`;\nCREATE TABLE `$tablename` (\n"; }
		else { $this->output .= "CREATE TABLE `$tablename` (\n"; }
	$sql = mysql_query("DESCRIBE $tablename");
	$this->fields = array();
	while ($row = mysql_fetch_array($sql)) {
		$name = $row[0];
		$type = $row[1];
		$null = $row[2];
		if (empty($null)) { $null = "NOT NULL"; }
		$key = $row[3];
		if ($key == "PRI") { $primary = $name; }
		$default = $row[4];
		$extra = $row[5];
		if ($extra !== "") { $extra .= ' '; }
		$this->output .= "  `$name` $type $null $extra,\n";
	}
	$this->output .= "  PRIMARY KEY  (`$primary`)\n);\n";
}

function getColumns($tablename) {
	$sql = mysql_query("DESCRIBE $tablename");
	$this->columns = array();
	while ($row = mysql_fetch_array($sql)) {
		$this->columns[] = array('name'=>$row[0],'type'=>$row[1]);
	}
}

function getTableStructure($tablename)
{
	
	if($this->isJoomla && $this->suffix != '')
		$table = str_replace($this->suffix,'#_',$tablename);
	else
		$table = $tablename;
		
	$this->output .= "\n--\n-- Dumping structure for table: $table\n--\n";
	if ($this->droptableifexists)
	{
		$this->output .= ($this->isJoomla && $this->suffix != '')?str_replace($this->suffix,'#_',"DROP TABLE IF EXISTS `$tablename`;\n"):"DROP TABLE IF EXISTS `$tablename`;\n";
	}
	
	$sql = mysql_query("SHOW CREATE TABLE $tablename");
	$result = mysql_fetch_array($sql);
	
	//$this->output .= "\n".preg_replace('/['.$this->suffix.']/', '#_', $result['Create Table']).";\n";
	
	if($this->isJoomla && $this->suffix != '')
		$this->output .= "\n".str_replace($this->suffix,'#_',$result['Create Table']).";\r\n";
	else
		$this->output .= "\n".$result['Create Table'].";\r\n";
}

function dumpdb()
{
	$this->list_tables();
	foreach($this->tables as $table)
	{
		$this->dump_table($table);
	}
}

function saveOutput($filename)
{
	$content = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";'."\r\n";

	$handle = fopen($filename.'.sql','w+');
	fwrite($handle,$content.$this->output);
	fclose($handle);
}
}
?>
