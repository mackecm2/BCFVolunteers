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

$sqltext = "SELECT choice1, COUNT( IF( Sun11 = 'Y', 1, NULL ) ) as Sun11Count, COUNT( IF( Sun12 = 'Y', 1, NULL ) ) as Sun12Count,
COUNT( IF( Mon1 = 'Y', 1, NULL ) ) as Mon1Count, COUNT( IF( Mon2 = 'Y', 1, NULL ) ) as Mon2Count,
COUNT( IF( Tues1 = 'Y', 1, NULL ) ) as Tues1Count, COUNT( IF( Tues2 = 'Y', 1, NULL ) ) as Tues2Count,
COUNT( IF( Wed1 = 'Y', 1, NULL ) ) as Wed1Count, COUNT( IF( Wed2 = 'Y', 1, NULL ) ) as Wed2Count,
COUNT( IF( Wed3 = 'Y', 1, NULL ) ) as Wed3Count, COUNT( IF( Thurs1 = 'Y', 1, NULL ) ) as Thurs1Count,
COUNT( IF( Thurs2 = 'Y', 1, NULL ) ) as Thurs2Count, COUNT( IF( Thurs3 = 'Y', 1, NULL ) ) as Thurs3Count,
COUNT( IF( Fri1 = 'Y', 1, NULL ) ) as Fri1Count, COUNT( IF( Fri2 = 'Y', 1, NULL ) ) as Fri2Count,
COUNT( IF( Fri3 = 'Y', 1, NULL ) ) as Fri3Count, COUNT( IF( Sat1 = 'Y', 1, NULL ) ) as Sat1Count,
COUNT( IF( Sat2 = 'Y', 1, NULL ) ) as Sat2Count, COUNT( IF( Sat3 = 'Y', 1, NULL ) ) as Sat3Count,
COUNT( IF( Sun21 = 'Y', 1, NULL ) ) as Sun21Count,
COUNT( IF( Sun22 = 'Y', 1, NULL ) ) as Sun22Count FROM  `BeerFestVolunteers`
GROUP BY choice1";

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

$filename = "volunteer_rota.csv";
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename='.$filename);

echo $output;
mysqli_free_result($con);
mysqli_close($con);
exit;

?>