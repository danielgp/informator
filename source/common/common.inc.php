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

trait CommonCode
{

    protected function getContentFromUrlThroughCurl($fullURL, $features = null)
    {
        if (!function_exists('curl_init')) {
            return 'CURL extension is not available... therefore the informations to be obtained by funtion named ' . __FUNCTION__ . ' from ' . __FILE__ . ' could not be obtained!';
        }
        $aReturn = [];
        $ch      = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        if ((strpos($fullURL, "https") !== false) || (isset($features['forceSSLverification']))) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_URL, $fullURL);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true); //avoid a cached response
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        $responseJsonFromClientOriginal = curl_exec($ch);
        if (curl_errno($ch)) {
            $aReturn['error_CURL'] = ['#' => curl_errno($ch), 'description' => curl_error($ch)];
            $aReturn['response']   = [''];
            $aReturn['info']       = [''];
        } else {
            $aReturn['response'] = (json_decode($responseJsonFromClientOriginal, true));
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    break;
                case JSON_ERROR_DEPTH:
                    $aReturn['error_JSON_encode'] = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $aReturn['error_JSON_encode'] = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $aReturn['error_JSON_encode'] = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $aReturn['error_JSON_encode'] = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $aReturn['error_JSON_encode'] = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $aReturn['error_JSON_encode'] = 'Unknown error';
                    break;
            }
            if (is_array($aReturn['response'])) {
                ksort($aReturn['response']);
            }
            $aReturn['info'] = curl_getinfo($ch);
            ksort($aReturn['info']);
        }
        curl_close($ch);
        return $aReturn;
    }
}
