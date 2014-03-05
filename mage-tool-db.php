<?php
/*
 *   Magento build tools By Brim LLC
 *   Copyright (C) 2011-2012  Brian McGilligan <brian@brimllc.com>
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

if (!isset($argv[2])) {
    die('Mising sub command');
}

$subCommand = $argv[2];

switch ($subCommand) {
    case 'backup':
        dbBackup();
        break;
    case 'restore':
        dbRestore();
        break;

    default:
        die('Invalid sub command');
        break;
}

function dbBackup() {

    // read magento config file

    $configXml = simplexml_load_file(PROJECT . BDS . 'config' . BDS . 'local.xml');

    $dbConnXml = $configXml->xpath('global/resources/default_setup/connection');

    $dbHost = (string)$dbConnXml[0]->host;
    $dbUser = (string)$dbConnXml[0]->username;
    $dbPass = (string)$dbConnXml[0]->password;
    $dbName = (string)$dbConnXml[0]->dbname;

    $outFile = PROJECT . BDS . 'backups' . BDS . date('Ymd-His') . '-' . $dbName . '.sql';

    $args = '';
    if ($dbPass) {
        $args .= " -p{$dbPass} ";
    }

    $command = "mysqldump --single-transaction -h{$dbHost} -u{$dbUser} $args $dbName > $outFile ";
    $output = shell_exec($command);

    return $outFile;
}


function dbRestore() {

}