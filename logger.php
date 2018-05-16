<?php

function executionLogger($message){

    date_default_timezone_set('UTC');
    $date = date("Y-m-d H:i:s");

    $file = fopen(__DIR__."/logs/myTracerFile.txt", "a");
    fwrite($file,PHP_EOL.$date." ('UTC') ".$message);
    fclose($file);

    echo $message;
}

function errorLogger($message){

    date_default_timezone_set('UTC');
    $date = date("Y-m-d H:i:s");

    $file = fopen(__DIR__."/logs/myErrorFile.txt", "a");
    fwrite($file,PHP_EOL.$date." ('UTC') ".$message);
    fclose($file);

    echo $message;
}