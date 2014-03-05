<?php
/**
 * Created by PhpStorm.
 * User: brian
 * Date: 2/10/14
 * Time: 2:24 PM
 */

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mage-tool-set-common.php';


// Install defined modules from Magento Connect
if (version_compare(Mage::getVersion(), '1.4.2.0') >= 0) {
    // Magento 1.4.2.0 +
    chdir(BUILD_DIR);
    chmod('mage', 0755);
    shell_exec('./mage mage-setup');

    if (isset($CONFIG['connect'])) {
        $installed_extensions   = shell_exec('./mage list-installed');
        $extensions             = $CONFIG['connect'];

        foreach ($extensions as $channel => $extensionList) {
            $extensionItems = explode(',', $extensionList);

            foreach ($extensionItems as $extension) {
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
        }
    }
} else {
    // Before 1.4.2.0
}
