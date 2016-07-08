<?php
$ERRORLOG = getenv('PHP_ERROR_LOG');

function pi_log($message) {
    error_log(date('Y-m-d H:i:s') . ': ' . $message . "\n", 3, $GLOBALS['ERRORLOG']);
}

function log_and_die($message) {
    pi_log($message);
    die($message);
}

if(!isset($_POST['domain'], $_POST['list'], $_POST['token'])) {
    log_and_die("Missing POST variables");
}

$AUTHORIZED_HOSTNAMES = [
    'http://' . $_SERVER['SERVER_ADDR'],
    'http://pi.hole',
    'http://localhost'
];

# Allow user set virtual hostnames
$virtual_host = getenv('VIRTUAL_HOST');
if (! empty($virtual_host))
    array_push($AUTHORIZED_HOSTNAMES, 'http://' . $virtual_host);

# For docker container's host IP, SERVER_ADDR will be docker0 interface ip
$server_ip = getenv('ServerIP');
if (! empty($server_ip))
    array_push($AUTHORIZED_HOSTNAMES, 'http://' . $server_ip);

// Check CORS
if(isset($_SERVER['HTTP_ORIGIN'])) {
    if(in_array($_SERVER['HTTP_ORIGIN'], $AUTHORIZED_HOSTNAMES)) {
        $CORS_ALLOW_ORIGIN = $_SERVER['HTTP_ORIGIN'];
    } else {
        log_and_die("Failed CORS: " . $_SERVER['HTTP_ORIGIN'] .' vs '. join(',', $AUTHORIZED_HOSTNAMES));
    }
    header("Access-Control-Allow-Origin: $CORS_ALLOW_ORIGIN");
} else {
    pi_log("CORS skipped, unknown HTTP_ORIGIN");
    //pi_log("CORS allowed: " . join(',', $AUTHORIZED_HOSTNAMES));
}

// Otherwise probably same origin... out of the scope of CORS
session_start();

// Check CSRF token
if(!isset($_SESSION['token'], $_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
    log_and_die("Wrong token");
}

?>
