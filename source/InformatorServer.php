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

trait InformatorServer
{

    protected function getDoctrineCaheFolder()
    {
        $tmpFolder        = $this->getTemporaryFolder();
        $tmpDoctrineCache = null;
        clearstatcache();
        if (is_dir($tmpFolder) && is_writable($tmpFolder)) {
            $tmpDoctrineCache = $tmpFolder . DIRECTORY_SEPARATOR . 'DoctrineCache';
        }
        return $tmpDoctrineCache;
    }

    protected function getServerDetailsEvaluated($hostName)
    {
        $serverMachineType = 'unknown';
        $serverInfo        = [
            'name'    => 'undisclosed',
            'host'    => $hostName,
            'release' => 'undisclosed',
            'version' => 'undisclosed',
        ];
        if (function_exists('php_uname')) {
            $infServerFromPhp  = $this->getServerDetailsFromPhp();
            $serverMachineType = $infServerFromPhp['OS Architecture'];
            $serverInfo        = $infServerFromPhp['OS Name+Host+Release+Version'];
        }
        return [
            'serverMachineType' => $serverMachineType,
            'serverInfo'        => $serverInfo,
        ];
    }

    private function getServerDetailsFromPhp()
    {
        $aReturn                    = [];
        $aReturn['OS Architecture'] = php_uname('m');
        $knownValues                = [
            'AMD64' => 'x64 (64 bit)',
            'i386'  => 'x86 (32 bit)',
            'i586'  => 'x86 (32 bit)',
        ];
        if (array_key_exists(php_uname('m'), $knownValues)) {
            $aReturn['OS Architecture'] = $knownValues[php_uname('m')];
        }
        $aReturn['OS Name+Host+Release+Version'] = [
            'name'    => php_uname('s'),
            'host'    => php_uname('n'),
            'release' => php_uname('r'),
            'version' => php_uname('v'),
        ];
        return $aReturn;
    }

    protected function getServerSoftware()
    {
        return $_SERVER['SERVER_SOFTWARE'];
    }

    protected function getTemporaryFolder()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR;
    }
}
