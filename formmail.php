<?php

require_once("include/membersite_config.php");

function starts_with_upper($str) {
    $chr = mb_substr ($str, 0, 1, "UTF-8");
    return mb_strtolower($chr, "UTF-8") != $chr;
}

if (($_POST['name1'] == "" && $_POST['name2'] == "") OR $_POST['email'] == "") {
    echo "You have not entered your name and/or email address. Please try again.<br />";
    exit();
}

$festival_start = strtotime($festival_start_date);
$festival_end = strtotime($festival_start_date. "+4 days");
$today = strtotime(date('d-m-Y'));

if ($today >= $festival_start) {
    if ($today > $festival_end) {
        echo "The Bedford Beer Festival is now over. See you again next year!<br />";
    } else {
        echo "The Bedford Beer Festival has started! Visit the CAMRA office at the festival venue to volunteer.<br />";
    }
    exit();
} 

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (mysqli_connect_errno()) {
    echo "Connect failed: %s<br />".mysqli_connect_error();
    exit();
}
$outputfields = array();
unset($data_array["id"]);
$datafields = array_keys($data_array);
$dayfields = array_keys($days_array);
$ucfields = array('name1', 'name2', 'address1', 'address2', 'town', 'county');

$sql = "INSERT INTO beerfestvolunteers (";
$sqlvals = "VALUES ( ";

$query = "SELECT * FROM beerfestvolunteers WHERE name1 LIKE '$_POST[name1]' AND name2 LIKE '$_POST[name2]'";
$results = mysqli_query($conn, $query);
$numResults = mysqli_num_rows($results);
if ($numResults > 0) {
    echo "<head>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"style/volunteers.css\" />";
    echo "</head><body>";
    echo "You have already entered your details on the database. Please send an email to beerfestival@northbedscamra.org.uk saying what you want to change.<br />";
    echo "<br />This is what we currently have for you on file:<br />";
    echo "<br /><div class='divTableHeadRow'>";

    foreach($datafields as $label => $title) {
        echo "<div class='divTableCell'>".$title."</div>";
    }

    echo "</div>";
    while($row = mysqli_fetch_array($results)) {
        if (array_key_exists('phone', $data_array)) {
            $row['phone'] = (preg_match('/\s/',$row['phone']) > 0) ? $row['phone'] : substr_replace($row['phone']," ", 5, -strlen($row['phone']));
        }
        echo "<div class='divTableRow'>";

        foreach($data_array as $label => $title) {
            echo "<div class='divTableCell'>".$row[$label]."</div>";
        }

        echo "</div>";
        echo "<br />";

        echo "<br /><table><tbody><tr>
        <th colspan=2>Sunday</th>
        <th colspan=2>Monday</th>
        <th colspan=2>Tuesday</th>
        <th colspan=3>Wednesday</th>
        <th colspan=3>Thursday</th>
        <th colspan=3>Friday</th>
        <th colspan=3>Saturday</th>
        <th colspan=2>Sunday</th>
        </tr><tr>
        <th colspan=7>Set Up</th>
        <th colspan=11>Festival</th>
        <th colspan=2>Set Down</th>
        </tr><tr>";

        // print out the time slot titles
        foreach($days_array as $label => $time) {
            echo "<th>".$time."</th>";
        }

        echo "</tr>";
        echo "<tr>";
        
        foreach($dayfields as $field)
        {
            echo "<td>".$row[$field]."</td>";
        }

        echo "</tr>";
    }
    echo "</tbody><tfoot>";		
    echo "</tfoot></table>";
    mysqli_close($conn);
    exit();
} else {
   // no data from query, so new volunteer
    foreach($datafields as $field)
    {
        $outputfield = mysqli_real_escape_string($conn,trim($_POST[$field]));
        $outputfield = rtrim($outputfield, ','); 
        $outputfields[$field] = $outputfield;
        $sql .=  "$field".",";

        if (in_array($field, $ucfields) && !starts_with_upper($field)) {
            $outputfields[$field] = ucwords(strtolower($outputfield));
        }
        if ($field == 'postcode') {
            $outputfields[$field] = strtoupper($outputfield);
        }  
        if ($field == 'phone') {
            $outputfields[$field] = (preg_match('/\s/',$outputfield) > 0) ? $outputfield : substr_replace($outputfield," ", 5, -strlen($outputfield));
        }  
        $sqlvals .= "'$outputfields[$field]'".",";
    }

    foreach($dayfields as $field)
    {
        $outputfields[$field] = isset($_POST[$field]) ? "Y" : "N";
        $sql .=  "$field".",";
        $sqlvals .= "'$outputfields[$field]'".",";
    }

    $sql .=  "date_added) ";
    $sqlvals .= "NOW() )"; 

    $sql .= $sqlvals;

    if (!mysqli_query($conn, $sql)) {
        echo "Error Entering Data: %s<br />". mysqli_sqlstate($conn);
    }
    else    // all good, we can send the welcome email
    {
        mysqli_close($conn);
        if(!$bcfvolunteers->SendVolunteerWelcomeEmail($outputfields)) {
             echo "done";
             exit;
        }

        header('Location: '.$thankyou);
    }
}
?> 