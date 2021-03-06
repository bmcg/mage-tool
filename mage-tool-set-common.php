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

    if (getenv('BUILD_DIR')) {
        $buildDir = getenv('BUILD_DIR');
        define('BUILD_DIR_REL', 'builds/' . pathinfo($buildDir, PATHINFO_BASENAME));
        define('BUILD_DIR', $buildDir);
    } else {
        if (isset($argv[2])) {
            $env = $argv[2];
        } else {
            if ($argv[1] == 'build') {
                $env = 'development';
            } else {
                $env = 'current';
            }
        }

        switch ($env) {
            case 'dev':
            case 'development':
                define('BUILD_DIR_REL', 'builds/development');
                define('BUILD_DIR', PROJECT . DIRECTORY_SEPARATOR . BUILD_DIR_REL);
                define('ENVIRONMENT', 'development');
                break;
            case 'release':
            case 'production':
                define('BUILD_DIR_REL', 'builds/' . date('Ymd-H'));
                define('BUILD_DIR', PROJECT . DIRECTORY_SEPARATOR . BUILD_DIR_REL);
                define('ENVIRONMENT', 'production');
                break;

            default:
                define('BUILD_DIR', PROJECT . DIRECTORY_SEPARATOR . 'current');
                break;
        }
    }

    // setup vars
    $MAGE_REPO_ORIGIN   = null;
    $MAGE_VERSION       = null;

    if (file_exists(PROJECT . DIRECTORY_SEPARATOR . 'mage-tools.ini')) {
        $CONFIG             = parse_ini_file(PROJECT . DIRECTORY_SEPARATOR . 'mage-tools.ini', true);
        extract($CONFIG['general']);
    }

    if (defined('BUILD_DIR')) {
        // Load Magento Core
        if (file_exists(BUILD_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
            require_once BUILD_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
        }
    }
}