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

    private $knownLabels;
    private $composerLockFile;

    public function __construct()
    {
        $this->composerLockFile = realpath('../') . DIRECTORY_SEPARATOR . 'composer.lock';
        $this->knownLabels      = [
            '--- List of known labels' => '',
            'ApacheInfo'               => ['getApacheDetails', null],
            'Auto Dependencies'        => ['getPackageDetailsFromGivenComposerLockFile', $this->composerLockFile],
            'Auto Dependencies file'   => [$this->composerLockFile],
            'ClientInfo'               => ['getClientBrowserDetailsForInformator', null],
            'Informator file details'  => ['getFileDetails', __FILE__],
            'MySQL Databases All'      => ['getMySQLinfo', ['Databases All']],
            'MySQL Databases Client'   => ['getMySQLinfo', ['Databases Client']],
            'MySQL Engines Active'     => ['getMySQLinfo', ['Engines Active']],
            'MySQL Engines All'        => ['getMySQLinfo', ['Engines All']],
            'MySQL General'            => ['getMySQLinfo', ['General']],
            'MySQL Variables Global'   => ['getMySQLinfo', ['Variables Global']],
            'MySQL info'               => ['getMySQLinfo', ['Engines Active', 'General', 'Variables Global']],
            'Php Extensions Loaded'    => ['getPhpDetails', ['Extensions Loaded']],
            'Php General'              => ['getPhpDetails', ['General']],
            'Php INI Settings'         => ['getPhpDetails', ['INI Settings']],
            'Php Stream Filters'       => ['getPhpDetails', ['Stream Filters']],
            'Php Stream Transports'    => ['getPhpDetails', ['Stream Transports']],
            'Php Stream Wrappers'      => ['getPhpDetails', ['Stream Wrappers']],
            'Php info'                 => ['getPhpDetails', null],
            'ServerInfo'               => ['getServerDetails', null],
            'SysInfo'                  => ['systemInfo', null],
            'TomcatInfo'               => ['getTomcatDetails', null],
        ];
        ksort($this->knownLabels);
        echo $this->setInterface();
    }

    protected function connectToMySqlServer()
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
    }

    private function getApacheDetails()
    {
        $srvSoftwareArray = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        foreach ($srvSoftwareArray as $value) {
            $tmp = explode('/', $value);
            if (strpos($value, 'Apache') !== false) {
                $sInfo['Apache'] = [
                    'Name'      => $tmp[0],
                    'Signature' => $_SERVER['SERVER_SOFTWARE'],
                    'Version'   => $tmp[1]
                ];
            }
            if (strpos($value, 'mod_fcgid') !== false) {
                $sInfo['Apache']['Modules']['fcgid'] = [
                    'Name'    => $tmp[0],
                    'Version' => $tmp[1]
                ];
            }
            if (strpos($value, 'OpenSSL') !== false) {
                $sInfo['Apache']['Modules']['ssl'] = [
                    'Name'    => $tmp[0],
                    'Version' => $tmp[1]
                ];
            }
            if (strpos($value, 'SVN') !== false) {
                $sInfo['Apache']['Modules']['svn'] = [
                    'Name'    => 'Subversion',
                    'Version' => $tmp[1]
                ];
            }
        }
        ksort($sInfo['Apache']['Modules']);
        ksort($sInfo['Apache']);
        return $sInfo['Apache'];
    }

    private function getClientBrowserDetailsForInformator()
    {
        $tmpFolder        = $this->getTemporaryFolder();
        $tmpDoctrineCache = null;
        if (is_dir($tmpFolder)) {
            clearstatcache();
            if (is_writable($tmpFolder)) {
                $tmpDoctrineCache = $tmpFolder . DIRECTORY_SEPARATOR . 'DoctrineCache';
                $aReturn          = $this->getClientBrowserDetails(['Browser', 'Device', 'OS'], $tmpDoctrineCache);
            }
        }
        return $this->getClientBrowserDetails(['Browser', 'Device', 'OS'], $tmpDoctrineCache);
    }

    private function getMySQLinfo($returnType = ['Engines Active', 'General', 'Variables Global'])
    {
        $this->connectToMySqlServer();
        $sInfo = [];
        foreach ($returnType as $value) {
            switch ($value) {
                case 'Databases All':
                    $sInfo['MySQL']['Databases All']    = $this->getMySQLlistDatabases(false);
                    break;
                case 'Databases Client':
                    $sInfo['MySQL']['Databases Client'] = $this->getMySQLlistDatabases(true);
                    break;
                case 'Engines Active':
                    $sInfo['MySQL']['Engines Active']   = $this->getMySQLlistEngines(true);
                    break;
                case 'Engines All':
                    $sInfo['MySQL']['Engines All']      = $this->getMySQLlistEngines(false);
                    break;
                case 'General':
                    $sInfo['MySQL']['General']          = $this->getMySQLgenericInformations();
                    break;
                case 'Variables Global':
                    $sInfo['MySQL']['Variables Global'] = $this->getMySQLglobalVariables();
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
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey(stream_get_filters());
                    break;
                case 'Stream Transports':
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey(stream_get_transports());
                    break;
                case 'Stream Wrappers':
                    $sInfo['PHP'][$value] = $this->setArrayValuesAsKey(stream_get_wrappers());
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
            'host'    => $_SERVER['HTTP_HOST'],
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
        return [
            'OS'              => php_uname(),
            'OS Architecture' => $serverMachineType,
            'OS Date/time'    => date('Y-m-d H:i:s'),
            'OS Ip'           => $_SERVER['SERVER_ADDR'],
            'OS Ip type'      => $this->checkIpIsPrivate($_SERVER['SERVER_ADDR']),
            'OS Ip v4/v6'     => $this->checkIpIsV4OrV6($_SERVER['SERVER_ADDR']),
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
        $sReturn['Tomcat'] = '---';
        $url               = 'http://' . $_SERVER['SERVER_NAME'] . ':8080/informator.Tomcat/index.jsp';
        $urlFeedback       = $this->getContentFromUrlThroughCurlAsArrayIfJson($url);
        if (is_array($urlFeedback)) {
            if (isset($urlFeedback['response'])) {
                $sReturn['Tomcat'] = $urlFeedback['response'];
            }
        }
        return $sReturn;
    }

    private function setInterface()
    {
        $sReturn        = [];
        $keysArray      = array_keys($this->knownLabels);
        $rqst           = new \Symfony\Component\HttpFoundation\Request;
        $requestedLabel = $rqst->createFromGlobals()->get('Label');
        if (isset($requestedLabel)) {
            if ($requestedLabel == '--- List of known labels') {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $this->setArrayToJson($keysArray);
                $this->setFooterGZiped();
                $showLabels = false;
            } elseif (in_array($requestedLabel, $keysArray)) {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                $lblValue = $this->knownLabels[$requestedLabel];
                if (is_null($lblValue[1])) {
                    $arToReturn = call_user_func([$this, $lblValue[0]]);
                } elseif (is_array($lblValue[1])) {
                    $arToReturn = call_user_func_array([$this, $lblValue[0]], [$lblValue[1]]);
                } else {
                    $arToReturn = call_user_func([$this, $lblValue[0]], $lblValue[1]);
                }
                echo $this->setArrayToJson($arToReturn);
                $this->setFooterGZiped();
                $showLabels = false;
            } else {
                $feedback   = '<span style="background-color:red;color:white;">There is no valid label transmited...';
                $showLabels = true;
            }
        } else {
            $feedback   = '<span style="background-color:red;color:white;">Label not set...';
            $showLabels = true;
        }
        if ($showLabels) {
            $sReturn[] = $this->setHeaderCommon([
                'lang'  => 'en-US',
                'title' => 'Informator'
            ]);
            $sReturn[] = $feedback . 'So you might want to choose one from the list below:</span>';
            foreach ($keysArray as $value) {
                $sReturn[] = '<br/>'
                        . '<a href="?Label=' . urlencode($value) . '" target="_blank">' . $value . '</a>';
            }
            $sReturn[] = $this->setFooterCommon();
        }
        return implode('', $sReturn);
    }

    /**
     * Builds an array with most important key aspects of LAMP/WAMP
     * @param  boolean $full
     * @return array
     */
    protected function systemInfo()
    {
        return [
            'Apache'            => $this->getApacheDetails(),
            'Auto Dependencies' => $this->getPackageDetailsFromGivenComposerLockFile($this->composerLockFile),
            'Client'            => $this->getClientBrowserDetailsForInformator(),
            'InfoCompareFile'   => $this->getFileDetails(__FILE__),
            'MySQL'             => $this->getMySQLinfo(),
            'PHP'               => $this->getPhpDetails(),
            'Server'            => $this->getServerDetails(),
            'Tomcat'            => $this->getTomcatDetails()['Tomcat'],
        ];
    }
}
