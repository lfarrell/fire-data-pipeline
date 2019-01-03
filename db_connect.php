<?php
include_once 'connection_vars.php';

try {
    $db = new PDO("mysql:$host;dbname=$dbname", $username, $password);
} catch(PDOException $e) {
    echo $e->getMessage();
}