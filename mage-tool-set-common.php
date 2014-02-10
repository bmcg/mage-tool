<?php

if (isset($argv[2])) {
    $env = $argv[2];
} else {
    $env = 'development';
}

switch ($env) {
    case 'dev':
    case 'development':
        define('BUILD_DIR_REL', 'builds/development');
        define('BUILD_DIR', PROJECT . BDS . BUILD_DIR_REL);
        break;
    case 'release':
    case 'production':
        define('BUILD_DIR_REL', 'builds/' . date('Ymd-H'));
        define('BUILD_DIR', PROJECT . BDS . BUILD_DIR_REL);
        break;

    default:
        die('Unknown Environment');
        break;
}
