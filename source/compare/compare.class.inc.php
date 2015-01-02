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

namespace danielgp\info_compare;

/**
 * Description of compare
 *
 * @author Transformer-
 */
class Compare
{

    use CommonCode;

    private $localConfiguration;
    private $serverConfiguration;

    public function __construct()
    {
        $this->applicationFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => 'ro_RO',
            'error_dir'           => pathinfo(ini_get('error_log'))['dirname'],
            'error_file'          => 'php' . PHP_VERSION_ID . 'errors_info-compare_' . date('Y-m-d') . '.log',
            'name'                => 'Info-Compare'
        ];

        // generate an error log file that is for this module only and current date
        ini_set('error_log', $this->applicationFlags['error_dir'] . '/' . $this->applicationFlags['error_file']);
        echo $this->setHeaderHtml();
        $this->processInfos();
        echo $this->setFormCurlInfos();
        echo $this->setFormInfos();
        echo $this->setFormOptions();
        echo $this->setFooterHtml();
    }

    private function displayTableFromMultiLevelArray($firstArray, $secondArray)
    {
        global $cfg;
        if ((!is_array($firstArray)) || (!is_array($secondArray))) {
            return '';
        }
        $row = null;
        foreach ($firstArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            if (is_array($value3)) {
                                foreach ($value3 as $key4 => $value4) {
                                    $row[$key . '_' . $key2 . '__' . $key3 . '__' . $key4]['first']  = $value4;
                                    $row[$key . '_' . $key2 . '__' . $key3 . '__' . $key4]['second'] = $secondArray[$key][$key2][$key3][$key4];
                                }
                            } else {
                                $row[$key . '_' . $key2 . '__' . $key3]['first']  = $value3;
                                $row[$key . '_' . $key2 . '__' . $key3]['second'] = $secondArray[$key][$key2][$key3];
                            }
                        }
                    } else {
                        $row[$key . '_' . $key2]['first']  = $value2;
                        $row[$key . '_' . $key2]['second'] = $secondArray[$key][$key2];
                    }
                }
            } else {
                $row[$key]['first']  = $value;
                $row[$key]['second'] = $secondArray[$key];
            }
        }
        foreach ($secondArray as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            if (is_array($value3)) {
                                foreach ($value3 as $key4 => $value4) {
                                    $row[$key . '_' . $key2 . '__' . $key3 . '__' . $key4]['second'] = $value4;
                                    $row[$key . '_' . $key2 . '__' . $key3 . '__' . $key4]['first']  = $firstArray[$key][$key2][$key3][$key4];
                                }
                            } else {
                                $row[$key . '_' . $key2 . '__' . $key3]['second'] = $value3;
                                $row[$key . '_' . $key2 . '__' . $key3]['first']  = $firstArray[$key][$key2][$key3];
                            }
                        }
                    } else {
                        $row[$key . '_' . $key2]['second'] = $value2;
                        $row[$key . '_' . $key2]['first']  = $firstArray[$key][$key2];
                    }
                }
            } else {
                $row[$key]['second'] = $value;
                $row[$key]['first']  = $firstArray[$key];
            }
        }
        ksort($row);
        $urlArguments = '?Label=' . $cfg['Defaults']['Label'];
        $sString[]    = '<table style="width:100%">'
            . '<thead><tr>'
            . '<th>Identifier</th>'
            . '<th><a href="' . $cfg['Servers'][$_REQUEST['localConfig']]['url'] . $urlArguments . '" target="_blank">'
            . $cfg['Servers'][$_REQUEST['localConfig']]['name'] . '</a></th>'
            . '<th><a href="' . $cfg['Servers'][$_REQUEST['serverConfig']]['url'] . $urlArguments . '" target="_blank">'
            . $cfg['Servers'][$_REQUEST['serverConfig']]['name'] . '</a></th>'
            . '</tr></thead>'
            . '<tbody>';
        if ($_REQUEST['displayOnlyDifferent'] == '1') {
            $displayOnlyDifferent = true;
        } else {
            $displayOnlyDifferent = false;
        }
        foreach ($row as $key => $value) {
            $rowString = '<tr><td style="width:20%;">' . $key . '</td><td style="width:40%;">'
                . str_replace(',', ', ', $value['first']) . '</td><td style="width:40%;">'
                . str_replace(',', ', ', $value['second']) . '</td></tr>';
            if ($displayOnlyDifferent) {
                if ($value['first'] != $value['second']) {
                    $sString[] = $rowString;
                }
            } else {
                $sString[] = $rowString;
            }
        }
        $sString[] = '</tbody></table>';
        return implode('', $sString);
    }

    private function processInfos()
    {
        global $cfg;
        if (!isset($_REQUEST['displayOnlyDifferent'])) {
            $_REQUEST['displayOnlyDifferent'] = '1';
        }
        if (!isset($_REQUEST['localConfig'])) {
            $_REQUEST['localConfig'] = $cfg['Defaults']['Source'];
        }
        if (!isset($_REQUEST['serverConfig'])) {
            $_REQUEST['serverConfig'] = $cfg['Defaults']['Target'];
        }
        if (isset($_REQUEST['localConfig']) && isset($_REQUEST['serverConfig'])) {
            $urlArguments              = '?Label=' . $cfg['Defaults']['Label'];
            $source                    = $cfg['Servers'][$_REQUEST['localConfig']]['url'] . $urlArguments;
            $this->localConfiguration  = $this->getContentFromUrlThroughCurl($source);
            $destination               = $cfg['Servers'][$_REQUEST['serverConfig']]['url'] . $urlArguments;
            $this->serverConfiguration = $this->getContentFromUrlThroughCurl($destination);
        } else {
            $this->localConfiguration  = ['response' => '', 'info' => ''];
            $this->serverConfiguration = ['response' => '', 'info' => ''];
        }
    }

    /**
     * Converts an array to string
     *
     * @version 20141217
     * @param string $sSeparator
     * @param array $aElements
     * @return string
     */
    private function setArray2String4Url($sSeparator, $aElements, $aExceptedElements = [''])
    {
        if (!is_array($aElements)) {
            return '';
        }
        $sReturn = [];
        reset($aElements);
        foreach ($aElements as $key => $value) {
            if (!in_array($key, $aExceptedElements)) {
                if (is_array($aElements[$key])) {
                    $aCounter = count($aElements[$key]);
                    for ($counter2 = 0; $counter2 < $aCounter; $counter2++) {
                        if ($value[$counter2] != '') {
                            $sReturn[] = $key . '[]=' . $value[$counter2];
                        }
                    }
                } else {
                    if ($value != '') {
                        $sReturn[] = $key . '=' . $value;
                    }
                }
            }
        }
        return implode($sSeparator, $sReturn);
    }

    private function setClearBoth1px($height = 1)
    {
        return $this->setStringIntoTag('&nbsp;', 'div', [
                'style' => 'height:' . $height . 'px;line-height:1px;float:none;clear:both;margin:0px;'
        ]);
    }

    /**
     * Returns css link to a given file
     *
     * @param string $cssFile
     * @return string
     */
    private function setCssFile($cssFile)
    {
        return '<link rel="stylesheet" type="text/css" href="' . $cssFile . '" />';
    }

    private function setFooterHtml()
    {
        $sReturn   = [];
        $sReturn[] = '</div><!-- from main Tabber -->';
        $sReturn[] = '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>';
        $sReturn[] = '<hr/>';
        $sReturn[] = '<div class="disclaimer">'
            . 'The developer cannot be liable of any data input or results, '
            . 'included but not limited to any implication of these '
            . '(anywhere and whomever there might be these)!'
            . '</div>';
        $sReturn[] = '</body>';
        $sReturn[] = '</html>';
        return implode('', $sReturn);
    }

    private function setFormCurlInfos()
    {
        $source      = $this->localConfiguration['info'];
        $destination = $this->serverConfiguration['info'];
        return '<div class="tabbertab" id="tabCurl" title="CURL infos">'
            . $this->displayTableFromMultiLevelArray($source, $destination)
            . '</div><!--from tabCurl-->';
    }

    private function setFormInfos()
    {
        $source      = $this->localConfiguration['response'];
        $destination = $this->serverConfiguration['response'];
        return '<div class="tabbertab" id="tabConfigs" title="Informations">'
            . $this->displayTableFromMultiLevelArray($source, $destination)
            . '</div><!--from tabConfigs-->';
    }

    private function setFormOptions()
    {
        global $cfg;
        $sReturn    = [];
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>Type of results to be displayed</legend>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayOnlyDifferent" value="1" '
            . ($_REQUEST['displayOnlyDifferent'] == '1' ? 'checked ' : '')
            . '/><label for="displayOnlyDifferent">Only the Different values</label>'
            . '<br/>'
            . '<input type="radio" name="displayOnlyDifferent" id="displayAll" value="0" '
            . ($_REQUEST['displayOnlyDifferent'] == '0' ? 'checked ' : '')
            . '/><label for="displayAll">All</label>'
            . '</fieldset>';
        $tmpOptions = [];
        foreach ($cfg['Servers'] as $key => $value) {
            $tmpOptions[] = '<input type="radio" name="localConfig" id="localConfig_'
                . $key . '" value="' . $key . '" '
                . ($_REQUEST['localConfig'] == $key ? 'checked ' : '')
                . '/><label for="localConfig_' . $key . '">'
                . $value['name'] . '</label>';
        }
        $sReturn[]  = '<fieldset style="float:left;">'
            . '<legend>List of source configuration providers</legend>'
            . implode('<br/>', $tmpOptions)
            . '</fieldset>';
        unset($tmpOptions);
        $tmpOptions = [];
        foreach ($cfg['Servers'] as $key => $value) {
            $tmpOptions[] = '<input type="radio" name="serverConfig" id="serverConfig_'
                . $key . '" value="' . $key . '" '
                . ($_REQUEST['serverConfig'] == $key ? 'checked ' : '')
                . '/><label for="serverConfig_' . $key . '">'
                . $value['name'] . '</label>';
        }
        $sReturn[] = '<fieldset style="float:left;">'
            . '<legend>List of target configuration providers</legend>'
            . implode('<br/>', $tmpOptions) . '</fieldset>';
        return '<div class="tabbertab'
            . ((!isset($_REQUEST['localConfig']) && !isset($_REQUEST['serverConfig'])) ? ' tabbertabtabdefault' : '')
            . '" id="tabOptions" title="Options">'
            . '<style>label { width: auto; }</style>'
            . '<form method="get" action="' . $_SERVER['PHP_SELF'] . '"><input type="submit" value="Apply" /><br/>' . implode('', $sReturn) . '</form>'
            . $this->setClearBoth1px()
            . '</div><!--from tabOptions-->';
    }

    private function setHeaderHtml()
    {
        return '<!DOCTYPE html>'
            . '<html lang="en-US">'
            . '<head>'
            . '<meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . $this->applicationFlags['name'] . '</title>'
            . $this->setCssFile('css/main.css')
            . $this->setJavascriptFile('js/tabber.min.js')
            . '</head>'
            . '<body>'
            . $this->setJavascriptContent('document.write(\'<style type="text/css">.tabber{display:none;}</style>\');')
            . '<h1>' . $this->applicationFlags['name'] . '</h1>'
            . '<div class="tabber" id="tab">'
        ;
    }

    /**
     * Returns javascript codes
     *
     * @param string $javascriptContent
     * @return string
     */
    final protected function setJavascriptContent($javascriptContent)
    {
        return '<script type="text/javascript">' . $javascriptContent . '</script>';
    }

    /**
     * Returns javascript link to a given file
     *
     * @param string $content
     * @return string
     */
    final protected function setJavascriptFile($content)
    {
        return '<script type="text/javascript" src="' . $content . '"></script>';
    }

    /**
     * Puts a given string into a specific short tag
     *
     * @param string $sTag
     * @param array $features
     * @return string
     */
    public function setStringIntoShortTag($sTag, $features = null)
    {
        $attributes = '';
        if ($features != null) {
            foreach ($features as $key => $value) {
                if ($key != 'dont_close') {
                    $attributes .= ' ' . $key . '="';
                    if (is_array($value)) {
                        foreach ($value as $key2 => $value2) {
                            $attributes .= $key2 . ':' . $value2 . ';';
                        }
                    } else {
                        $attributes .= str_replace('"', '\'', $value);
                    }
                    $attributes .= '"';
                }
            }
        }
        if (isset($features['dont_close'])) {
            $sReturn = '<' . $sTag . $attributes . '>';
        } else {
            $sReturn = '<' . $sTag . $attributes . ' />';
        }
        return $sReturn;
    }

    /**
     * Puts a given string into a specific tag
     *
     * @param string $sString
     * @param string $sTag
     * @param array $features
     * @return string
     */
    public function setStringIntoTag($sString, $sTag, $features = null)
    {
        $attributes = '';
        if ($features != null) {
            foreach ($features as $key => $value) {
                $attributes .= ' ' . $key . '="';
                if (is_array($value)) {
                    foreach ($value as $key2 => $value2) {
                        $attributes .= $key2 . ':' . $value2 . ';';
                    }
                } else {
                    $attributes .= $value;
                }
                $attributes .= '"';
            }
        }
        return '<' . $sTag . $attributes . '>' . $sString . '</' . $sTag . '>';
    }
}
