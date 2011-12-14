<?php
/*
 *   Magento build tools By Brim
 *   Copyright (C) 2011  Brian McGilligan <brian@brimllc.com>
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
if (!file_exists(PROJECT . BDS . 'build')) {
    mkdir(PROJECT . BDS . 'build');
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
    echo " - Cloning Magento Repo\n";
    echo shell_exec("git clone --mirror $MAGE_REPO_ORIGIN $local_repo");
} else {
    echo " - Updating Magento Repo\n";
    echo shell_exec("git --git-dir=$local_repo  remote update");
}

// Check if the version needs to change
if (@file_get_contents(PROJECT . BDS . 'build' . BDS . 'VERSION') != $MAGE_VERSION) {
    echo " - Cleaning Magento Build\n";
    // clean build
    killdir(PROJECT . BDS . 'build');
    mkdir(PROJECT . BDS . 'build');

    // checkout a fresh copy of magento
    echo " - Extracting Magento ${MAGE_VERSION}\n";
    echo shell_exec("git --git-dir=$local_repo archive $MAGE_VERSION | tar -x -C " . PROJECT . "/build");

    // link external var directory
    if (file_exists(PROJECT . BDS . 'build' . BDS . 'media')) {
        killdir(PROJECT . BDS . 'build' . BDS . 'media');
    }
    symlink(PROJECT . BDS . 'media', PROJECT . BDS . 'build' . BDS . 'media');

    // link external var directory
    if (file_exists(PROJECT . BDS . 'build' . BDS . 'var')) {
        killdir(PROJECT . BDS . 'build' . BDS . 'var');
    }
    symlink(PROJECT . BDS . 'var', PROJECT . BDS . 'build' . BDS . 'var');

    // Symlink in a local config file.
    if (!file_exists(PROJECT . BDS . 'config' . BDS . 'local.xml')) {
        if (copy(PROJECT . BDS . 'build' . BDS . 'app' . BDS . 'etc' . BDS . 'local.xml.template',
                PROJECT . BDS . 'config' . BDS . 'local.xml')) {
        } else {
            echo " - Error Copying local template\n";
            exit();
        }
    }
    symlink(
        PROJECT . BDS . 'config' . BDS . 'local.xml',
        PROJECT . BDS . 'build' . BDS . 'app' . BDS . 'etc' . BDS . 'local.xml'
    );

    file_put_contents(PROJECT . BDS . 'build' . BDS . 'VERSION', $MAGE_VERSION);
}

// init module manager
if (!file_exists(PROJECT . BDS . '.modman')) {
    echo shell_exec("modman init build");
}

echo " - Updating git submodules\n";
 shell_exec("git submodule init");
 shell_exec("git submodule update");

// Link in modman extensions
foreach (scandir(PROJECT . BDS . 'extensions') as $file) {
    if ($file !=  '.' && $file != '..') {
        if (is_dir(PROJECT . BDS . 'extensions' . BDS . $file) && file_exists(PROJECT . BDS . 'extensions' . BDS . $file . BDS . 'modman')) {
            if (!file_exists(PROJECT . BDS . '.modman' . BDS . $file)) {
                symlink(PROJECT . BDS . 'extensions' . BDS . $file, PROJECT . BDS . '.modman' . BDS . $file);
            }
        }
    }
}

echo " - Updating modman links\n";
echo shell_exec("modman update-all");
shell_exec("modman repair");

// Load Magento Core
require PROJECT . BDS . 'build' . BDS . 'app' . BDS . 'Mage.php';

// Install defined modules from Magento Connect
if (version_compare(Mage::getVersion(), '1.4.2.0') >= 0) {
    // Magento 1.4.2.0 +
    chdir(PROJECT . BDS . 'build' );
    chmod('mage', 0755);
    shell_exec('./mage mage-setup');

    $connect_file = PROJECT . BDS . 'extensions' . BDS . 'magento-connect';
    if (file_exists($connect_file)) {
        if (($fp = fopen($connect_file, 'r')) != null) {
            //
            $installed_extensions = shell_exec('./mage list-installed');
            while (($line = fgets($fp)) != false) {
                list($channel, $extension) = explode(' ', $line);
                if (strpos($installed_extensions, $extension) === false) {
                    echo " - Installing $extension\n";
                    shell_exec("./mage install $channel $extension");
                } else {
                    echo " - Upgrading $extension\n";
                    shell_exec("./mage upgrade $channel $extension");
                }
            }
            fclose($fp);
        }
    }

} else {
    // Before 1.4.2.0

}
