<?php
include_once 'db_connect.php';
date_default_timezone_set('America/New_York');
$days_since_jan_2000 = 6942; // Through 1-1-2019

for ($i=$days_since_jan_2000; $i>0; $i--) {
    $days = strtotime("-$i days");
    $file_date = date('Y-m-d', $days);

    // Build station daily values
    $avg_station = $db->prepare("SELECT 'reading_date', 'kbd','temp', 'breeze', 'humidity', 'rain'
    UNION ALL
    SELECT reading_date, kbdi as kbd, tmp as temp, wind as breeze, rh as humidity, ppt as rain
    FROM state, station, weather
    WHERE state.id = station.state_id and station.id = weather.station_id
    and kbdi >= 0 and tmp != -99 and rh != -99 and ppt != -99 and wind != -99 and reading_date = ?
    INTO OUTFILE '" . $output_path . "/station_dailies/" . $file_date . ".csv'
    FIELDS TERMINATED BY ','
    ENCLOSED BY '\"'
    LINES TERMINATED BY '\n'");
    $avg_station->execute([$file_date]);

    echo $file_date . " processed\n";
}