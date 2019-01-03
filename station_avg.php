<?php
include_once 'db_connect.php';

$find_last_station = "SELECT id from station
ORDER BY id DESC
LIMIT 1";

$last_rec = $db->prepare($find_last_station);
$last_rec->execute();

$stations_num = $last_rec->fetchColumn();

for ($i=1; $i<$stations_num; $i++) {
    // Get station name and state
    $find_station = "SELECT station, state from state, station WHERE state.id = station.state_id AND station.id = ? LIMIT 1";

    $is_in_db = $db->prepare($find_station);
    $is_in_db->execute([$i]);
    $station_name = $is_in_db->fetch(PDO::FETCH_ASSOC);

    // Build station daily avgs
    $avg_station = $db->prepare("SELECT 'month', 'day', 'kbd','temp', 'breeze', 'humidity', 'rain'
    UNION ALL
    SELECT month, day, ROUND(AVG(kbdi),2) as kbd, ROUND(AVG(tmp), 2) as temp,
    ROUND(AVG(wind), 2) as breeze, ROUND(AVG(rh), 2) as humidity, ROUND(AVG(ppt), 2) as rain
    FROM state, station, weather
    WHERE state.id = station.state_id and station.id = weather.station_id
    and kbdi >= 0 and tmp != -99 and rh != -99 and ppt != -99 and wind != -99 and station.id = ?
    GROUP BY month, day
    INTO OUTFILE '" . $output_path . "/station_avgs/" . preg_replace('/\s+/', '_', $station_name['station'] . "_" . $station_name['state']) . ".csv'
    FIELDS TERMINATED BY ','
    ENCLOSED BY '\"'
    LINES TERMINATED BY '\n'");
    $avg_station->execute([$i]);

    echo $station_name['station'] . "_" . $station_name['state'] . " processed\n";
}