#!/usr/bin/php
<?php
/*
 *   Magento build tools By Brim LLC
 *   Copyright (C) 2011-2014  Brian McGilligan <brian@brimllc.com>
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Down and dirty proof of concept.
 */

define('BDS', DIRECTORY_SEPARATOR); // prevents redefine warning if we need to load Magento (BDS vs DS)
$ds =  BDS;
define('INSTALL_PATH', dirname(__FILE__));

//var_dump($argc, $argv);

if ($argc == 1) {
    die("Missing required argumets!\n");
}

require_once INSTALL_PATH . DIRECTORY_SEPARATOR . 'mage-tool-set-common.php';

// Load our library functions.
require INSTALL_PATH . BDS . "mage-tool-lib.php";

/**
 * Commands
 *
 * build
 * upgrade
 *
 */

$command = $argv[1];
switch($command) {
    case 'build':
    case 'upgrade':
    case 'package':
    case 'db':
    case 'deploy-connect':
        require INSTALL_PATH . BDS . "mage-tool-{$command}.php";
        break;

    default:
        die("Invalid Command!\n");
        break;
}


die("Complete!\n");
