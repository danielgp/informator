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
            'ApacheInfo'               => $this->getApacheDetails(),
            'Auto Dependencies'        => $this->getPackageDetailsFromGivenComposerLockFile($this->composerLockFile),
            'Auto Dependencies file'   => [$this->composerLockFile],
            'ClientInfo'               => $this->getClientBrowserDetails(),
            'Informator file details'  => $this->getFileDetails(__FILE__),
            'MySQL Databases All'      => $this->getMySQLinfo(['Databases All']),
            'MySQL Databases Client'   => $this->getMySQLinfo(['Databases Client']),
            'MySQL Engines Active'     => $this->getMySQLinfo(['Engines Active']),
            'MySQL Engines All'        => $this->getMySQLinfo(['Engines All']),
            'MySQL General'            => $this->getMySQLinfo(['General']),
            'MySQL Variables Global'   => $this->getMySQLinfo(['Variables Global']),
            'MySQL info'               => $this->getMySQLinfo(['Engines Active', 'General', 'Variables Global']),
            'Php Extensions Loaded'    => $this->getPhpDetails(['Extensions Loaded']),
            'Php General'              => $this->getPhpDetails(['General']),
            'Php INI Settings'         => $this->getPhpDetails(['INI Settings']),
            'Php Stream Filters'       => $this->getPhpDetails(['Stream Filters']),
            'Php Stream Transports'    => $this->getPhpDetails(['Stream Transports']),
            'Php Stream Wrappers'      => $this->getPhpDetails(['Stream Wrappers']),
            'Php info'                 => $this->getPhpDetails(['General', 'INI Settings', 'Extensions Loaded']),
            'ServerInfo'               => $this->getServerDetails(),
            'SysInfo'                  => $this->systemInfo(),
            'TomcatInfo'               => $this->getTomcatDetails(),
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
        $ss = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        foreach ($ss as $value) {
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

    private function getMySQLinfo($returnType = ['Engines Active', 'General', 'Variables Global'])
    {
        $this->connectToMySqlServer();
        $sInfo = [];
        foreach ($returnType as $value) {
            switch ($value) {
                case 'Databases All':
                    $sInfo['MySQL']['Engines All']      = $this->getMySQLlistDatabases(false);
                    break;
                case 'Databases Client':
                    $sInfo['MySQL']['Engines Client']   = $this->getMySQLlistDatabases(true);
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

    private function getPhpDetails($returnType = ['General', 'INI Settings', 'Extensions Loaded'])
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
            }
        }
        ksort($sInfo['PHP']);
        return $sInfo['PHP'];
    }

    private function getServerDetails()
    {
        $serverMachineType = 'unknown';
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
        } else {
            $serverInfo = [
                'name'    => 'undisclosed',
                'host'    => $_SERVER['HTTP_HOST'],
                'release' => 'undisclosed',
                'version' => 'undisclosed',
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
        $sReturn   = [];
        $keysArray = array_keys($this->knownLabels);
        if (isset($_REQUEST['Label'])) {
            if ($_REQUEST['Label'] == '--- List of known labels') {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $this->setArrayToJson($keysArray);
                $this->setFooterGZiped();
                $showLabels = false;
            } elseif (in_array($_REQUEST['Label'], $keysArray)) {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $this->setArrayToJson($this->knownLabels[$_REQUEST['Label']]);
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
            'Client'            => $this->getClientBrowserDetails(),
            'InfoCompareFile'   => $this->getFileDetails(__FILE__),
            'MySQL'             => $this->getMySQLinfo(),
            'PHP'               => $this->getPhpDetails(),
            'Server'            => $this->getServerDetails(),
            'Tomcat'            => $this->getTomcatDetails()['Tomcat'],
        ];
    }
}
