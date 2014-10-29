<?php

$cwd[__FILE__] = __FILE__;
if (is_link($cwd[__FILE__])) $cwd[__FILE__] = readlink($cwd[__FILE__]);
$cwd[__FILE__] = dirname(dirname($cwd[__FILE__]));

//echo $cwd[__FILE__];
require_once($cwd[__FILE__] . "/../citizen_science_grid/header.php");
require_once($cwd[__FILE__] . "/../citizen_science_grid/navbar.php");
//require_once($cwd[__FILE__] . "/../citizen_science_grid/news.php");
require_once($cwd[__FILE__] . "/../citizen_science_grid/footer.php");
//require_once($cwd[__FILE__] . "/../citizen_science_grid/uotd.php");
require_once($cwd[__FILE__] . "/webpage/wildlife_db.php");
require_once($cwd[__FILE__] . "/webpage/my_query.php");

print_header("Wildlife@Home: Duration vs Difficulty", "", "wildlife");
print_navbar("Projects: Wildlife@Home", "Wildlife@Home");

//echo "Header:";

ini_set("mysql.connect_timeout", 300);
ini_set("default_socket_timeout", 300);

$wildlife_db = mysql_connect("wildlife.und.edu", $wildlife_user, $wildlife_passwd);
mysql_select_db("wildlife_video", $wildlife_db);

$easy_query = "SELECT experience, total_experience FROM watched_videos_stats WHERE timediff(end_time, start_time) IS NOT NULL AND time_to_sec(timediff(end_time, start_time)) < 3600 AND difficulty = 'easy' ORDER BY experience/total_experience ASC";
$medium_query = "SELECT experience, total_experience FROM watched_videos_stats WHERE timediff(end_time, start_time) IS NOT NULL AND time_to_sec(timediff(end_time, start_time)) < 3600 AND difficulty = 'medium' ORDER BY experience/total_experience ASC";
$hard_query = "SELECT experience, total_experience FROM watched_videos_stats WHERE timediff(end_time, start_time) IS NOT NULL AND time_to_sec(timediff(end_time, start_time)) < 3600 AND difficulty = 'hard' ORDER BY experience/total_experience ASC";
$easy_result = attempt_query_with_ping($easy_query, $wildlife_db);
$medium_result = attempt_query_with_ping($medium_query, $wildlife_db);
$hard_result = attempt_query_with_ping($hard_query, $wildlife_db);
if (!$easy_result || !$medium_result || !$hard_result) {
    error_log("MYSQL Error (" . mysql_errno($wildlife_db) . "): " . mysql_error($wildlife_db) . "/nquery: $easy_query\n");
    die("MYSQL Error (" . mysql_errno($wildlife_db) . "): " . mysql_error($wildlife_db) . "/nquery: $easy_query\n");
}

$easy_rows = mysql_num_rows($easy_result);
$medium_rows = mysql_num_rows($medium_result);
$hard_rows = mysql_num_rows($hard_result);

echo "
<div class='containder'>
    <div class='row'>
        <div class='col-sm-12'>
    <script type = 'text/javascript' src='https://www.google.com/jsapi'></script>
    <script type = 'text/javascript'>
        google.load('visualization', '1', {packages:['corechart']});
        google.setOnLoadCallback(drawChart);

        function drawChart() {
            var data = google.visualization.arrayToDataTable([
";

echo "['Easy'";
$index = 0;
$total = 0;
while ($row = mysql_fetch_assoc($easy_result)) {
    $total += 100*$row['experience']/$row['total_experience'];
    if ($index == 0) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(1/4 * $easy_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(3/4 * $easy_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == $easy_rows - 1) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
        //echo ", 'Mean: " . $total/$easy_rows . "'";
    }
    $index++;
}
echo "],\n";

echo "['Medium'";
$index = 0;
$total = 0;
while ($row = mysql_fetch_assoc($medium_result)) {
    $total += 100*$row['experience']/$row['total_experience'];
    if ($index == 0) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(1/4 * $medium_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(3/4 * $medium_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == $hard_rows - 1) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
        //echo ", 'Mean: " . $total/$medium_rows . "'";
    }
    $index++;
}
echo "],\n";

echo "['Hard'";
$index = 0;
$total = 0;
while ($row = mysql_fetch_assoc($hard_result)) {
    $total += 100*$row['experience']/$row['total_experience'];
    if ($index == 0) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(1/4 * $hard_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == floor(3/4 * $hard_rows)) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
    } else if ($index == $hard_rows - 1) {
        echo ", " . 100*$row['experience']/$row['total_experience'];
        //echo ", 'Mean: " . $total/$hard_rows . "'";
    }
    $index++;
}
echo "]";

echo "
            ], true);

            var options = {
                title: 'User percent experience vs. User provided difficulty',
                vAxis: {title: 'Percent Experience'},
                hAxis: {title: 'Provided Difficulty'},
                legend: 'none'
            };

            var chart = new google.visualization.CandlestickChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>

            <h1>Candlestick Chart!</h1>

            <div id='chart_div' style='width: auto; height: 700px;'></div>

        </div>
    </div>
</div>
";

print_footer("Travis Desell, 'Travis Desell, Susan Ellis-Felege and the Wildlife@Home Team'", "Travis Desell, Susan Ellis-Felege");

echo "
    </body>
</html>
";
?>