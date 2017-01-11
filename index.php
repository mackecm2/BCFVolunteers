<?PHP
require_once("./include/membersite_config.php");

if(isset($_POST['submitted']))
{
   if($bcfvolunteers->Login())
   {
        $bcfvolunteers->RedirectToURL("volunteers.php");
   }
}
$bcfvolunteers->RedirectToURL("login.php");
?>