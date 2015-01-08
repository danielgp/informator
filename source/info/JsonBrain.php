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

class JsonBrain extends AppQueries
{

    use \danielgp\common_lib\CommonCode;

    private $filesFromDir;
    protected $mySQLconnection = null;

    public function __construct()
    {
        if (isset($_REQUEST['Label'])) {
            $knownLabels = [
                'ApacheInfo'           => $this->getApacheDetails(),
                'ClientInfo'           => $this->getClientBrowserDetails(),
                'ListOfFiles'          => $this->getListOfFiles(realpath('.')),
                'MySQLactiveDatabases' => $this->getMySQLactiveDatabases(),
                'MySQLactiveEngines'   => $this->getMySQLactiveEngines(),
                'MySQLgenericInfo'     => $this->getMySQLgenericInformations(),
                'MySQLglobalVariables' => $this->getMySQLglobalVariables(),
                'MySQLinfo'            => $this->getMySQLinfo(),
                'PhpInfo'              => $this->getPhpDetails(),
                'ServerInfo'           => $this->getServerDetails(),
                'TomcatInfo'           => $this->getTomcatDetails(),
                'SysInfo'              => $this->systemInfo(),
            ];
            if (in_array($_REQUEST['Label'], array_keys($knownLabels))) {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $this->setArray2json($knownLabels[$_REQUEST['Label']]);
                $this->setFooterGZiped();
            } else {
                echo '<span style="background-color:red;color:white;">Unknown Label... :-(</span>';
            }
        } else {
            echo '<span style="background-color:red;color:white;">Label not set... :-(</span>';
        }
    }

    /**
     * returns a list of MySQL databases (except the system ones)
     *
     * @param  boolean $full
     * @return array
     */
    private function getMySQLactiveDatabases()
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            echo '<p style="color:red;">'
            . 'There is no connection to MySQL server, '
            . 'therefore I cannot asses the MySQL active user databases '
            . '(with function `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        $result = $this->mySQLconnection->query($this->sActiveDatabases());
        if ($result) {
            $iNoOfRows = $result->num_rows;
            for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                $line[] = $result->fetch_assoc();
            }
            $result->close();
        } else {
            $line['error'] = 'In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error
                . '<b/>...';
        }
        return $line;
    }

    /**
     * returns a list of MySQL engines |(except the system ones)
     *
     * @return array
     */
    private function getMySQLactiveEngines()
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            echo '<p style="color:red;">'
            . 'There is no connection to MySQL server, '
            . 'therefore I cannot asses the MySQL active engines '
            . '(with function `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        $result = $this->mySQLconnection->query($this->sActiveEngines());
        if ($result) {
            $iNoOfRows = $result->num_rows;
            for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                $line[] = $result->fetch_assoc();
            }
            $result->close();
        } else {
            $line['erorr'] = 'In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error
                . '<b/>...';
        }
        return $line;
    }

    private function getMySQLgenericInformations()
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            echo '<p style="color:red;">'
            . 'There is no connection to MySQL server, '
            . 'therefore I cannot asses the MySQL global variable values '
            . '(with function `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        return [
            'Info'    => $this->mySQLconnection->server_info,
            'Version' => $this->mySQLconnection->server_version
        ];
    }

    /**
     * returns the list of all MySQL global variables
     *
     * @return array
     */
    private function getMySQLglobalVariables()
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            echo '<p style="color:red;">'
            . 'There is no connection to MySQL server, '
            . 'therefore I cannot asses the MySQL global variable values '
            . '(with function `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        $query  = 'SHOW GLOBAL VARIABLES;';
        $result = $this->mySQLconnection->query($query);
        if ($result) {
            $iNoOfRows = $result->num_rows;
            for ($counter = 0; $counter < $iNoOfRows; $counter++) {
                $line                   = $result->fetch_row();
                $array2return[$line[0]] = $line[1];
            }
            $result->close();
        } else {
            $line['error'] = 'In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error . '<b/>...';
        }
        return $array2return;
    }

    private function getMySQLinfo()
    {
        $sInfo                             = [];
        $sInfo['MySQL']                    = $this->getMySQLgenericInformations();
        $sInfo['MySQL']['Engines']         = $this->getMySQLactiveEngines();
        $sInfo['MySQL']['Databases']       = $this->getMySQLactiveDatabases();
        $sInfo['MySQL']['GlobalVariables'] = $this->getMySQLglobalVariables();
        ksort($sInfo['MySQL']);
        return $sInfo['MySQL'];
    }

    private function getPhpDetails()
    {
        $sInfo['PHP']                        = ini_get_all(null, false);
        $sInfo['PHP']['Loaded extensions']   = get_loaded_extensions();
        $sInfo['PHP']['Stream Filters']      = stream_get_filters();
        $sInfo['PHP']['Stream Transports']   = stream_get_transports();
        $sInfo['PHP']['Stream Wrappers']     = stream_get_wrappers();
        $sInfo['PHP']['Version']             = phpversion();
        $sInfo['PHP']['Zend engine version'] = zend_version();
        ksort($sInfo['PHP']);
        return $sInfo['PHP'];
    }

    private function getServerDetails()
    {
        switch (php_uname('m')) {
            case 'AMD64':
                $serverMachineType = 'x64 (64 bit)';
                break;
            case 'i386':
                $serverMachineType = 'x86 (32 bit)';
                break;
            default:
                $serverMachineType = php_uname('m');
                break;
        }
        return [
            'OS Architecture' => $serverMachineType,
            'OS Date/time'    => date('Y-m-d H:i:s'),
            'OS Ip'           => $_SERVER['SERVER_ADDR'],
            'OS Name'         => php_uname('s'),
            'OS Host'         => php_uname('n'),
            'OS Release'      => php_uname('r'),
            'OS Version'      => php_uname('v'),
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

    final protected function connectToMySql()
    {
        if (is_null($this->mySQLconnection)) {
            $mySQLconfig           = [
                'host'     => MYSQL_HOST,
                'port'     => MYSQL_PORT,
                'user'     => MYSQL_USERNAME,
                'password' => MYSQL_PASSWORD,
                'database' => MYSQL_DATABASE,
            ];
            extract($mySQLconfig);
            $this->mySQLconnection = new \mysqli($host, $user, $password, $database, $port);
            if ($this->mySQLconnection->connect_error) {
                $erNo                  = $this->mySQLconnection->connect_errno;
                $erMsg                 = $this->mySQLconnection->connect_error;
                $feedback              = implode('', [
                    'Connection error (',
                    'no.: %d, ',
                    'message from server: %s, ',
                    'host = %s, ',
                    'port: %s, ',
                    'username: %s, ',
                    'database: %s',
                    ')'
                ]);
                echo sprintf($feedback, $erNo, $erMsg, $host, $port, $user, $database);
                $this->mySQLconnection = null;
            }
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
        $dd->discardBotInformation();
        $dd->parse();
        if ($dd->isBot()) {
            // handle bots,spiders,crawlers,...
            return [
                'Bot' => $dd->getBot(),
            ];
        } else {
            $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            preg_match_all('/([a-z]{2})(?:-[a-zA-Z]{2})?/', $http_accept_language, $m);
            $browserInformation   = array_merge($dd->getClient(), [
                'architecture'        => $this->getArchitectureFromUserAgent($userAgent, 'browser'),
                'connection'          => $_SERVER['HTTP_CONNECTION'],
                'host'                => $_SERVER['HTTP_HOST'],
                'preferred locale'    => $m[0],
                'preferred languages' => array_values(array_unique(array_values($m[1]))),
                'referrer'            => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
                'user_agent'          => $_SERVER['HTTP_USER_AGENT'],
                ], $this->getAcceptFomrUserAgent());
            ksort($browserInformation);
            $osInfo               = array_merge($dd->getOs(), [
                'architecture' => $this->getArchitectureFromUserAgent($userAgent, 'os')
            ]);
            ksort($osInfo);
            return [
                'Browser' => $browserInformation,
                'Device'  => [
                    'brand'     => $dd->getDevice(),
                    'ip'        => $this->getClientRealIpAddress(),
                    'ip direct' => $_SERVER['REMOTE_ADDR'],
                    'model'     => $dd->getModel(),
                    'name'      => $dd->getBrand(),
                ],
                'OS'      => $osInfo,
            ];
        }
    }

    /**
     * returns the details about Communicator (current) file
     *
     * @return array
     */
    private function getFileDetails($fileGiven)
    {
        return [
            'Name'                      => $fileGiven,
            'Size'                      => filesize($fileGiven),
            'Sha1'                      => sha1_file($fileGiven),
            'TimestampAccessed'         => fileatime($fileGiven),
            'TimestampAccessedReadable' => date('Y-m-d H:i:s', fileatime($fileGiven)),
            'TimestampChanged'          => filectime(__FILE__),
            'TimestampChangedReadable'  => date('Y-m-d H:i:s', filectime($fileGiven)),
            'TimestampModified'         => filemtime(__FILE__),
            'TimestampModifiedReadable' => date('Y-m-d H:i:s', filemtime($fileGiven)),
        ];
    }

    /**
     * returns a multi-dimensional array with list of file details within a given path
     * @param  string $pathAnalised
     * @return array
     */
    private function getListOfFiles($pathAnalised)
    {
        if (!file_exists($pathAnalised)) {
            return null;
        }
        $dir                = dir($pathAnalised);
        $this->filesFromDir = 0;
        $fileDetails        = null;
        while ($file               = $dir->read()) {
            clearstatcache();
            $fName     = $pathAnalised . '/' . $file;
            $fileParts = pathinfo($fName);
            switch ($fileParts['basename']) {
                case '.':
                case '..':
                    break;
                default:
                    if (is_dir($fName)) {
                        $fileDetails[$fName] = $this->getListOfFiles($fName);
                    } else {
                        $this->filesFromDir += 1;
                        $xt                  = (isset($fileParts['extension']) ? $fileParts['extension'] : '-');
                        $fileDetails[$fName] = [
                            'Folder'                    => $fileParts['dirname'],
                            'BaseName'                  => $fileParts['basename'],
                            'Extension'                 => $xt,
                            'FileName'                  => $fileParts['filename'],
                            'Size'                      => filesize($fName),
                            'Sha1'                      => sha1_file($fName),
                            'TimestampAccessed'         => fileatime($fName),
                            'TimestampAccessedReadable' => date('Y-m-d H:i:s', fileatime($fName)),
                            'TimestampChanged'          => filectime($fName),
                            'TimestampChangedReadable'  => date('Y-m-d H:i:s', filectime($fName)),
                            'TimestampModified'         => filemtime($fName),
                            'TimestampModifiedReadable' => date('Y-m-d H:i:s', filemtime($fName)),
                        ];
                    }
                    break;
            }
        }
        $dir->close();
        return $fileDetails;
    }
}
