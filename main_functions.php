<?php
set_time_limit(0);
@require_once('msql_dumper.php');

function extractPackage($siteName, $packageName)
{
	$zip = new ZipArchive;
	$destPath = $_SERVER['DOCUMENT_ROOT'].'/'.$siteName.'/';
	if ($zip->open($packageName.'.zip') === TRUE) {
		$zip->extractTo(str_replace('//','/',$destPath));
		$zip->close();
		return true;
	} else {
		return false;
	}
}
function getConfig($siteName)
{
	require_once($_SERVER['DOCUMENT_ROOT'].'/'.$siteName.'/configuration.php');
	
	$jConf = new JConfig();
	return $jConf;
}

function xcreatePackage($siteName, $destPath)
{
	$folder = $_SERVER['DOCUMENT_ROOT'].'/'.$siteName.'/';
	$output = $destPath.'/'.$siteName.".zip";
	$zip = new ZipArchive();
	
	if ($zip->open($output, ZIPARCHIVE::CREATE) !== TRUE) {
		die ("Unable to create Archive");
	}
	
	$all = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
	 
	foreach ($all as $f=>$value) {
	   $zip->addFile($f, str_replace($folder,'',str_replace("\\",'/',$f))) or die ("ERROR: Unable to add file: $f");
	}
	
	if($zip->close())
		return true;
	else
		return false;
}

function zipPackage($siteName, $destPath)
{
	$folder = $siteName.'/';
	$output = $destPath.'/'.$siteName.".zip";
	$zip = new ZipArchive();
	
	if ($zip->open($output, ZIPARCHIVE::CREATE) !== TRUE) {
		die ("Unable to create Archive");
	}
	
	$all = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
	 
	foreach ($all as $f=>$value) {
	   $zip->addFile($f, str_replace($folder,'',str_replace("\\",'/',$f))) or die ("ERROR: Unable to add file: $f");
	}
	
	if($zip->close())
		return true;
	else
		return false;
}

function dumpDatabase($host,$user,$pass,$dbName, $preFix, $destination)
{
	$suffix = $preFix;
	$msq = new MySQLDump();
	$msq->connect($host,$user,$pass,$dbName, true, $suffix);
	$msq->dumpdb();
	$msq->saveOutput($destination);
}

function makePackage($sitename)
{
	$rootPath = $_SERVER['DOCUMENT_ROOT'].'/'.$sitename;
	$data = $sitename.'/installation/sql/mysql/joomla';
	
	$config = getConfig($sitename);
	
	xCopy($rootPath, $sitename);
	xCopy('installer_files/installation',$sitename.'/installation');
	
	dumpDatabase($config->host,$config->user,$config->password,$config->db,trim($config->dbprefix,'_'),$data);
	unlink($sitename.'/configuration.php');
	if(zipPackage($sitename,'packages'))
	{
		lc_delete($sitename);
		return true;
	}
	else
	{
		return false;
	}
}

function deleteProject($sitename)
{
	$config = getConfig($sitename);
	@require_once('db.class.php');
	$rootPath = $_SERVER['DOCUMENT_ROOT'].'/'.$sitename;
	
	$dbase = new db_class();

	if(!$dbase->connect($config->host,$config->user, $config->password, $config->db,false, false))
	{
		if(!$dbase->connect($config->host,$config->user, $config->password, $config->db,true, false))
			$dbase->print_last_error(false);
	}
	
	chmod($rootPath,'777');
	
	$dbase->drop_db($config->db);
	lc_delete($rootPath);
	deleteConfig($sitename);
	
	rmdir($rootPath);
	
	return true;
}

function xCopy($src,$dst) {
    $dir = opendir($src);
	$result = ($dir === false)?false:true;
	if($result !== false)
	{
		$result = @mkdir($dst);
		if($result === true)
		{
			while(false !== ( $file = readdir($dir)))
			{
				if (( $file != '.' ) && ( $file != '..' ) && $result) {
					if ( is_dir($src . '/' . $file) ) {
						$result = xCopy($src . '/' . $file,$dst . '/' . $file);
					}
					else {
						$result = copy($src . '/' . $file,$dst . '/' . $file);
					}
				}
			}
			closedir($dir);
			return $result;
		}
	}
}

function rc_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                rc_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function lc_delete($targ) {
  if(is_dir($targ)){
    $files = glob( $targ . '*', GLOB_MARK );
    foreach( $files as $file )
      lc_delete( $file );
    rmdir( $targ );
  }
  else
    unlink( $targ );
}

function saveConfig($configData = array(),$filename = '', $destination = '')
{
	if(!file_exists($filename))
	{
		die('Configuration file does not exist');
	}
	
	$trimmed = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$f = 0;
	
	$out = '';
	$tmp = '';
	
	foreach($trimmed as $line)
	{
		foreach($configData as $k => $v)
		{
			$ln = '$'.$k.' = ';
			$pos = strpos($line,$ln);
			
			if($pos > 0)
			{
				$tm = substr($line, 0, $pos+strlen($ln));
				$tmp = "\tpublic $ln'$v';";
				break;
			}
			else
			{
				$tmp = $line;
			}
		}
		
		$out .= $tmp."\r\n";
	}
	
	$handle = fopen($destination,'w+');
	fwrite($handle,trim($out,"\r\n"));
	fclose($handle);
	
	//return $out;
}

function createTmpSQL($filename, $prefix, $tmp_name = 'tmp.sql')
{
	if(!file_exists($filename))
	{
		die('SQL file does not exist');
	}
	
	$trimmed = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$f = 0;
	
	$out = '';
	$tmp = '';
	
	foreach($trimmed as $line)
	{
		$tmp = str_replace('#__',$prefix,$line);
		$out .= $tmp."\r\n";
	}
	
	$handle = fopen($tmp_name,'w+');
	fwrite($handle,trim($out,"\r\n"));
	fclose($handle);
}

function createSite($sitename,$config = array(), $packageFile='fresh',$sampledata = false)
{
	$rootPath = $_SERVER['DOCUMENT_ROOT'].'/'.$sitename.'/';
	
	extractPackage($sitename,$packageFile) or die('Cannot Extract File');
	saveConfig($config,'configuration.php',$rootPath.'configuration.php');
	loadSQLData($sitename, $config, $sampledata);
	lc_delete($rootPath.'installation');
	saveInstall($sitename);
	
	return true;
}

function loadSQLData($sitename, $dbConfig=array(), $sample = false)
{
	$rootPath = $_SERVER['DOCUMENT_ROOT'].'/'.$sitename.'/';
	$data = $rootPath.'installation/sql/mysql/joomla.sql';
	$sample = $rootPath.'installation/sql/mysql/sample_data.sql';
	
	//$data = 'installer_files/installation/sql/mysql/joomla.sql';
	
	@require_once('db.class.php');

	$dbase = new db_class();

	if(!$dbase->connect($dbConfig['host'],$dbConfig['user'], $dbConfig['password'], $dbConfig['db'],false, false))
	{
		if(!$dbase->connect($dbConfig['host'],$dbConfig['user'], $dbConfig['password'], $dbConfig['db'],true, false))
			$dbase->print_last_error(false);
	}
	
	$prefix = $dbConfig['dbprefix'];
	
	createTmpSQL($data,$prefix);
	$dbase->execute_file('tmp.sql');
	createAdmin($dbConfig['admin'],$dbConfig['email'],$prefix,$dbase);
	
	if($sample)
	{
		createTmpSQL($sample,$prefix,'tmp_sample.sql');
		$dbase->execute_file('tmp_sample.sql');
	}

	unlink('tmp.sql');
	unlink('tmp_sample.sql');
}

function createAdmin($username,$email,$prefix,$dbase)
{
	$sql = "INSERT INTO `".$prefix."users` (`name`, `username`, `email`,  `password`,  `block`,  `sendEmail`,  `registerDate`, `lastvisitDate`, `activation`, `params`, `lastResetTime`, `resetCount`) VALUES ('Super Admin', '$username','$email','433903e0a9d6a712e00251e44d29bf87:UJ0b9J5fufL3FKfCc0TLsYJBh2PFULvT', '0','1','2013-04-12 20:20:31','0000-00-00 00:00:00', '0','','0000-00-00 00:00:00','0');";
	$uid = $dbase->select($sql);
	$sql1 = "INSERT INTO `".$prefix."user_usergroup_map` (`user_id`,`group_id`) VALUES ($uid,8);";
	$dbase->select($sql1);
}

function getRandomString($length)
{
	$c = "abcdefghijklmnopqrstuvwxyzzywvutsrqponmlkjihgfedcb";
	$rsl = strlen($c);
	$string = substr(rand(100, 999),4,15);

	for ($p = 0; $p < $length; $p++)
	{
		$string .= $c[mt_rand(0, strlen($c) - 1)];
	}
	return $string;
}
	
function saveInstall($sitename)
{
	$config = 'config.idb';
	$out = json_encode(array('name'=>$sitename,'url'=>'http://localhost/'.$sitename.'/'));
	
	$trimmed = file($config, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$f = 0;
	
	$found = false;
	
	foreach($trimmed as $line)
	{
		if($out == $line)
		{
			$found = true;
		}
	}
	
	if(!$found)
	{
		$handle = fopen($config,'a');
		fwrite($handle,$out."\r\n");
		fclose($handle);
	}
}

function deleteConfig($sitename)
{
	$config = 'config.idb';
	$out = json_encode(array('name'=>$sitename,'url'=>'http://localhost/'.$sitename.'/'));
	if(!file_exists($config))
		return false;
	
	$trimmed = file($config, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$f = 0;
	
	$tmp = '';
	
	foreach($trimmed as $line)
	{
		if($out != $line)
		{
			$tmp = $line."\r\n";
		}
	}
	
	$handle = fopen('config.idb','w+');
	fwrite($handle,trim($tmp,"\r\n"));
	fclose($handle);
}

function get_zip_originalsize($filename) {
    $size = 0;
    $resource = zip_open($filename);
    while ($dir_resource = zip_read($resource)) {
        $size += zip_entry_filesize($dir_resource);
    }
    zip_close($resource);

    return $size;
}

function getPackageSize($file)
{
	$size = 0;
	if($file == 'fresh')
	{
		$size = get_zip_originalsize('fresh.zip');
	}
	else
	{
		$size = get_zip_originalsize('packages/'.$file.'.zip');
	}
	
	return number_format($size/(1024*1024),2);
}

function listPackages()
{
	$all = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('packages'));
	
	$packages = array(array('name' => 'Fresh Joomla', 'file'=>'fresh','size'=>getPackageSize('fresh')));
	
	$regex = new RegexIterator($all, '/^.+\.zip$/i', RecursiveRegexIterator::GET_MATCH);
	
	foreach ($regex as $f=>$value)
	{
		$n = str_replace('.zip','',basename($f));
		$packages[] = array('name' => ucfirst($n),'file' => $n, 'size' => getPackageSize($n));
	}
	
	return $packages;
}

function listInstalls()
{
	$installs = '';
	
	$config = 'config.idb';
		
	$trimmed = file($config, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	
	$i = 1;
	$c = 0;
	foreach($trimmed as $line)
	{
		$install = json_decode($line,true);
		$installs .= '<tr>
                <td width="6%">'.$i++.'</td>
                <td width="25%">'.ucfirst($install['name']).'</td>
                <td width="69%"><a href="'.$install['url'].'" target="_blank">[View Website]</a> <a href="'.$install['url'].'administrator/" target="_blank">[Admin Page]</a> <a href="?action=makepackage&name='.$install['name'].'">[Make Installer]</a> <a href="?action=deleteinstall&name='.$install['name'].'">[Delete Project]</a></td>
              </tr>
			  ';
		$c++;
	}
	
	if($c == 0)
	{
		$installs = '<tr>
                <td colspan="3">No Installation Project Found!</td>
              </tr>
			  ';
	}
	
	return $installs;
}

function cleanString($string)
{
	$s = preg_replace('/\%/','',trim($string,' '));
	$s = preg_replace('/\@/','',$s); 
	$s = preg_replace('/\&/','',$s); 
	$s = preg_replace('/\s[\s]+/',' ',$s);    // Strip off multiple spaces 
	$s = preg_replace('/[\s\W]+/','_',$s);    // Strip off spaces and non-alpha-numeric 
	$s = preg_replace('/^[\-]+/','',$s); // Strip off the starting hyphens 
	$s = preg_replace('/[\-]+$/','',$s); // // Strip off the ending hyphens
	
	return strtolower(trim($s,'_'));
}
?>