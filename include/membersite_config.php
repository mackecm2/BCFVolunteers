<?PHP
require_once("./include/fg_membersite.php");
require_once('./config.inc.php');

$bcfvolunteers = new BCFVolunteers();

//Provide your site name here  
$bcfvolunteers->SetFestivalName($festival_name);

//Provide the email address where you want to get notifications
$bcfvolunteers->SetAdminEmail($admin_email);

//Provide the name to go in the "from" in the email notifications
$bcfvolunteers->SetFromName($from_name);

//Provide the name to the mail host server
$bcfvolunteers->SetMailHost($mail_host);

//Provide the name of the homepage to go after logging in
$bcfvolunteers->SetHomePage($home_page);

//Initialise database connection
$bcfvolunteers->InitDB($dbhost,$dbuser,$dbpass,$dbname,'bcf_users','beerfestvolunteers');

//For better security. Get a random string from this link: http://tinyurl.com/randstr
// and put it here
$bcfvolunteers->SetRandomKey('0IsHrT8ElEPrTmk');
?>