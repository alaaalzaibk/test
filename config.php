<?php
define('DB_NAME','cardcharging');
define('DB_USER','ansysak');
define('DB_PASSWORD','F{rCT+TSgm(t');
define('DB_HOST','localhost');


$mysqli= new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die("error");


mysqli_set_charset($mysqli, 'utf8');
