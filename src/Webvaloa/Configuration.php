<?php

/**
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.io>.
 *
 * Portions created by the Initial Developer are
 * Copyright (C) 2014 Tarmo Alexander Sundström <ta@sundstrom.io>
 *
 * All Rights Reserved.
 *
 * Contributor(s):
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace Webvaloa;

use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration.
 */
class Configuration
{

    /**
     * Configuration constructor.
     */
    public function __construct()
    {
    }

    public function __set($k, $v)
    {
    }

    /**
     * Get a configuration variable.
     *
     * @param mixed $k
     *
     * @return mixed|\Webvaloa\config
     */
    public function __get($k)
    {
        // Env vars.
        $tmp = strtoupper($k);
        if (!empty($_ENV[$tmp])) {
            return $_ENV[$tmp];
        }

        // config.yaml.
        if (defined(WEBVALOA_CONFIGDIR) && file_exists(WEBVALOA_CONFIGDIR.'/config.yaml')) {
            $config = Yaml::parse(file_get_contents(WEBVALOA_CONFGIDIR.'/config.yaml'));

            if (isset($config[$k]) && !empty($config[$k])) {
                return $config[$k];
            }
        }

        return false;
    }
}
