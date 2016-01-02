<?php

/*
 * The MIT License
 *
 * Copyright 2016 Daniel Popiniuc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\informator;

trait InformatorKnownLabels
{

    protected function knownLabelsForMySql()
    {
        return [
            'Databases All'    => ['getMySQLlistDatabases', false],
            'Databases Client' => ['getMySQLlistDatabases', true],
            'Engines Active'   => ['getMySQLlistEngines', true],
            'Engines All'      => ['getMySQLlistEngines', false],
            'General'          => ['getMySQLgenericInformations'],
            'Variables Global' => ['getMySQLglobalVariables'],
        ];
    }

    protected function knownLabelsForPhp()
    {
        return [
            'Extensions Loaded' => ['setArrayValuesAsKey', 'get_loaded_extensions'],
            'Stream Filters'    => ['setArrayValuesAsKey', 'stream_get_filters'],
            'Stream Transports' => ['setArrayValuesAsKey', 'stream_get_transports'],
            'Stream Wrappers'   => ['setArrayValuesAsKey', 'stream_get_wrappers'],
            'Temporary Folder'  => ['getTemporaryFolder'],
        ];
    }

    protected function knownLabelsGlobal($inArray)
    {
        $aReturn = [
            '--- List of known labels' => '',
            'Apache Info'              => ['getApacheDetails'],
            'Auto Dependencies'        => ['getPackageDetailsFromGivenComposerLockFile', $inArray['composerLockFile']],
            'Auto Dependencies File'   => [$inArray['composerLockFile']],
            'Client Info'              => ['getClientBrowserDetailsForInformator', null],
            'Informator File Details'  => ['getFileDetails', $inArray['informatorFile']],
            'Server Info'              => ['getServerDetails'],
            'System Info'              => ['systemInfo'],
            'Tomcat Info'              => ['getTomcatDetails'],
        ];
        return array_merge($aReturn, array_merge($this->subLabelsMySql(), $this->subLabelsPhp()));
    }

    private function subLabelsMySql()
    {
        return [
            'MySQL Databases All'    => ['getMySQLinfo', ['Databases All']],
            'MySQL Databases Client' => ['getMySQLinfo', ['Databases Client']],
            'MySQL Engines Active'   => ['getMySQLinfo', ['Engines Active']],
            'MySQL Engines All'      => ['getMySQLinfo', ['Engines All']],
            'MySQL General'          => ['getMySQLinfo', ['General']],
            'MySQL Variables Global' => ['getMySQLinfo', ['Variables Global']],
            'MySQL Info'             => ['getMySQLinfo', ['Engines Active', 'General', 'Variables Global']],
        ];
    }

    private function subLabelsPhp()
    {
        return [
            'Php Extensions Loaded' => ['getPhpDetails', ['Extensions Loaded']],
            'Php General'           => ['getPhpDetails', ['General']],
            'Php INI Settings'      => ['getPhpDetails', ['INI Settings']],
            'Php Stream Filters'    => ['getPhpDetails', ['Stream Filters']],
            'Php Stream Transports' => ['getPhpDetails', ['Stream Transports']],
            'Php Stream Wrappers'   => ['getPhpDetails', ['Stream Wrappers']],
            'Php Info'              => ['getPhpDetails'],
        ];
    }
}
