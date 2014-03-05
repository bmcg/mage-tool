<?php

$path   = getcwd();
$i      = 0;
while (!file_exists($path . '/mage-tools.ini') && $i++ < 100) {
    $path = dirname($path);
}

if (file_exists($path . '/mage-tools.ini')) {
    define('PROJECT', $path);

    // Commands need to be run from the project dir.
    chdir(PROJECT);


    if (isset($argv[2])) {
        $env = $argv[2];
    } else {
        $env = 'development';
    }

    switch ($env) {
        case 'dev':
        case 'development':
            define('BUILD_DIR_REL', 'builds/development');
            define('BUILD_DIR', PROJECT . DIRECTORY_SEPARATOR . BUILD_DIR_REL);
            break;
        case 'release':
        case 'production':
            define('BUILD_DIR_REL', 'builds/' . date('Ymd-H'));
            define('BUILD_DIR', PROJECT . DIRECTORY_SEPARATOR . BUILD_DIR_REL);
            break;

        default:
            break;
    }

    if (defined('BUILD_DIR')) {
        // setup vars
        $MAGE_REPO_ORIGIN   = null;
        $MAGE_VERSION       = null;

        if (file_exists(PROJECT . DIRECTORY_SEPARATOR . 'mage-tools.ini')) {
            $CONFIG             = parse_ini_file(PROJECT . DIRECTORY_SEPARATOR . 'mage-tools.ini', true);
            extract($CONFIG['general']);
        }

        // Load Magento Core
        if (file_exists(BUILD_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
            require_once BUILD_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
        }
    }
}