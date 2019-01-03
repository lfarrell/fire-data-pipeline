<?php
date_default_timezone_set('America/New_York');
$days_since_jan_2000 = 6941; // Through 1-1-2019

for ($i=$days_since_jan_2000; $i>0; $i--) {
    $days = strtotime("-$i days");
    $full_date = date('Y/m/d', $days);
    $file_date = date('Y-m-d', $days);

    $extension_switch_date = 1244123939; // Switched over file extension on June 4th 2009
    $extension = ($days < $extension_switch_date) ? 'dat' : 'txt';

    $path = "https://www.wfas.net/archive/www.fs.fed.us/land/wfas/archive/$full_date/fdr_obs.$extension";
    $file_name = "fire-weather/$file_date.csv";


    $ch = curl_init($path);
    $fp = fopen($file_name, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    if (curl_exec($ch) === false) {
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);
    fclose($fp);

    echo $full_date . " processed\n";
}