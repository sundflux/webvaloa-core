<?php

/**
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>.
 *
 * Portions created by the Initial Developer are
 * Copyright (C) 2019 Tarmo Alexander Sundström <ta@sundstrom.im>
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

use Libvaloa\Debug\Debug;
use Webvaloa\I18n\Translate\Translate;
use Webvaloa\Locale\Locales;
use Webvaloa\Controller\Request;
use PDOException;

/**
 * Webvaloa kernel class.
 *
 * Handles session starting, setting excpetion handlers, locales, database connection.
 *
 * @uses \Webvaloa\config
 * @uses \Webvaloa\Cache
 * @uses \Libvaloa\Db\Db
 * @uses \Webvaloa\Controller\Request
 */
class Webvaloa
{
    /**
     * Database connection.
     */
    public static $db = false;

    /**
     * Static var to track if Webvaloa kernel has been loaded.
     */
    public static $loaded = false;

    /**
     * Current locale.
     */
    public static $locale = false;

    /**
     * Session.
     */
    public static $session = false;

    /**
     * Whoops.
     */
    public $whoops;

    /**
     * Cli.
     */
    public static $cli = false;

    /**
     * Properties array.
     *
     * startSession         - defines if the kernel should start session.
     * sessionMaxlifetime   - sets the session length with ini_set. Defaults to 1 hour.
     * ui                   - defines the user interface driver. By default webvaloa uses XSL ui driver.
     * layout               - template name for ui
     */
    public static $properties = array(
        'startSession' => 1,
        'sessionMaxlifetime' => 3600,
        'ui' => 'Libvaloa\Ui\Xml',
        'vendor' => 'ValoaApplication',
        'layout' => 'default',
    );

    /**
     * Sets up libvaloa environment and registers base classes/functions.
     */
    public function __construct()
    {
        // Register class autoloader.
        spl_autoload_register(array('Webvaloa\Webvaloa', 'autoload'));

        // Uncaught exception handler.
        if (error_reporting() !== E_ALL) {
            // Default, safer handler for production mode
            set_exception_handler(array('Webvaloa\Webvaloa', 'exceptionHandler'));
        } else {
            // Register whoops for developer mode
            $this->whoops = new \Whoops\Run();
            $this->whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $this->whoops->register();
        }

        self::$cli = self::isCommandLine();
        self::$loaded = true;
    }

    /**
     * @return bool
     */
    public static function isCommandLine()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        return false;
    }

    /**
     *
     */
    public static function initializeSession()
    {
        // Start the session

        if (self::$properties['startSession'] > 0 && !self::$session) {
            // Set session lifetime from config, if available
            $config = new Configuration();

            if ($config->sessionmaxfiletime) {
                $sessionMaxlifetime = $config->sessionmaxfiletime;
            } else {
                $sessionMaxlifetime = (string) self::$properties['sessionMaxlifetime'];
            }

            if (function_exists('ini_set')) {
                ini_set('session.gc_maxlifetime', $sessionMaxlifetime);
            }

            session_set_cookie_params($sessionMaxlifetime);
            session_start();

            Debug::__print('Using '.ini_get('session.save_handler').' session handler');

            self::$session = true;
        }
    }

    /**
     * @return array
     */
    public static function getSystemPaths()
    {
        $paths[] = LIBVALOA_INSTALLPATH;
        $paths[] = LIBVALOA_EXTENSIONSPATH;
        $paths = array_merge($paths, explode(':', get_include_path()));

        foreach ($paths as $path) {
            $path = rtrim($path);
            $path = rtrim($path, '/');

            if (file_exists($path.'/'.self::$properties['vendor'])) {
                $systemPaths[] = realpath($path);
                $systemPaths[] = realpath($path.'/'.self::$properties['vendor']);
            }

            // Treat any path with FrontController as system path.
            if (file_exists($path.'/Webvaloa/FrontController.php')) {
                $systemPaths[] = $path;
            }
        }

        if (is_array($systemPaths)) {
            $systemPaths = array_reverse(array_unique($systemPaths));

            return $systemPaths;
        }

        throw new \RuntimeException('Could not find any system paths');
    }

    /**
     * Class autoloader.
     *
     *
     * @param string $name Class name
     */
    public static function autoload($name)
    {
        // Autoloading standard:
        // http://www.php-fig.org/psr/psr-0/
        $className = ltrim($name, '\\');
        $fileName = '';
        $namespace = '';

        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', '/', $namespace).'/';
        }

        $fileName .= str_replace('_', '/', $className).'.php';

        // Include classes if found
        foreach (self::getSystemPaths() as $v) {
            if (!is_readable($v.'/'.$fileName)) {
                continue;
            }

            include_once $v.'/'.$fileName;

            return;
        }
    }

    /**
     * Opens database connection.
     *
     *
     * @return DB database connection
     *
     * @uses DB
     */
    public static function DBConnection()
    {
        if (!self::$db instanceof \Libvaloa\Db\Db) {
            try {
                $config = new Configuration();

                // Make sure we use UTF-8
                if ($config->db_server != 'sqlite') {
                    $initquery = 'SET NAMES utf8mb4';
                    if ($config->time_zone) {
                        date_default_timezone_set($config->time_zone);
                        $date = new \DateTime();
                        $hours = $date->getOffset() / 3600;
                        $seconds = 60 * ($hours - floor($hours));
                        $offset = sprintf('%+d:%02d', $hours, $seconds);
                        $initquery .= ", time_zone = '{$offset}'";
                    }
                } else {
                    $initquery = '';
                }

                if ($config->db == '') {
                    return self::$db = false;
                }

                // Initialize the db connection
                self::$db = new \Libvaloa\Db\Db(
                    $config->db_host,
                    $config->db_user,
                    $config->db_pass,
                    $config->db,
                    $config->db_server,
                    false,
                    $initquery
                );
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage());
            }
        }

        return self::$db;
    }

    /**
     * Catches uncaught exceptions and displays error message.
     */
    public static function exceptionHandler($e)
    {
        echo '<h3>An error occured which could not be fixed.</h3>';
        printf('<p>%s</p>', $e->getMessage());
        if ($e->getCode()) {
            echo ' ('.$e->getCode().')';
        }
        if (error_reporting() == E_ALL) {
            printf('<p><b>Location:</b> %s line %s.</p>', $e->getFile(), $e->getLine());
            echo '<h4>Exception backtrace:</h4>';
            echo '<pre>';
            print_r($e->getTrace());
            echo '</pre>';
        }
    }

    /**
     * Returns current locale.
     *
     * @return string self::$locale
     */
    public static function getLocale()
    {
        if (!self::$locale) {
            // Get current system locale
            $systemLocale = getenv('LANG');

            // Get available locales
            $locales = new Locales();
            $available = $locales->locales();

            // Set the locale
            if (isset($_SESSION['locale'])) {
                // Set locale from session
                self::$locale = $_SESSION['locale'];
            } elseif ((!isset($_SESSION['locale']) || empty($_SESSION['locale'])) && in_array($systemLocale, $available)) {
                // Set locale from system
                // Default locale
                self::$locale = $_SESSION['locale'] = $systemLocale;
            } else {
                self::$locale = 'en_US';
            }

            // Set the locale to envvars
            putenv('LANG='.self::$locale);
            setlocale(LC_MESSAGES, self::$locale);
        }

        return self::$locale;
    }

    /**
     * Translate a string.
     */
    public static function translate()
    {
        $args = func_get_args();

        if (isset($args[1])) {
            $domain = $args[1];
        } else {
            // Controller translations
            $request = Request::getInstance();
            $domain = $request->getMainController();
        }

        // Select translator backend
        $configuration = new \Webvaloa\Configuration();
        $translatorBackend = $configuration->default_translator_backend;

        if (!empty($translatorBackend) && $translatorBackend !== false) {
            $params = array_merge($args, array('backend' => $translatorBackend));
        } else {
            $params = $args;
        }

        $translate = new Translate($params);

        // Default to installpath
        if (file_exists(LIBVALOA_INSTALLPATH.'/'.self::$properties['vendor'].'/'.'Locale'.'/'.self::getLocale().'/'.'LC_MESSAGES'.'/'.$domain.'.ini')) {
            $path = LIBVALOA_INSTALLPATH;
        }

        // Override from extensionspath if found
        if (file_exists(LIBVALOA_EXTENSIONSPATH.'/'.self::$properties['vendor'].'/'.'Locale'.'/'.self::getLocale().'/'.'LC_MESSAGES'.'/'.$domain.'.ini')) {
            $path = LIBVALOA_EXTENSIONSPATH;
        }

        // No translation found
        if (!isset($path)) {
            return $args[0];
        }

        $translate->bindTextDomain($domain, $path.'/'.self::$properties['vendor'].'/'.'Locale');
        $t = (string) $translate;

        return $t;
    }
}
