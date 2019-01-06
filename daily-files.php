<?php
include_once 'db_connect.php';
date_default_timezone_set('America/New_York');
$days_since_jan_2000 = 6945; // Through 1-1-2019

for ($i=$days_since_jan_2000; $i>0; $i--) {
    $days = strtotime("-$i days");
    $file_date = date('Y-m-d', $days);

    $fh = fopen('station_dailies/' . $file_date . '.csv', 'wb');
    fputcsv($fh, ['station', 'state', 'lat', 'lng', 'reading_date', 'kbd','temp', 'breeze', 'humidity', 'rain']);

    // Build station daily values
    $daily_station = $db->prepare("
    SELECT station, state, lat, lng, reading_date, kbdi as kbd, tmp as temp, wind as breeze, rh as humidity, ppt as rain
    FROM state, station, weather
    WHERE state.id = station.state_id and station.id = weather.station_id and state.id != 21 and state.id != 46 and state.id < 55
    and kbdi >= 0 and tmp != -99 and rh != -99 and ppt != -99 and wind != -99 and reading_date = ?");
    $daily_station->execute([$file_date]);

    $rows = $user = $daily_station->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row) {
        fputcsv($fh, $row);
    }

    fclose($fh);

    echo $file_date . " processed\n";
}