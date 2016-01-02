<?php

/*
 * The MIT License
 *
 * Copyright 2016 Transformer-.
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

trait InformatorDynamicFunctions
{

    protected function callDynamicFunctionToGetResults(array $inLabelsArray)
    {
        switch (count($inLabelsArray)) {
            case 1:
                return call_user_func([$this, $inLabelsArray[0]]);
            // intentionlly left open since prior line is a return
            case 2:
                if (is_array($inLabelsArray[1])) {
                    return call_user_func_array([$this, $inLabelsArray[0]], [$inLabelsArray[1]]);
                }
                $dynFnPrmtr = $this->evaluateParameterAgainstKnownFunctions($inLabelsArray[1]);
                return call_user_func([$this, $inLabelsArray[0]], $dynFnPrmtr);
        }
    }

    private function evaluateParameterAgainstKnownFunctions($inParameter)
    {
        $outParameter         = $inParameter;
        $standardPhpFunctions = [
            'get_loaded_extensions',
            'stream_get_filters',
            'stream_get_transports',
            'stream_get_wrappers',
        ];
        if (in_array($inParameter, $standardPhpFunctions)) {
            $outParameter = call_user_func($inParameter);
        }
        return $outParameter;
    }
}
