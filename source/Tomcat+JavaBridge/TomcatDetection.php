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

/**
 * Description of TomcatDetection
 *
 * @author Daniel Popiniuc
 */
class TomcatDetection
{

    use \danielgp\common_lib\CommonCode;

    public function __construct()
    {
        $this->setHeaderGZiped();
        $this->setHeaderNoCache('application/json');
        echo $this->getJavaBridgeInfo();
        $this->setFooterGZiped();
    }

    private function getJavaBridgeInfo()
    {
        $system      = new \Java('java.lang.System');
        $tomcatInfos = [
            'CATALINA_HOME'                  => filter_var($_SERVER['CATALINA_HOME'], FILTER_SANITIZE_STRING),
            'JAVA_HOME'                      => filter_var($_SERVER['JAVA_HOME'], FILTER_SANITIZE_STRING),
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
            'Version'                        => $_SERVER['SERVER_SIGNATURE']
        ];
        ksort($tomcatInfos);
        return $this->setArrayToJson($tomcatInfos);
    }
}
