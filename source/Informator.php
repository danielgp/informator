<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\informator;

class Informator
{

    use \danielgp\common_lib\CommonCode;

    private $informatorInternalArray;

    public function __construct()
    {
        $this->informatorInternalArray['composerLockFile'] = realpath('../') . DIRECTORY_SEPARATOR . 'composer.lock';
        $this->informatorInternalArray['knownLabels']      = [
            '--- List of known labels' => '',
            'Apache Info'              => ['getApacheDetails', null],
            'Auto Dependencies'        => [
                'getPackageDetailsFromGivenComposerLockFile',
                $this->informatorInternalArray['composerLockFile'],
            ],
            'Auto Dependencies File'   => [$this->informatorInternalArray['composerLockFile']],
            'Client Info'              => ['getClientBrowserDetailsForInformator', null],
            'Informator File Details'  => ['getFileDetails', __FILE__],
            'MySQL Databases All'      => ['getMySQLinfo', ['Databases All']],
            'MySQL Databases Client'   => ['getMySQLinfo', ['Databases Client']],
            'MySQL Engines Active'     => ['getMySQLinfo', ['Engines Active']],
            'MySQL Engines All'        => ['getMySQLinfo', ['Engines All']],
            'MySQL General'            => ['getMySQLinfo', ['General']],
            'MySQL Variables Global'   => ['getMySQLinfo', ['Variables Global']],
            'MySQL Info'               => ['getMySQLinfo', ['Engines Active', 'General', 'Variables Global']],
            'Php Extensions Loaded'    => ['getPhpDetails', ['Extensions Loaded']],
            'Php General'              => ['getPhpDetails', ['General']],
            'Php INI Settings'         => ['getPhpDetails', ['INI Settings']],
            'Php Stream Filters'       => ['getPhpDetails', ['Stream Filters']],
            'Php Stream Transports'    => ['getPhpDetails', ['Stream Transports']],
            'Php Stream Wrappers'      => ['getPhpDetails', ['Stream Wrappers']],
            'Php Info'                 => ['getPhpDetails', null],
            'Server Info'              => ['getServerDetails', null],
            'System Info'              => ['systemInfo', null],
            'Tomcat Info'              => ['getTomcatDetails', null],
        ];
        ksort($this->informatorInternalArray['knownLabels']);
        $rqst                                              = new \Symfony\Component\HttpFoundation\Request;
        $this->informatorInternalArray['superGlobals']     = $rqst->createFromGlobals();
        echo $this->setInterface();
    }

    private function getApacheDetails()
    {
        $srvSignature     = $_SERVER['SERVER_SOFTWARE'];
        $srvSoftwareArray = explode(' ', $srvSignature);
        $sInfo            = [];
        $tmp              = explode('/', $srvSoftwareArray[0]);
        if (strpos($srvSoftwareArray[0], 'Apache') !== false) {
            $sInfo['Apache'] = [
                'Name'      => $tmp[0],
                'Signature' => $srvSignature,
                'Version'   => $tmp[1]
            ];
        }
        $modulesToDisregard         = [
            $srvSoftwareArray[0],
            '(Win64)',
        ];
        $sInfo['Apache']['Modules'] = $this->getApacheModules(array_diff($srvSoftwareArray, $modulesToDisregard));
        ksort($sInfo['Apache']);
        return $sInfo['Apache'];
    }

    private function getApacheModules(array $srvSoftwareArray)
    {
        $aReturn = [];
        foreach ($srvSoftwareArray as $value) {
            $tmp                  = explode('/', $value);
            $rootModule           = strtolower(str_replace(['mod_', 'OpenSSL'], ['', 'SSL'], $tmp[0]));
            $aReturn[$rootModule] = [
                'Name'    => $tmp[0],
                'Version' => $tmp[1]
            ];
        }
        ksort($aReturn);
        return $aReturn;
    }

    private function getClientBrowserDetailsForInformator()
    {
        $tmpFolder        = $this->getTemporaryFolder();
        $tmpDoctrineCache = null;
        clearstatcache();
        if (is_dir($tmpFolder) && is_writable($tmpFolder)) {
            $tmpDoctrineCache = $tmpFolder . DIRECTORY_SEPARATOR . 'DoctrineCache';
        }
        return $this->getClientBrowserDetails(['Browser', 'Device', 'OS'], $tmpDoctrineCache);
    }

    private function getMySQLinfo($returnType = ['Engines Active', 'General', 'Variables Global'])
    {
        if (is_null($this->mySQLconnection)) {
            $mySQLconfig = [
                'host'     => MYSQL_HOST,
                'port'     => MYSQL_PORT,
                'username' => MYSQL_USERNAME,
                'password' => MYSQL_PASSWORD,
                'database' => MYSQL_DATABASE,
            ];
            $this->connectToMySql($mySQLconfig);
        }
        $sInfo           = [];
        $aMySQLinfoLabel = [
            'Databases All'    => ['getMySQLlistDatabases', false],
            'Databases Client' => ['getMySQLlistDatabases', true],
            'Engines Active'   => ['getMySQLlistEngines', true],
            'Engines All'      => ['getMySQLlistEngines', false],
            'General'          => ['getMySQLgenericInformations'],
            'Variables Global' => ['getMySQLglobalVariables'],
        ];
        foreach ($returnType as $value) {
            $aVal = $aMySQLinfoLabel[$value];
            switch (count($aVal)) {
                case 1:
                    $sInfo['MySQL'][$value] = call_user_func([$this, $aVal[0]]);
                    break;
                case 2:
                    $sInfo['MySQL'][$value] = call_user_func([this, $aVal[0]], $aVal[1]);
                    break;
            }
        }
        ksort($sInfo['MySQL']);
        return $sInfo['MySQL'];
    }

    private function getPhpDetails($returnType = ['General', 'INI Settings', 'Extensions Loaded', 'Temporary Folder'])
    {
        $sInfo = [];
        foreach ($returnType as $value) {
            switch ($value) {
                case 'Extensions Loaded':
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey(get_loaded_extensions());
                    break;
                case 'General':
                    $sInfo['PHP'][$value] = [
                        'Version'             => phpversion(),
                        'Zend Engine Version' => zend_version(),
                    ];
                    break;
                case 'INI Settings':
                    $sInfo['PHP'][$value] = ini_get_all(null, false);
                    break;
                case 'Stream Filters':
                    $sArray               = (array) stream_get_filters();
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey($sArray);
                    break;
                case 'Stream Transports':
                    $sArray               = (array) stream_get_transports();
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey($sArray);
                    break;
                case 'Stream Wrappers':
                    $sArray               = (array) stream_get_wrappers();
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey($sArray);
                    break;
                case 'Temporary Folder':
                    $sInfo['PHP'][$value] = $this->getTemporaryFolder();
                    break;
            }
        }
        ksort($sInfo['PHP']);
        return $sInfo['PHP'];
    }

    private function getServerDetails()
    {
        $serverMachineType = 'unknown';
        $serverInfo        = [
            'name'    => 'undisclosed',
            'host'    => $this->informatorInternalArray['superGlobals']->getHttpHost(),
            'release' => 'undisclosed',
            'version' => 'undisclosed',
        ];
        if (function_exists('php_uname')) {
            switch (php_uname('m')) {
                case 'AMD64':
                    $serverMachineType = 'x64 (64 bit)';
                    break;
                case 'i386':
                case 'i586':
                    $serverMachineType = 'x86 (32 bit)';
                    break;
                default:
                    $serverMachineType = php_uname('m');
                    break;
            }
            $serverInfo = [
                'name'    => php_uname('s'),
                'host'    => php_uname('n'),
                'release' => php_uname('r'),
                'version' => php_uname('v'),
            ];
        }
        $srvIp = gethostbyname($this->informatorInternalArray['superGlobals']->getHttpHost());
        return [
            'OS'              => php_uname(),
            'OS Architecture' => $serverMachineType,
            'OS Date/time'    => date('Y-m-d H:i:s'),
            'OS Ip'           => $srvIp,
            'OS Ip type'      => $this->checkIpIsPrivate($srvIp),
            'OS Ip v4/v6'     => $this->checkIpIsV4OrV6($srvIp),
            'OS Name'         => $serverInfo['name'],
            'OS Host'         => $serverInfo['host'],
            'OS Release'      => $serverInfo['release'],
            'OS Version'      => $serverInfo['version'],
        ];
    }

    private function getTemporaryFolder()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    }

    private function getTomcatDetails()
    {
        $sReturn           = [];
        $sReturn['Tomcat'] = '---';
        $url               = 'http://' . $this->informatorInternalArray['superGlobals']->getHttpHost()
                . ':8080/informator.Tomcat/index.jsp';
        $urlFeedback       = $this->getContentFromUrlThroughCurlAsArrayIfJson($url);
        if (is_array($urlFeedback) && isset($urlFeedback['response'])) {
            $sReturn['Tomcat'] = $urlFeedback['response'];
        }
        return $sReturn;
    }

    private function setInterface()
    {
        $requestedLabel = $this->informatorInternalArray['superGlobals']->get('Label');
        $showLabels     = true;
        $feedback       = '<span style="background-color:red;color:white;">Label not set...</span>';
        $arToReturn     = [];
        if (isset($requestedLabel)) {
            $feedback = '<span style="background-color:red;color:white;">'
                    . 'Unknown label transmited...'
                    . '</span>';
            if (array_key_exists($requestedLabel, $this->informatorInternalArray['knownLabels'])) {
                $showLabels = false;
                $feedback   = '';
                $lblValue   = $this->informatorInternalArray['knownLabels'][$requestedLabel];
                if ($requestedLabel == '--- List of known labels') {
                    $arToReturn = array_keys($this->informatorInternalArray['knownLabels']);
                } elseif ($requestedLabel == 'Auto Dependencies File') {
                    $arToReturn = $lblValue;
                } elseif ($requestedLabel == 'System Info') {
                    $arToReturn = $this->systemInfo();
                } elseif (is_null($lblValue[1])) {
                    $arToReturn = call_user_func([$this, $lblValue[0]]);
                } elseif (is_array($lblValue[1])) {
                    $arToReturn = call_user_func_array([$this, $lblValue[0]], [$lblValue[1]]);
                } elseif (is_string($lblValue[1])) {
                    $arToReturn = call_user_func([$this, $lblValue[0]], $lblValue[1]);
                }
            }
        }
        $outputArray = [
            'showLabels'    => $showLabels,
            'feedback'      => $feedback,
            'arrayToReturn' => $arToReturn,
        ];
        return $this->setOutputInterface($outputArray);
    }

    private function setOutputInterface($inArray)
    {
        if ($inArray['showLabels']) {
            $sReturn    = [];
            $sReturn[]  = $this->setHeaderCommon([
                'lang'  => 'en-US',
                'title' => 'Informator'
            ]);
            $sReturn[]  = $inArray['feedback'] . '<p style="background-color:green;color:white;">'
                    . 'So you might want to choose one from the list below:</p>'
                    . '<ul>';
            $arToReturn = array_keys($this->informatorInternalArray['knownLabels']);
            foreach ($arToReturn as $value) {
                $sReturn[] = '<li>'
                        . '<a href="?Label=' . urlencode($value) . '" target="_blank">' . $value . '</a>'
                        . '</li>';
            }
            $sReturn[] = '</ul>' . $this->setFooterCommon();
            return implode('', $sReturn);
        }
        $this->setHeaderGZiped();
        $this->setHeaderNoCache('application/json');
        echo $this->setArrayToJson($inArray['arrayToReturn']);
        $this->setFooterGZiped();
    }

    /**
     * Builds an array with most important key aspects of LAMP/WAMP
     * @return array
     */
    private function systemInfo()
    {
        $cFile = $this->informatorInternalArray['composerLockFile'];
        return [
            'Apache'            => $this->getApacheDetails(),
            'Auto Dependencies' => $this->getPackageDetailsFromGivenComposerLockFile($cFile),
            'Client'            => $this->getClientBrowserDetailsForInformator(),
            'InfoCompareFile'   => $this->getFileDetails(__FILE__),
            'MySQL'             => $this->getMySQLinfo(),
            'PHP'               => $this->getPhpDetails(),
            'Server'            => $this->getServerDetails(),
            'Tomcat'            => $this->getTomcatDetails()['Tomcat'],
        ];
    }
}
