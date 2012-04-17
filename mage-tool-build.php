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

// Init Base Directories

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


if (!file_exists(PROJECT . BDS . 'builds')) {
    mkdir(PROJECT . BDS . 'builds');
}
if (!file_exists(BUILD_DIR)) {
    mkdir(BUILD_DIR);
}
if (!file_exists(PROJECT . BDS . 'cache')) {
    mkdir(PROJECT . BDS . 'cache');
}
if (!file_exists(PROJECT . BDS . 'var')) {
    mkdir(PROJECT . BDS . 'var');
}

// Clone and update the repo, makes sure we have the latest versions
$local_repo = PROJECT . BDS . 'cache'. BDS . 'magento-repo';
if (!file_exists($local_repo)) {
    mkdir($local_repo);
}

// Check if the version needs to change
if (@file_get_contents(BUILD_DIR . BDS . 'VERSION') != $MAGE_VERSION) {
    echo " - Cleaning Magento Build\n";
    // clean build
    killdir(BUILD_DIR);
    mkdir(BUILD_DIR);

    // checkout a fresh copy of magento
    echo " - Extracting Magento ${MAGE_VERSION}\n";

    // Check if we have a cached archive.
    $cachedFile = $local_repo . BDS . $MAGE_VERSION . $MAGE_EXT;
    $remoteFile = $MAGE_REPO_BASE . $MAGE_VERSION . $MAGE_EXT;
    if (!file_exists($cachedFile)) {
        file_put_contents($cachedFile, file_get_contents($remoteFile));
    }

    echo shell_exec("tar -xjf $cachedFile -C " . BUILD_DIR . " --strip-components=1");

    if (file_exists(PROJECT . BDS . '..' . BDS . 'shared')) {
        $sharedMedia = realpath(PROJECT . BDS . '..' . BDS . 'shared');
    } else {
        $sharedMedia = PROJECT;
    }

    // link external var directory
    if (file_exists(BUILD_DIR . BDS . 'media')) {
        killdir(BUILD_DIR . BDS . 'media');
    }
    symlink($sharedMedia . BDS . 'media', BUILD_DIR . BDS . 'media');

    // link external var directory
    if (file_exists(BUILD_DIR . BDS . 'var')) {
        killdir(BUILD_DIR . BDS . 'var');
    }
    symlink($sharedMedia . BDS . 'var', BUILD_DIR . BDS . 'var');

    // Symlink in a local config file.
    if (!file_exists(PROJECT . BDS . 'config' . BDS . 'local.xml')) {
        if (copy(BUILD_DIR . BDS . 'app' . BDS . 'etc' . BDS . 'local.xml.template',
                PROJECT . BDS . 'config' . BDS . 'local.xml')) {
        } else {
            echo " - Error Copying local template\n";
            exit();
        }
    }
    symlink(
        PROJECT . BDS . 'config' . BDS . 'local.xml',
        BUILD_DIR . BDS . 'app' . BDS . 'etc' . BDS . 'local.xml'
    );

    if (file_exists(PROJECT . BDS . 'config' . BDS . 'robots.txt')) {
        symlink(
            PROJECT . BDS . 'config' . BDS . 'robots.txt',
            BUILD_DIR . BDS . 'robots.txt'
        );
    }

    file_put_contents(BUILD_DIR . BDS . 'VERSION', $MAGE_VERSION);
}

// init module manager
if (!file_exists(PROJECT . BDS . '.modman')) {
    symlink(PROJECT . BDS . 'extensions' . BDS, PROJECT . BDS . '.modman');
}

file_put_contents(PROJECT . BDS . '.modman' . BDS . '.basedir', BUILD_DIR_REL);

echo " - Updating git submodules\n";
 shell_exec("git submodule init");
 shell_exec("git submodule update");


echo " - Updating modman links\n";
echo shell_exec("modman update-all");
shell_exec("modman repair");

// Load Magento Core
require BUILD_DIR . BDS . 'app' . BDS . 'Mage.php';

// Install defined modules from Magento Connect
if (version_compare(Mage::getVersion(), '1.4.2.0') >= 0) {
    // Magento 1.4.2.0 +
    chdir(BUILD_DIR);
    chmod('mage', 0755);
    shell_exec('./mage mage-setup');

    $connect_file = PROJECT . BDS . 'extensions' . BDS . 'magento-connect';
    if (file_exists($connect_file)) {
        if (($fp = fopen($connect_file, 'r')) != null) {
            //
            $installed_extensions = shell_exec('./mage list-installed');
            while (($line = fgets($fp)) != false) {
                list($channel, $extension) = explode(' ', $line);

                if ($channel == 'download') {
                    $local_filename = pathinfo($extension, PATHINFO_BASENAME);
                    $local_extension= pathinfo($extension, PATHINFO_EXTENSION);

                    echo " - Installing $local_filename\n";

                    if (file_exists($local_filename)) {
                        unlink($local_filename);
                    }

                    file_put_contents($local_filename, file_get_contents($extension));

                    if ($local_extension == 'zip') {
                        $l = shell_exec("unzip -o $local_filename");
                        var_dump($l);
                    } else {
                        shell_exec("./mage install-file $local_filename");
                    }
                } else {
                    if (strpos($installed_extensions, $extension) === false) {
                        echo " - Installing $extension\n";
                        shell_exec("./mage install $channel $extension");
                    } else {
                        echo " - Upgrading $extension\n";
                        shell_exec("./mage upgrade $channel $extension");
                    }
                }
            }
            fclose($fp);
        }
    }

} else {
    // Before 1.4.2.0

}

if (file_exists(PROJECT . BDS . 'current')) {
    unlink(PROJECT . BDS . 'current');
}

symlink(BUILD_DIR, PROJECT . BDS . 'current');
