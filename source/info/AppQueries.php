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
 * Queries used to handle SQL data
 *
 * @author Popiniuc Daniel-Gheorghe
 */
class AppQueries
{

    protected function sActiveDatabases()
    {
        return 'SELECT `SCHEMA_NAME` As `Db` '
            . ', `DEFAULT_CHARACTER_SET_NAME` AS `DbCharset` '
            . ', `DEFAULT_COLLATION_NAME` AS `DbCollation` '
            . 'FROM `information_schema`.`SCHEMATA` '
            . 'WHERE `SCHEMA_NAME` NOT IN ("information_schema", "mysql", "performance_schema", "sys") '
            . 'GROUP BY `SCHEMA_NAME`;';
    }

    protected function sActiveEngines()
    {
        return 'SELECT `ENGINE` AS `Engine`'
            . ', `SUPPORT` AS `Support`'
            . ', `COMMENT` AS `Comment` '
            . 'FROM `information_schema`.`ENGINES` '
            . 'WHERE (`SUPPORT` IN ("DEFAULT", "YES")) '
            . 'AND (`ENGINE` != "PERFORMANCE_SCHEMA") '
            . 'GROUP BY `ENGINE`;';
    }
}
