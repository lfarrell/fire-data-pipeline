<?php
include_once 'db_connect.php';

$file_dir = 'fire-weather';
$files = scandir($file_dir);

$columns_regx = "/\s{1,}/";
$state_regx = "/\*\*/";
$station_regx = "/^[a-zA-Z]|^#\d{1,}|#/";

$current_date = '';
$state = '';
$state_id = '';

foreach ($files as $file) {
    if(preg_match("/.csv$/", $file)) {
        $current_date = preg_split('/\./', $file)[0];
        $date_parts = preg_split('/-/', $current_date);

        if (($handle = fopen($file_dir . "/" . $file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $weather = [];

                $station = '';
                $offset = 0;

                if (preg_match($state_regx, trim($data[0]))) {
                    $headers = cleanReading($columns_regx, $data[0])[0];

                    if (!preg_match($state_regx, $headers[2])) {
                        $state = $headers[1] . ' ' . $headers[2];
                    } else {
                        $state = $headers[1];
                    }

                    if(preg_match('/Elev|Elev Lat/', $headers[2])) {
                        $state_id = -99;
                        continue;
                    }

                    if (preg_match('/Puerto|Guam|\d/', $state)
                                || $state == 'a' || $state == 'b') {
                        $state_id = -99;
                        continue;
                    }

                    $state_id = addState($db, $state);
                } elseif (preg_match('/^\d{3,}/', trim($data[0]))) {
                    if ($state_id == -99) {
                        continue;
                    }
                    $full_values = cleanReading($columns_regx, $data[0]);
                    $columns = $full_values[0];
                    $long_name = "$columns[1] $columns[2] $columns[3]";

                    if (strtolower($long_name) == 'fire weather observations') {
                        continue;
                    } elseif (preg_match($station_regx, $columns[5])) {
                        $offset = 4;
                        $station = "$columns[1] $columns[2] $columns[3] + $columns[4] + $columns[5]";
                    } elseif (preg_match($station_regx, $columns[4])) {
                        $offset = 3;
                        $station = "$columns[1] $columns[2] $columns[3] + $columns[4]";
                    } elseif (preg_match($station_regx, $columns[3])) {
                        $offset = 2;
                        $station = $long_name;
                    } elseif (preg_match($station_regx, $columns[2])) {
                        $offset = 1;
                        $station = "$columns[1] $columns[2]";
                    } else {
                        $offset = 0;
                        $station = $columns[1];
                    }

                    if (!preg_match('/\./', $columns[9 + $offset]) || preg_match('/[a-zA-Z]/', $columns[6 + $offset])) {
                        $offset -=1;
                    }

                    if (preg_match('/^(\d{3,}|[a-zA-Z])/', $columns[3 + $offset])) {
                        $offset+=1;
                    }

                    $lng = -1 * abs($columns[4 + $offset]); // ensure negative number

                    $station_info = [$station, $columns[3 + $offset], $lng, $state_id];
                    $station_id = addStation($db, $station_info, $state);

                    if (preg_match('/^\d/', $columns[19 + $offset])) {
                        $ignition_component = $columns[19 + $offset];
                    } else {
                        $ignition_component = -99;
                    }

                    $precip = $full_values[1];

                    $weather_info = [
                        $columns[6 + $offset], // tmp
                        $columns[7 + $offset], // rh
                        $columns[8 + $offset], // wind
                        $columns[$precip], // ppt
                        $columns[$precip + 1], // erc
                        $columns[$precip + 2], // bi
                        $columns[$precip + 3], // sc
                        $columns[$precip + 4], // kbdi
                        $columns[$precip + 8], // stl
                        $ignition_component, // ic
                        $current_date,
                        $date_parts[0],
                        $date_parts[1],
                        $date_parts[2],
                        $station_id
                    ];

                    addReading($db, $weather_info);
                    echo "$station - $current_date - $state\n";

                }
            }
            fclose($handle);
        }
    }
}

function findState($db, $current_state) {
    $find_state = "SELECT id from state WHERE state = ?";

    $is_in_db = $db->prepare($find_state);
    $is_in_db->execute([$current_state]);

    return $is_in_db->fetchColumn();
}

function findStation($db, $station_location) {
    $find_station = "SELECT id from station WHERE station = ? AND lat = ? AND lng = ?";

    $is_in_db = $db->prepare($find_station);
    $is_in_db->execute($station_location);

    return $is_in_db->fetchColumn();
}

function addState($db, $current_state) {
    $state_id = findState($db, $current_state);

    if (!$state_id) {
        $add_state = $db->prepare("INSERT INTO state(state) VALUES(?)");
        $add_state->execute([$current_state]);

        $state_id = $db->lastInsertId();
    }

    return $state_id;
}

function addStation($db, $row) {
    $station_id = findStation($db, [$row[0], $row[1], $row[2]]);

    if (!$station_id) {
        $add_station = $db->prepare("INSERT INTO station(station, lat, lng, state_id) VALUES(?, ?, ?, ?)");
        $add_station->execute($row);

        $station_id = $db->lastInsertId();
    }

    return $station_id;
}

function addReading($db, $reading) {
    $add_reading = $db->prepare("INSERT INTO weather(tmp, rh, wind, ppt, erc, bi, sc, kbdi, stl, ic, reading_date, year, month, day, station_id)
     VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $add_reading->execute($reading);
}

function cleanReading($regx, $value) {
    $cleaned = [];
    $values = array_values(array_filter(preg_split($regx, $value)));
    $set_val = 9;

    foreach($values as $key => $val) {
        $v = trim($val);

        if ($v == '') {
            continue;
        }

        if (preg_match('/\./', $v)) {
            $set_val = $key;
        }

        $cleaned[] = $v;
    }

    return [$cleaned, $set_val];
}