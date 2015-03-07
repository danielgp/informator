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

    public function __construct()
    {
        if (substr($_REQUEST['Label'], 0, 5) == 'MySQL') {
            $this->connectToMySqlServer();
        }
        $knownLabels = [
            'ApacheInfo'           => $this->getApacheDetails(),
            'ClientInfo'           => $this->getClientBrowserDetails(),
            'MySQLactiveDatabases' => $this->getMySQLactiveDatabases(),
            'MySQLactiveEngines'   => $this->getMySQLactiveEngines(),
            'MySQLgenericInfo'     => $this->getMySQLgenericInformations(),
            'MySQLglobalVariables' => $this->getMySQLglobalVariables(),
            'MySQLinfo'            => $this->getMySQLinfo(),
            'PhpInfo'              => $this->getPhpDetails(),
            'ServerInfo'           => $this->getServerDetails(),
            'SysInfo'              => $this->systemInfo(),
            'TomcatInfo'           => $this->getTomcatDetails(),
        ];
        $keysArray   = array_keys($knownLabels);
        if (isset($_REQUEST['Label'])) {
            if (in_array($_REQUEST['Label'], $keysArray)) {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $this->setArray2json($knownLabels[$_REQUEST['Label']]);
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
            echo $this->setHeaderCommon([
                'lang'  => 'en-US',
                'title' => 'Informator'
            ]);
            echo $feedback . 'So you might want to choose one from the list below:</span>';
            foreach ($keysArray as $value) {
                echo '<br/>'
                . '<a href="?Label=' . $value . '" target="_blank">' . $value . '</a>';
            }
            echo $this->setFooterCommon();
        }
    }

    private function getMySQLinfo()
    {
        $sInfo                             = [];
        $sInfo['MySQL']                    = $this->getMySQLgenericInformations();
        $sInfo['MySQL']['Engines']         = $this->getMySQLactiveEngines();
        $sInfo['MySQL']['GlobalVariables'] = $this->getMySQLglobalVariables();
        ksort($sInfo['MySQL']);
        return $sInfo['MySQL'];
    }

    private function getPhpDetails()
    {
        $sInfo['PHP']                        = ini_get_all(null, false);
        $sInfo['PHP']['Loaded extensions']   = $this->setArrayValuesAsKey(get_loaded_extensions());
        $sInfo['PHP']['Stream Filters']      = $this->setArrayValuesAsKey(stream_get_filters());
        $sInfo['PHP']['Stream Transports']   = $this->setArrayValuesAsKey(stream_get_transports());
        $sInfo['PHP']['Stream Wrappers']     = $this->setArrayValuesAsKey(stream_get_wrappers());
        $sInfo['PHP']['Version']             = phpversion();
        $sInfo['PHP']['Zend engine version'] = zend_version();
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
                    $serverMachineType = 'x86 (32 bit)';
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
            'OS Architecture' => $serverMachineType,
            'OS Date/time'    => date('Y-m-d H:i:s'),
            'OS Ip'           => $_SERVER['SERVER_ADDR'],
            'OS Name'         => $serverInfo['name'],
            'OS Host'         => $serverInfo['host'],
            'OS Release'      => $serverInfo['release'],
            'OS Version'      => $serverInfo['version'],
        ];
    }

    private function getTomcatDetails()
    {
        $url             = 'http://' . $_SERVER['SERVER_NAME'] . ':8080/JavaBridge/TomcatInfos.php';
        $sInfo['Tomcat'] = $this->getContentFromUrlThroughCurl($url)['response'];
        $sReturn         = '';
        if ($this->isJson($sInfo['Tomcat'])) {
            $sReturn['Tomcat'] = $this->setJson2array($sInfo['Tomcat']);
            ksort($sReturn);
        } else {
            $sReturn['Tomcat'] = ['-'];
        }
        return $sReturn;
    }

    /**
     * Builds an array with most important key aspects of LAMP/WAMP
     * @param  boolean $full
     * @return array
     */
    protected function systemInfo()
    {
        return [
            'Apache'          => $this->getApacheDetails(),
            'Client'          => $this->getClientBrowserDetails(),
            'InfoCompareFile' => $this->getFileDetails(__FILE__),
            'MySQL'           => $this->getMySQLinfo(),
            'PHP'             => $this->getPhpDetails(),
            'Server'          => $this->getServerDetails(),
            'Tomcat'          => $this->getTomcatDetails(),
        ];
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

    private function getAcceptFomrUserAgent()
    {
        $sReturn           = [];
        $sReturn['accept'] = $_SERVER['HTTP_ACCEPT'];
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $sReturn['accept_charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $sReturn['accept_encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $sReturn['accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        return $sReturn;
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

    private function getArchitectureFromUserAgent($userAgent, $targetToAnalyze = 'os')
    {
        $isX64 = false;
        switch ($targetToAnalyze) {
            case 'browser':
                if (strpos($userAgent, 'Win64') && strpos($userAgent, 'x64')) {
                    $isX64 = true;
                }
                break;
            case 'os':
                if (strpos($userAgent, 'x86_64')) {
                    $isX64 = true;
                } elseif (strpos($userAgent, 'x86-64')) {
                    $isX64 = true;
                } elseif (strpos($userAgent, 'Win64')) {
                    $isX64 = true;
                } elseif (strpos($userAgent, 'x64;')) {
                    $isX64 = true;
                } elseif (strpos(strtolower($userAgent), 'amd64')) {
                    $isX64 = true;
                } elseif (strpos($userAgent, 'WOW64')) {
                    $isX64 = true;
                } elseif (strpos($userAgent, 'x64_64')) {
                    $isX64 = true;
                }
                break;
            default:
                return 'Unknown target to analyze...';
                break;
        }
        if ($isX64) {
            return 'x64 (64 bit)';
        } else {
            return 'x86 (32 bit)';
        }
    }

    private function getClientBrowserDetails()
    {
        if (isset($_GET['ua'])) {
            $userAgent = $_GET['ua'];
        } else {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }
        $dd = new \DeviceDetector\DeviceDetector($userAgent);
        $dd->setCache(new \Doctrine\Common\Cache\PhpFileCache('../../tmp/'));
        $dd->discardBotInformation();
        $dd->parse();
        if ($dd->isBot()) {
            return [
                'Bot' => $dd->getBot(), // handle bots,spiders,crawlers,...
            ];
        } else {
            $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            preg_match_all('/([a-z]{2})(?:-[a-zA-Z]{2})?/', $http_accept_language, $m);
            $br                   = new \DeviceDetector\Parser\Client\Browser();
            $browserFamily        = $br->getBrowserFamily($dd->getClient('short_name'));
            $browserInformation   = array_merge($dd->getClient(), [
                'architecture'        => $this->getArchitectureFromUserAgent($userAgent, 'browser'),
                'connection'          => $_SERVER['HTTP_CONNECTION'],
                'family'              => ($browserFamily !== false ? $browserFamily : 'Unknown'),
                'host'                => $_SERVER['HTTP_HOST'],
                'preferred locale'    => $m[0],
                'preferred languages' => array_values(array_unique(array_values($m[1]))),
                'referrer'            => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
                'user_agent'          => $dd->getUserAgent(),
                ], $this->getAcceptFomrUserAgent());
            ksort($browserInformation);
            $os                   = new \DeviceDetector\Parser\OperatingSystem();
            $osFamily             = $os->getOsFamily($dd->getOs('short_name'));
            $osInfo               = array_merge($dd->getOs(), [
                'architecture' => $this->getArchitectureFromUserAgent($userAgent, 'os'),
                'family'       => ($osFamily !== false ? $osFamily : 'Unknown')
            ]);
            ksort($osInfo);
            return [
                'Browser' => $browserInformation,
                'Device'  => [
                    'brand'     => $dd->getDeviceName(),
                    'ip'        => $this->getClientRealIpAddress(),
                    'ip direct' => $_SERVER['REMOTE_ADDR'],
                    'model'     => $dd->getModel(),
                    'name'      => $dd->getBrandName(),
                ],
                'OS'      => $osInfo,
            ];
        }
    }
}
