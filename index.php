<?php
@require_once('main_functions.php');

if(isset($_REQUEST['action']))
{
	$action = $_REQUEST['action'];
	switch($action)
	{
		case 'InstallPackage':
			extract($_POST);
			
			$rootPath = str_replace("\\","\\\\",realpath($_SERVER['DOCUMENT_ROOT']));
			$prefix = getRandomString(3).'_';
			$db = getRandomString(5);

			$tmp = $rootPath.'/'.$site_name.'/tmp';
			$log = $rootPath.'/'.$site_name.'/log';
			
			$config = array('sitename'=>$sitename,'host'=>'localhost','user'=>'admin','password'=>'digihub','db'=>$db,'dbprefix'=>$prefix,'mailfrom'=>$email,'fromname'=>'Super Admin','log_path'=>$log,'tmp_path'=>$tmp,'admin'=>$username,'email'=>$email);
			
			$sample = (isset($sample_data))?true:false;
			
			$cSite = '';
			if($package == 'fresh')
				$cSite = createSite(cleanString($site_name),$config,'fresh',$sample);
			else
				$cSite = createSite(cleanString($sitename),$config,'packages/'.$package,$sample);
			
			if($cSite)
				header('location:index.php');
			else
				echo 'Unable to Create Site';
			
			exit();
			break;
		case 'makepackage':
			if(makePackage($_GET['name']))
			{
				$msg = 'Package Created, packages can be found in the packages directory';
				header('location:index.php?msg='.$msg);
			}
			else
			{
				$msg = 'Unable to create Package';
				header('location:index.php?msg='.$msg);
			}
			break;
		case 'deleteinstall':
			if(deleteProject($_GET['name']))
			{
				$msg = 'Project Deleted successfully';
				header('location:index.php?msg='.$msg);
			}
			else
			{
				$msg = 'Unable to delete project';
				header('location:index.php?msg='.$msg);
			}
			break;
		default:
			break;
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Joomla Package Manager</title>
<script src="js/jquery-1.7.1.min.js"></script>
<script src="js/SpryTabbedPanels.js" type="text/javascript"></script>
<script src="js/SpryValidationTextField.js" type="text/javascript"></script>
<link href="css/SpryTabbedPanels.css" rel="stylesheet" type="text/css">
<style type="text/css">
.mid_wrapper {width:700px; min-height: 400px; margin:auto}
body,td,th {
	font-family: Tahoma, Geneva, sans-serif;
	font-size: medium;
}

input {
	border: 1px solid #099;
}
</style>
<link href="css/SpryValidationTextField.css" rel="stylesheet" type="text/css">
<style type="text/css">
a:link {
	color: #099;
	text-decoration: none;
}
a:visited {
	text-decoration: none;
	color: #099;
}
a:hover {
	text-decoration: none;
	color: #000;
}
a:active {
	text-decoration: none;
	color: #099;
}
</style>
</head>

<body>
<div class="mid_wrapper">
  <header>
        <div>
            <img src="img/Joomla_Logo_Slogan.png" width="196" height="48" alt="Joomla">
      <span style="font-size:36px; font-stretch:extra-expanded; font-weight:bold">Package Manager</span>
      </div>
  </header><br>
  <div id="TabbedPanels1" class="TabbedPanels">
      <ul class="TabbedPanelsTabGroup">
        <li class="TabbedPanelsTab" tabindex="0">PROJECT LIST</li>
        <li class="TabbedPanelsTab" tabindex="0">CREATE NEW JOOMLA PROJECT</li>
      </ul>
      <div class="TabbedPanelsContentGroup">
        <div class="TabbedPanelsContent">
          <div class="project_wrapper" style="min-height:400px; margin: 5px; background-color:#fff; color:#000">
            <table width="100%" border="0" cellpadding="5">
              <?php echo listInstalls();?>
            </table>
          </div>
        </div>
        <div class="TabbedPanelsContent">
          <div class="install_wrapper" style="min-height:400px; margin: 5px; background-color:#fff; color:#000;">
          	<div class="intro" style="font-size: 12px">Joomla! - the dynamic portal engine and content management system<br>
       	      <strong>Version:</strong> 3.0.3<br>
       	    <strong>Install Size:</strong> <span class="i_size">19.37 MB</span><br>
       	    <strong>Official Site:</strong> <a href="http://www.joomla.org/">http://www.joomla.org/ </a></div>
            <div style="margin-top: 10px;">
              <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
                <table width="100%" border="0" cellpadding="5" style="font-size: small !important">
                  <tr>
                    <td colspan="3" align="center"><strong>Install Joomla</strong></td>
                  </tr>
                  <tr>
                    <td colspan="3" align="center">Application URL (where you will find the app in your browser):</td>
                   </tr>
                   <tr>
                     <td colspan="3" align="center" style="border-bottom: thin dotted #000">
                      <label for="site_name">http://localhost/</label>
                      <span id="sprytextfield2">
                      <input type="text" name="site_name" id="site_name">
                     <span class="textfieldRequiredMsg">Site name is required.</span></span>/ </td>
                  </tr>
                  <tr>
                    <td width="38%">Joomla Package to Install</td>
                    <td width="4%">:</td>
                    <td width="58%">
                      <select name="package" id="package">
                       <?php
						 $packs = listPackages();
                         foreach($packs as $pack)
						 {
							echo '<option value="'.$pack['file'].'" onClick="$(\'.i_size\').html('.$pack['size'].')">'.$pack['name'].'</option>'."\n";
						 }
                       ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td>Site Name</td>
                    <td>:</td>
                    <td><span id="sitename_f">
                      <input type="text" name="sitename" id="sitename">
                    <span class="textfieldRequiredMsg">Site Title is required.</span></span></td>
                  </tr>
                  <tr>
                    <td>Admin Username</td>
                    <td>:</td>
                    <td><span id="sprytextfield5">
                      <input type="text" name="username" id="username">
                    <span class="textfieldRequiredMsg">Admin Username is required.</span></span></td>
                  </tr>
                  <tr>
                    <td>Admin Email</td>
                    <td>:</td>
                    <td><span id="sprytextfield4">
                      <input type="text" name="email" id="email">
                    <span class="textfieldRequiredMsg">Admin email is required.</span></span></td>
                  </tr>
                  <tr>
                    <td>Install Sample Data</td>
                    <td>:</td>
                    <td><input type="checkbox" name="sample_data" value="true" id="sample_data"></td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>Note: Admin default password = admin</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><input type="submit" name="installNow" id="installNow" value="Install Now" style="border: 1px solid #0CC"><input type="hidden" value="InstallPackage" name="action"></td>
                  </tr>
                </table>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
<script type="text/javascript">
var TabbedPanels1 = new Spry.Widget.TabbedPanels("TabbedPanels1");
var sprytextfield1 = new Spry.Widget.ValidationTextField("sprytextfield1");
var sprytextfield2 = new Spry.Widget.ValidationTextField("sprytextfield2");
var sprytextfield3 = new Spry.Widget.ValidationTextField("sitename_f");
var sprytextfield4 = new Spry.Widget.ValidationTextField("sprytextfield4");
var sprytextfield5 = new Spry.Widget.ValidationTextField("sprytextfield5");
<?php
if(isset($_GET['msg']))
{
	echo 'alert("'.$_GET['msg'].'")';
}
?>
</script>
</body>
</html>