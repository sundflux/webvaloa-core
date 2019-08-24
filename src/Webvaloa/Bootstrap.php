<?php

/**
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.io>.
 *
 * Portions created by the Initial Developer are
 * Copyright (C) 2019 Tarmo Alexander Sundström <ta@sundstrom.io>
 *
 * All Rights Reserved.
 *
 * Contributor(s):
 * 2009, 2010 Joni Halme <jontsa@amigaone.cc>
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

/**
 * Class Bootstrap
 * @package Webvaloa
 */
class Bootstrap
{
    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        if (!defined('WEBVALOA_BASEDIR')) {
            die('WEBVALOA_BASEDIR must be set before initializing core.');
        }

        // Core paths
        if (!defined('LIBVALOA_INSTALLPATH')) {
            define('LIBVALOA_INSTALLPATH', WEBVALOA_BASEDIR.'/'.'vendor');
        }

        // Configurations
        if (!defined('WEBVALOA_CONFIGDIR')) {
            define('WEBVALOA_CONFIGDIR', WEBVALOA_BASEDIR.'/'.'config');
        }

        // Extensions
        if (!defined('LIBVALOA_EXTENSIONSPATH')) {
            define('LIBVALOA_EXTENSIONSPATH', WEBVALOA_BASEDIR.'/'.'vendor');
        }

        // Public media
        if (!defined('LIBVALOA_PUBLICPATH')) {
            define('LIBVALOA_PUBLICPATH', WEBVALOA_BASEDIR.'/'.'public');
        }

        // Include paths
        set_include_path(LIBVALOA_EXTENSIONSPATH.'/'.PATH_SEPARATOR.get_include_path());
        set_include_path(LIBVALOA_INSTALLPATH.'/'.PATH_SEPARATOR.get_include_path());

        // Composer autoloader
        if (!file_exists(LIBVALOA_INSTALLPATH.'/autoload.php')) {
            die('Please install dependencies first, run: composer install');
        }

        include_once LIBVALOA_INSTALLPATH.'/autoload.php';
    }

    /**
     *
     */
    public function loadRuntimeConfiguration()
    {
        // Include separate config-file
        if (is_readable(WEBVALOA_BASEDIR.'/config/config.php')) {
            // Configuration found

            include_once WEBVALOA_BASEDIR.'/config/config.php';
        } elseif (file_exists(WEBVALOA_BASEDIR.'/config/config.php') && !is_readable(WEBVALOA_BASEDIR.'/config/config.php')) {
            // Configuration exists, but is not readable, so don't proceed

            die("Configuration exists, but configuration is not readable.");
        } elseif (!file_exists(WEBVALOA_BASEDIR.'/config/config.php') && file_exists(WEBVALOA_BASEDIR.'/config/config.php-stub')) {
            // Configuration doesn't exist, but -stub does, so we can
            // assume clean install - copy stub file as temporary configuration

            if (is_readable(WEBVALOA_BASEDIR.'/config/config.php-stub')) {
                copy(WEBVALOA_BASEDIR.'/config/config.php-stub', WEBVALOA_BASEDIR.'/config/config.php');

                header('location: '.$_SERVER['REQUEST_URI']);
                exit;
            } else {
                die("Could not copy configuration.");
            }
        } else {
            die('Could not load runtime configuration.');
        }
    }
}
