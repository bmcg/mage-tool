<?php

$commonPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mage-tool-set-common.php';
if (file_exists($commonPath)) {
    include_once $commonPath;
    Mage::app();
}

try {
    if (isset($argv[2])) {
        $baseDir = $argv[2];
    } else {
        $baseDir = Mage::getBaseDir();
    }
    if (isset($argv[3])) {
        $baseUrl = $argv[3];
    } else {
        $baseUrl = Mage::getBaseUrl();
    }

    if (is_writeable($baseDir)) {
        $apcCleanFileName   = 'apc_clean_' . uniqid() .'.php';
        $apcCleanAbsPath    = $baseDir . DIRECTORY_SEPARATOR . $apcCleanFileName;
        $apcCleanUrl        = $baseUrl . $apcCleanFileName;

        $apcCleanScript =<<<OEF
    <?php

    if (in_array(@\$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', @\$_SERVER["SERVER_ADDR"]))) {
        if (extension_loaded('apc')) {
            apc_clear_cache();
            apc_clear_cache('user');
            echo 1;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
OEF;

        if (file_put_contents($apcCleanAbsPath, $apcCleanScript) != false) {
            sleep(1); //sometimes doesn't work on Production without this
            // wrote file successfully, call the file, then remove
            if (file_get_contents($apcCleanUrl) == '1') {
                echo " - APC cache storage has been flushed.\n";
            } else {
                echo " - The web accessible cache clean script FAILED.\n";
            }
            if (!unlink($apcCleanAbsPath)) {
                echo " - Unable to remove the web accessible cache clean script: $apcCleanFileName\n";
            }
        } else {
            echo " - Unable to write the web accessible cache clean script.\n";
        }
    } else {
        echo " - The Magento base directory is not writable by the current user.";
    }

} catch (Exception $e) {
    echo "Exception:\n";
    echo $e . "\n";
}
 