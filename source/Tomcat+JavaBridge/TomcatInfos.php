<?php

require_once('java/Java.inc');
$system      = new Java('java.lang.System');
$tomcatInfos = [
    'CATALINA_HOME'                  => $_SERVER['CATALINA_HOME'],
    'JAVA_HOME'                      => $_SERVER['JAVA_HOME'],
    'Java Version'                   => ((string) $system->getProperty('java.version')),
    'Java Runtime Version'           => ((string) $system->getProperty('java.runtime.version')),
    'Java Specification Version'     => ((string) $system->getProperty('java.specification.version')),
    'Java VM Name'                   => ((string) $system->getProperty('java.vm.name')),
    'Java VM Specficiation Name'     => ((string) $system->getProperty('java.vm.specification.name')),
    'Java VM Version'                => ((string) $system->getProperty('java.vm.version')),
    'Java Vendor'                    => ((string) $system->getProperty('java.vendor')),
    'JavaBridge version'             => JAVA_PEAR_VERSION,
    'JavaBridge-PHP Version'         => phpversion(),
    'JavaBridge-Zend Engine Version' => zend_version(),
    'Version'                        => $_SERVER["SERVER_SIGNATURE"]
];
ksort($tomcatInfos);
$sReturn     = utf8_encode(json_encode($tomcatInfos, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    if (extension_loaded('zlib')) {
        ob_start();
        ob_implicit_flush(0);
        header('Content-Encoding: gzip');
    }
}
header("Content-Type: application/json; charset=utf-8");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
echo $sReturn;
if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
    if (extension_loaded('zlib')) {
        $gzip_contents = ob_get_contents();
        ob_end_clean();
        $gzip_size     = strlen($gzip_contents);
        $gzip_crc      = crc32($gzip_contents);
        $gzip_contents = gzcompress($gzip_contents, 9);
        $gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);
        echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
        echo $gzip_contents;
        echo pack('V', $gzip_crc);
        echo pack('V', $gzip_size);
    }
}