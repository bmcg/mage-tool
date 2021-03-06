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

/*
 * Utility Functions
 */

function killdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (filetype("{$dir}/{$file}") == 'dir') {
                    killdir("{$dir}/{$file}");
                } else {
                    unlink("{$dir}/{$file}");
                }
            }
        }
        reset($files);
        rmdir($dir);
    }
}

class ZipArchive_Custom extends ZipArchive {

    protected $_licenseText = null;

    public function setLicenseText($text) {
        $this->_licenseText = $text;
    }

    public function addFile($filename, $localname = NULL, $start = 0, $length = 0) {

        $content = file_get_contents($filename);

        $content = str_replace("/** @INSERT_LICENSE_TEXT_HERE */", $this->_licenseText, $content);

        $this->addFromString($localname, $content);
    }

    public function addDir($path, $localPath=null) {
        if ($localPath == null) { $localPath = $path; }

        $this->addEmptyDir($localPath);
        $nodes = glob($path . '/*');
        foreach ($nodes as $node) {
            if (is_dir($node)) {
                //var_dump($node);
                $localNode = $localPath . pathinfo($node, PATHINFO_FILENAME) . '/';
                //var_dump($localNode);
                $this->addDir($node, $localNode);
            } else if (is_file($node))  {
                $localNode = $localPath . pathinfo($node, PATHINFO_BASENAME);
                $this->addFile($node, $localNode);
            }
        }
    }
}