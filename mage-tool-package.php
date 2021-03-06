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

/**
 * Read a modman file and generates a zip package that can be unzip in a magento root.
 */

define('DS', DIRECTORY_SEPARATOR);
define('BP', getcwd());

$modmanConfigFilename   = BP . '/modman';
$modConfigFilename      = BP . '/code/etc/config.xml';

// Extract module name and version
if (file_exists($modConfigFilename)) {
    $sxml = simplexml_load_file($modConfigFilename);
    $module = current($sxml->modules->children());
    $modName = $module->getName();
    $modVersion = (string)$module->version;
} else {
    $modName = pathinfo(BP, PATHINFO_FILENAME);
    $modVersion = false;
}

// Create zip archive
$packageName = $modName;
if ($modVersion) {
    $packageName .= '-' . $modVersion;
    $packageNameVersion = $packageName;
}
$packageName .= '.zip';
$packagePath = BP . '/'. $packageName;

echo "\nWriting package: $packageName\n";
$za = new ZipArchive_Custom();
$za->open($packagePath, ZipArchive::OVERWRITE);

if (file_exists(BP . '/license-header.txt')) {
    $za->setLicenseText(file_get_contents(BP. '/license-header.txt'));
}

process_modman_file($za, $modmanConfigFilename);

if (file_exists(BP . '/license.txt')) {
    $za->addFile(BP . '/license.txt', $modName .'-license.txt');
}
if (file_exists(BP . '/license.html')) {
    $za->addFile(BP . '/license.html', $modName .'-license.html');
}
if (file_exists(BP . '/license.pdf')) {
    $za->addFile(BP . '/license.pdf', $modName . '-license.pdf');
}
if (file_exists(BP . '/README')) {
    $za->addFile(BP . '/README', 'README');
}
if (file_exists(BP . '/docs/user-guide.pdf')) {
    $za->addFile(BP . '/docs/user-guide.pdf', $modName . '-user-guide.pdf');
    copy(BP . '/docs/user-guide.pdf', BP . '/'. $modName . '-user-guide.pdf');
}

$za->close();


function process_modman_file ($za, $modmanFilename) {

    $basepath = pathinfo($modmanFilename, PATHINFO_DIRNAME);

    // read modman file to start packaging
    if (!($fp = fopen($modmanFilename, 'r'))) {
        die("Unable to read the modman config file");
    }

    // process modman config file
    while(($line = fgets($fp)) !== false) {
        preg_match('/([a-zA-z0-9\/\.\-\_\@]*)\s*([a-zA-z0-9\/\.\-\_]*)/i', $line, $matches);
        $path       = $matches[1];
        $zipPath    = $matches[2];

        if ($path == '@import') {
            process_modman_file($za, $basepath . DS . $zipPath . DS . 'modman');
        } else {
            if (is_file($basepath . DS . $path)){
                $za->addFile($basepath . DS . $path, $zipPath);
            } else {
                // dir
                $za->addDir($path, $zipPath);
            }
        }
    }
}
