<?php
require_once("./include/membersite_config.php");

if(!$bcfvolunteers->CheckLogin())
{
    $bcfvolunteers->RedirectToURL("login.php");
    exit;
}

// Database Connection

require_once('config.inc.php');

$con=mysqli_connect("$dbhost","$dbuser","$dbpass","$dbname");
// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$sqltext = "SELECT 
id AS 'ID', 
name1 AS 'FirstName', 
name2 AS 'LastName', 
email AS 'EmailAddress', 
phone AS 'PhoneNumber', 
address1 AS 'Address', 
town AS 'City', 
county AS 'StateProvince', 
postcode AS 'PostCode', 
memno AS 'memno', 
IF(Sun11 = 'N','FALSE','TRUE') AS 'Sunday am',
IF(Sun12 = 'N','FALSE','TRUE') AS 'Sunday pm',
IF(Mon1 = 'N','FALSE','TRUE') AS 'Monday am',
IF(Mon2 = 'N','FALSE','TRUE') AS 'Monday pm',
IF(Tues1 = 'N','FALSE','TRUE') AS 'Tuesday am',
IF(Tues2 = 'N','FALSE','TRUE') AS 'Tuesday pm',
IF(Wed1 = 'N','FALSE','TRUE') AS 'Wednesday am',
IF(Wed2 = 'N','FALSE','TRUE') AS 'Wednesday Lunch',
IF(Wed3 = 'N','FALSE','TRUE') AS 'Wednesday pm',
IF(Thurs1 = 'N','FALSE','TRUE') AS 'Thursday Lunch',
IF(Thurs2 = 'N','FALSE','TRUE') AS 'Thursday pm',
IF(Thurs3 = 'N','FALSE','TRUE') AS 'Thursday Evepm',
IF(Fri1 = 'N','FALSE','TRUE') AS 'Friday Lunch',
IF(Fri2 = 'N','FALSE','TRUE') AS 'Friday pm',
IF(Fri3 = 'N','FALSE','TRUE') AS 'Friday Eve',
IF(Sat1 = 'N','FALSE','TRUE') AS 'Saturday Lunch',
IF(Sat2 = 'N','FALSE','TRUE') AS 'Saturday pm',
IF(Sat3 = 'N','FALSE','TRUE') AS 'Saturday Eve',
IF(Sun21 = 'N','FALSE','TRUE') AS 'Sunday (Set Down) am',
IF(Sun22 = 'N','FALSE','TRUE') AS 'Sunday (Set Down) pm',
choice1 AS 'choice1', 
choice2 AS 'choice2', 
other AS 'Notes', 
date_added AS 'comments', 
address2 AS 'Address2'  FROM `BeerFestVolunteers`";

//$sql = mysqli_query($con,"SELECT * FROM BeerFestVolunteers");
$sql = mysqli_query($con,$sqltext);

$output = "";
$columns_total = mysqli_num_fields($sql);

// Get The Field Name

while ($property = mysqli_fetch_field($sql)) {
    $output .= '"'.$property->name.'",';
}

$output .="\n";
 
// Get Records from the table

while ($row = mysqli_fetch_array($sql)) {
for ($i = 0; $i < $columns_total; $i++) {
$output .='"'.$row["$i"].'",';
}
$output .="\n";
}

// Download the file

$filename = "volunteers.csv";
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);

echo $output;

mysqli_free_result($sql);
mysqli_close($con);
exit;

?>