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


if (!file_exists(PROJECT . BDS . 'current' . BDS . 'app' . BDS . 'Mage.php')) {
    die("Unabled to find a Magento build!\n");
}

require_once PROJECT . BDS . 'current' . BDS . 'app' . BDS . 'Mage.php';

set_time_limit(0);
ini_set('memory_limit', '2G');

// should start the upgrade.
$start = microtime(true);

$app = Mage::app('admin');
echo "\nLoaded App";
$app->getCache()->clean();
echo "\nCleaned Cache";

// Will trigger the actual upgrade.
ob_start();
Mage_Core_Model_Resource_Setup::applyAllUpdates();
Mage_Core_Model_Resource_Setup::applyAllDataUpdates();
ob_end_clean();

$end = microtime(true);

echo "\n\n";
echo "DB Upgrade took: " . ($end - $start);
echo "\n\n";
