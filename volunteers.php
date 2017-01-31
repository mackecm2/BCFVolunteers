<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
require_once("./include/membersite_config.php");

if(!$bcfvolunteers->CheckLogin())
{
    $bcfvolunteers->RedirectToURL("login.php");
    exit;
}

?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="style/volunteers.css" />
    <title>Bedford Beer Festival Volunteers</title>
</head>
<body>
<?php

$con=mysqli_connect("$dbhost","$dbuser","$dbpass","$dbname");
// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

if ($result = mysqli_query($con,"SELECT *,date_added AS date_registered FROM beerfestvolunteers")) {
    $row_cnt = mysqli_num_rows($result);
} else {
    $row_cnt = 0;
}



// print out count and logout button at the top
echo "<div class='divTableHeadRow'>";
echo "<div class='divTableHead'>" . "<span style=\"background-color: #FFFF00\">Number of Volunteers so far: $row_cnt </span>" . "</div>";
echo "<div class='divTableHead'>" . "Change Password
    <form action=\"change-pwd.php\" method=\"post\"><input type=\"image\" src=\"images/general/change-pwd-button.png\" name=\"submit\"></form>
        " . "</div>";
echo "<div class='divTableHead'>" . "Logout
    <form action=\"logout.php\" method=\"post\"><input type=\"image\" src=\"images/general/logout-button.png\" name=\"submit\"></form>
        " . "</div>";
echo "</div>";
echo "<br />";
echo "<div class='divTableHeadRow'>";

// print out the data titles (name, address, etc)
foreach($data_array as $label => $title) {
    echo "<div class='divTableHead'>".$title."</div>";
}

// print out the time slot titles
foreach($days_array as $label => $time) {
    echo "<div class='divTableHead'>".$time."</div>";
}

echo "<div class='divTableHead'>Date Added</div>
    </div>";

$dayfields = array_keys($days_array);
$total = array_fill_keys($dayfields,0);

if ($row_cnt > 0) {
    while($row = mysqli_fetch_array($result)) {
        if (array_key_exists('phone', $data_array)) {
            $row['phone'] = (preg_match('/\s/',$row['phone']) > 0) ? $row['phone'] : substr_replace($row['phone']," ", 5, -strlen($row['phone']));
        }

        echo "<div class='divTableRow'>";

        // print out the volunteer data
        foreach($data_array as $label => $title) {
            echo "<div class='divTableCell'>" . $row[$label] . "</div>";
        }

        // print out the timeslots they've volunteered for
        foreach($dayfields as $field)
        {
          echo "<div class='divTableCell'>" . $row[$field] . "</div>";
          if ($row[$field] == "Y") { $total[$field]++; } 
        }

        echo "<div class='divTableCell'>" . $row['date_registered'] . "</div>";
        echo "</div>";
    }
}


echo "<div class='divTableHeadRow'>";
// print out the data titles (name, address, etc)
foreach($data_array as $label => $title) {
    echo "<div class='divTableHead'>".$title."</div>";
}

// print out the time slot titles
foreach($days_array as $label => $time) {
    echo "<div class='divTableHead'>".$time."</div>";
}

echo "<div class='divTableHead'>Date Added</div>
    </div>";

// time to print out the totals
echo "<div class='divTableRow'>";

foreach($data_array as $label => $title) {
    echo "<div class='divTableCell'>" . ($title == "Other" ? "totals" : "" ) . "</div>";
}

// print out the timeslot totals
foreach($dayfields as $field)
{
  echo "<div class='divTableCell'>" . $total[$field] . "</div>";
}
echo "<div class='divTableCell'></div>";
echo "</div>";
echo "<br />";

echo "<div class='divTableHeadRow'>";
echo "<div class='divTableCell'>" . "<span style=\"background-color: #FFFF00\">Number of Volunteers so far: $row_cnt </span>" . "</div>";
echo "<div class='divTableCell'>" . "Download CSV
    <form action=\"csvdownload.php\" method=\"post\"><input type=\"image\" src=\"images/general/download-button.png\" name=\"submit\"></form></th>
 " . "</div>";
echo "<div class='divTableCell'>" . "Staff Counts:
        <form action=\"csvrotadownload.php\" method=\"post\"><input type=\"image\" src=\"images/general/download-button.png\" name=\"submit\"></form></th>
       " . "</div>";
echo "<div class='divTableCell'>" . "Logout
    <form action=\"logout.php\" method=\"post\"><input type=\"image\" src=\"images/general/logout-button.png\" name=\"submit\"></form>
        " . "</div>";
echo "</div>";

mysqli_close($con);
?>
</body>
</html>