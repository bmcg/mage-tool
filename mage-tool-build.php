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
include_once dirname(__FILE__) . BDS . 'mage-tool-set-common.php';

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

touch(PROJECT . BDS . 'current' . BDS . 'maintenance.flag');

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

// Submoules has proven very difficult mainly due to the fact that they checkout as HEAD.
//echo " - Updating git submodules\n";
// shell_exec("git submodule init");
// shell_exec("git submodule update");


echo " - Updating modman links\n";
$modmanRepos        = $CONFIG['modman'];
$installedModules   = explode("\n", shell_exec("modman list"));
foreach ($modmanRepos as $name => $repo) {
    chdir(PROJECT);

    @list($repo, $branch) = explode(',', $repo);

    $args = '';
    if (!empty($branch)) {
        $args .= ' --branch ' . $branch;
    }

    if (!in_array($name, $installedModules)) {
        passthru("modman clone {$name} {$repo} {$args}");

    } else {
        chdir(PROJECT . "/.modman/{$name}");

        $statusOutput = shell_exec("git status -s");
        if ($statusOutput) {
            // Stash Output
            $timeStamp = date('Y-m-d h:i:s');
            passthru("git stash save 'MAGE-BUILD-TOOL AUTO-SAVE {$timeStamp}'");
        }

        passthru("git checkout {$branch}");

        // checkout against branch name and manually checkout a new one if needed
        passthru("modman update {$name}");
    }
}
chdir(PROJECT);

// init all modman repos
shell_exec("modman deploy-all"); // Handles local modman files.
shell_exec("modman repair");

echo " - Deploying magento connect \n";
$cmd = "php " . INSTALL_PATH . BDS . "mage-tool-deploy-connect.php";
passthru($cmd);


echo " - Starting Magento upgrade\n";
// turns Magento off
touch(BUILD_DIR . BDS . 'maintenance.flag');

// link in the latest build
if (file_exists(PROJECT . BDS . 'current')) {
    unlink(PROJECT . BDS . 'current');
}
symlink(BUILD_DIR, PROJECT . BDS . 'current');

// upgrade
chdir(PROJECT);
echo shell_exec("mage-tool.php upgrade");

// turn the site back on
chdir(BUILD_DIR);
unlink(BUILD_DIR . BDS . 'maintenance.flag');