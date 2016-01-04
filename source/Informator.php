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

    use \danielgp\common_lib\CommonCode,
        InformatorDynamicFunctions,
        InformatorKnownLabels,
        InformatorServer,
        ConfigurationMySQL;

    private $informatorInternalArray;

    public function __construct()
    {
        $this->informatorInternalArray['composerLockFile'] = 'composer.lock';
        $this->informatorInternalArray['knownLabels']      = $this->knownLabelsGlobal([
            'composerLockFile' => $this->informatorInternalArray['composerLockFile'],
            'informatorFile'   => __FILE__,
        ]);
        ksort($this->informatorInternalArray['knownLabels']);
        $rqst                                              = new \Symfony\Component\HttpFoundation\Request;
        $this->informatorInternalArray['superGlobals']     = $rqst->createFromGlobals();
        echo $this->setInterface();
    }

    private function connectToMySqlForInformation()
    {
        if (is_null($this->mySQLconnection)) {
            $this->connectToMySql($this->configuredMySqlServer());
        }
    }

    private function getApacheDetails()
    {
        $srvSoftware      = $this->informatorInternalArray['superGlobals']->server->get('SERVER_SOFTWARE');
        $srvSoftwareArray = explode(' ', $srvSoftware);
        $sInfo            = [];
        $tmp              = explode('/', $srvSoftwareArray[0]);
        if (strpos($srvSoftwareArray[0], 'Apache') !== false) {
            $sInfo['Apache'] = [
                'Name'      => $tmp[0],
                'Signature' => $srvSoftware,
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
        return $this->getClientBrowserDetails(['Browser', 'Device', 'OS'], $this->getDoctrineCaheFolder());
    }

    private function getMySQLinfo($returnType = ['Databases Client', 'Engines Active', 'General', 'Variables Global'])
    {
        $this->connectToMySqlForInformation();
        $sInfo   = [];
        $mLabels = $this->knownLabelsForMySql();
        foreach ($returnType as $value) {
            $sInfo['MySQL'][$value] = $this->callDynamicFunctionToGetResults($mLabels[$value]);
        }
        ksort($sInfo['MySQL']);
        return $sInfo['MySQL'];
    }

    private function getPhpDetails($returnType = ['General', 'INI Settings', 'Extensions Loaded', 'Temporary Folder'])
    {
        $sInfo = [];
        foreach ($returnType as $value) {
            $sInfo['PHP'][$value] = $this->getPhpDetailsIndividually($value);
        }
        ksort($sInfo['PHP']);
        return $sInfo['PHP'];
    }

    private function getPhpDetailsIndividually($value)
    {
        switch ($value) {
            case 'General':
                $sInfo = [
                    'Version'             => phpversion(),
                    'Zend Engine Version' => zend_version(),
                ];
                break;
            case 'INI Settings':
                $sInfo = ini_get_all(null, false);
                break;
            default:
                $sInfo = $this->callDynamicFunctionToGetResults($this->knownLabelsForPhp()[$value]);
                break;
        }
        return $sInfo;
    }

    private function getServerDetails()
    {
        $hst          = $this->informatorInternalArray['superGlobals']->getHttpHost();
        $srvIp        = filter_var(gethostbyname($hst), FILTER_VALIDATE_IP);
        $srvEvaluated = $this->getServerDetailsEvaluated($hst);
        return [
            'OS'              => php_uname(),
            'OS Architecture' => $srvEvaluated['serverMachineType'],
            'OS Date/time'    => date('Y-m-d H:i:s'),
            'OS Ip'           => $srvIp,
            'OS Ip type'      => $this->checkIpIsPrivate($srvIp),
            'OS Ip v4/v6'     => $this->checkIpIsV4OrV6($srvIp),
            'OS Name'         => $srvEvaluated['serverInfo']['name'],
            'OS Host'         => $srvEvaluated['serverInfo']['host'],
            'OS Release'      => $srvEvaluated['serverInfo']['release'],
            'OS Version'      => $srvEvaluated['serverInfo']['version'],
        ];
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
        $outputArray    = [
            'showLabels'    => true,
            'feedback'      => '<span style="background-color:red;color:white;">Label not set...</span>',
            'arrayToReturn' => [],
        ];
        $requestedLabel = $this->informatorInternalArray['superGlobals']->get('Label');
        if (isset($requestedLabel)) {
            $outputArray['feedback'] = '<span style="background-color:red;color:white;">'
                    . 'Unknown label transmited...'
                    . '</span>';
            if (array_key_exists($requestedLabel, $this->informatorInternalArray['knownLabels'])) {
                $lblValue    = $this->informatorInternalArray['knownLabels'][$requestedLabel];
                $outputArray = [
                    'showLabels'    => false,
                    'feedback'      => '',
                    'arrayToReturn' => $this->performLabelDefinition($requestedLabel, $lblValue),
                ];
            }
        }
        return $this->setOutputInterface($outputArray);
    }

    private function performLabelDefinition($requestedLabel, $lblValue)
    {
        switch ($requestedLabel) {
            case '--- List of known labels':
                $arToReturn = array_keys($this->informatorInternalArray['knownLabels']);
                break;
            case 'Auto Dependencies File':
                $arToReturn = $lblValue;
                break;
            case 'System Info':
                $arToReturn = $this->systemInfo();
                break;
            default:
                $arToReturn = $this->callDynamicFunctionToGetResults($lblValue);
                break;
        }
        return $arToReturn;
    }

    private function setOutputInterface($inArray)
    {
        if ($inArray['showLabels']) {
            return $this->setOutputWithLabels($inArray);
        }
        $this->setHeaderGZiped();
        $this->setHeaderNoCache('application/json');
        echo $this->setArrayToJson($inArray['arrayToReturn']);
        $this->setFooterGZiped();
    }

    private function setOutputWithLabels($inArray)
    {
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
