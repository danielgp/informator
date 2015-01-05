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

class JsonBrain
{

    use \danielgp\common_lib\CommonCode;

    private $filesFromDir;
    protected $mySQLconnection = null;

    public function __construct()
    {
        if (isset($_REQUEST['Label'])) {
            switch ($_REQUEST['Label']) {
                case 'ApacheInfo':
                    echo $this->gate2transmit('getApacheDetails');
                    break;
                case 'ClientInfo':
                    echo $this->gate2transmit('getClientBrowserDetails');
                    break;
                case 'ListOfFiles':
                    echo $this->gate2transmit('getListOfFiles', realpath('.'));
                    break;
                case 'MySQLactiveDatabases':
                    echo $this->gate2transmit('getMySQLactiveDatabases');
                    break;
                case 'MySQLactiveEngines':
                    echo $this->gate2transmit('getMySQLactiveEngines');
                    break;
                case 'MySQLgenericInfo':
                    echo $this->gate2transmit('getMySQLgenericInformations');
                    break;
                case 'MySQLglobalVariables':
                    echo $this->gate2transmit('getMySQLglobalVariables');
                    break;
                case 'MySQLinfo':
                    echo $this->gate2transmit('getMySQLinfo');
                    break;
                case 'PhpInfo':
                    echo $this->gate2transmit('getPhpDetails');
                    break;
                case 'ServerInfo':
                    echo $this->gate2transmit('getServerDetails');
                    break;
                case 'TomcatInfo':
                    echo $this->gate2transmit('getTomcatDetails');
                    break;
                case 'SysInfo':
                    echo $this->gate2transmit('systemInfo', true);
                    break;
                default:
                    echo '<span style="background-color:red;color:white;">Unknown Label... :-(</span>';
                    break;
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
    private function getMySQLactiveDatabases($full = false)
    {
        $this->connectToMySql();
        if (is_null($this->mySQLconnection)) {
            echo '<p style="color:red;">'
            . 'There is no connection to MySQL server, '
            . 'therefore I cannot asses the MySQL active user databases '
            . '(with function `' . __FUNCTION__ . '`)...</p>';
            return null;
        }
        $query     = $this->storedQuery('ActiveDatabases');
        $result    = $this->mySQLconnection->query($query) or die('In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error . '<b/>...');
        $iNoOfRows = $result->num_rows;
        for ($counter = 0; $counter < $iNoOfRows; $counter++) {
            $line[] = $result->fetch_assoc();
        }
        $result->close();
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
        $query     = $this->storedQuery('ActiveEngines');
        $result    = $this->mySQLconnection->query($query) or die('In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error . '<b/>...');
        $iNoOfRows = $result->num_rows;
        for ($counter = 0; $counter < $iNoOfRows; $counter++) {
            $line[] = $result->fetch_assoc();
        }
        $result->close();
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
        $query     = 'SHOW GLOBAL VARIABLES;';
        $result    = $this->mySQLconnection->query($query) or die('In functia <b>'
                . __FUNCTION__ . '</b>'
                . ', nu se poate efectua interogarea '
                . '<b>' . $query . '</b>'
                . ' because <b>' . $this->mySQLconnection->error . '<b/>...');
        $iNoOfRows = $result->num_rows;
        for ($counter = 0; $counter < $iNoOfRows; $counter++) {
            $line                   = $result->fetch_row();
            $array2return[$line[0]] = $line[1];
        }
        $result->close();
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
        $sInfo['Tomcat'] = $this->getContentFromUrlThroughCurl('http://' . $_SERVER['SERVER_NAME'] . ':8080/JavaBridge/TomcatInfos.php')['response'];
        ksort($sInfo['Tomcat']);
        return $sInfo['Tomcat'];
    }

    /**
     * Place for all MySQL queries used within current class
     *
     * @version 20080525
     * @param string $label
     * @param array $given_parameters
     * @return string
     */
    final protected function storedQuery($label, $given_parameters = null)
    {
        require_once 'sql.queries.inc.php';
        $tq = new AppQueries;
        // redirection because of a reserved word
        if ($label == 'use') {
            $label = 'usee';
        }
        // end of redirection
        $sReturn = call_user_func_array([$tq, 'setRightQuery'], [$label, $given_parameters]);
        if ($sReturn === false) {
            echo $this->setFeedback(0, 'Error', 'The MySQL query labeled %s is not defined...' . $label);
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
        $sInfo                    = [];
        $sInfo['Apache']          = $this->getApacheDetails();
        $sInfo['Client']          = $this->getClientBrowserDetails();
        $sInfo['InfoCompareFile'] = $this->getFileDetails(__FILE__);
        $sInfo['MySQL']           = $this->getMySQLinfo();
        $sInfo['PHP']             = $this->getPhpDetails();
        $sInfo['Server']          = $this->getServerDetails();
        $sInfo['Tomcat']          = $this->getTomcatDetails();
        return $sInfo;
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

    /**
     * builds the JSON output based on a given Label
     *
     * @param  type    $label
     * @param  type    $prmtrs
     * @return boolean
     */
    public function gate2transmit($label, $prmtrs = null)
    {
//        global $db;
//        if ($db == null) {
//            $this->setConnectionHost(true);
//        }
        if (method_exists($this, $label)) {
            if (is_null($prmtrs)) {
                $aRreturn = call_user_func([$this, $label]);
            } else {
                if (is_array($prmtrs)) {
                    $aRreturn = call_user_func_array([$this, $label], [$prmtrs]);
                } else {
                    $aRreturn = call_user_func([$this, $label], $prmtrs);
                }
            }
            if (version_compare(phpversion(), "5.4.0", ">=")) {
                $sReturn = utf8_encode(json_encode($aRreturn, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            } else {
                $sReturn = json_encode($aRreturn);
            }
            if (isset($_REQUEST['Readable'])) {
                $result = (json_decode($sReturn, true));
                echo '<pre>';
                print_r($result);
                echo '</pre>';
            } else {
                $this->setHeaderGZiped();
                $this->setHeaderNoCache('application/json');
                echo $sReturn;
                $this->setFooterGZiped();
            }
        } else {
            echo '<span style="background-color:red;color:white;">Functie necunoscuta in `' . __FILE__ . '`!' . PHP_EOL . '(cea cautata este `' . $label . '`)</span>';
            return '';
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

    private function getClientBrowserDetails()
    {
        $uap                     = new \UserAgentParser();
        $br                      = $uap->getBrowser($_SERVER['HTTP_USER_AGENT']);
        $os                      = $uap->getOperatingSystem($_SERVER['HTTP_USER_AGENT']);
        $cVersion                = str_replace('.', '', $br['version']);
        $br['ComparisonVersion'] = $cVersion . str_repeat('0', ((strlen($cVersion) < 3 ? 3 : 4) - strlen($cVersion)));
        $http_accept_language    = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        preg_match_all('/([a-z]{2})(?:-[a-zA-Z]{2})?/', $http_accept_language, $m);
        $preferrences            = [
            'Preferred locale'    => $m[0],
            'Preferred languages' => array_values(array_unique(array_values($m[1])))
        ];
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'x86_64') || strpos($_SERVER['HTTP_USER_AGENT'], 'x86-64') || strpos($_SERVER['HTTP_USER_AGENT'], 'Win64') || strpos($_SERVER['HTTP_USER_AGENT'], 'x64;') || strpos($_SERVER['HTTP_USER_AGENT'], 'amd64') || strpos($_SERVER['HTTP_USER_AGENT'], 'AMD64') || strpos($_SERVER['HTTP_USER_AGENT'], 'WOW64') || strpos($_SERVER['HTTP_USER_AGENT'], 'x64_64')) {
            $br['customAdded']['clientOsArchitecture'] = 'x64 (64 bit)';
        } else {
            $br['customAdded']['clientOsArchitecture'] = 'x86 (32 bit)';
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Win64') && strpos($_SERVER['HTTP_USER_AGENT'], 'x64')) {
            $br['customAdded']['clientBrowserArchitecture'] = 'x64 (64 bit)';
        } else {
            $br['customAdded']['clientBrowserArchitecture'] = 'x86 (32 bit)';
        }
        return [
            'Browser'                           => $br['name'],
            'Browser Detected Version Original' => $br['version'],
            'Browser Detected Version'          => $br['ComparisonVersion'],
            'Browser Preferred locale'          => $preferrences['Preferred locale'],
            'Browser Preferred languages'       => $preferrences['Preferred languages'],
            'Browser Architecture'              => $br['customAdded']['clientBrowserArchitecture'],
            'HTTP_ACCEPT'                       => $_SERVER['HTTP_ACCEPT'],
            'HTTP_ACCEPT_CHARSET'               => @$_SERVER['HTTP_ACCEPT_CHARSET'],
            'HTTP_ACCEPT_ENCODING'              => @$_SERVER['HTTP_ACCEPT_ENCODING'],
            'HTTP_ACCEPT_LANGUAGE'              => @$_SERVER['HTTP_ACCEPT_LANGUAGE'],
            'HTTP_CONNECTION'                   => $_SERVER['HTTP_CONNECTION'],
            'HTTP_HOST'                         => $_SERVER['HTTP_HOST'],
            'HTTP_REFERER'                      => @$_SERVER['HTTP_REFERER'],
            'HTTP_USER_AGENT'                   => $_SERVER['HTTP_USER_AGENT'],
            'IP'                                => $this->getClientRealIpAddress(),
            'IP direct'                         => $_SERVER['REMOTE_ADDR'],
            'OS Name'                           => $os['name'],
            'OS Architecture'                   => $br['customAdded']['clientOsArchitecture']
        ];
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
