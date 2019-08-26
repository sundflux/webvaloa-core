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

use Webvaloa\Helpers\Path;
use Webvaloa\Controller\Request;

/**
 * Set up the user interface.
 *
 * Loads the UI driver, sets up paths for the given UI driver, sets up properties,
 * and returns instace of the UI.
 *
 * @uses \Webvaloa\Controller\Request
 * @uses \Libvaloa\Ui
 */
class ApplicationUI
{
    /**
     * @var bool
     */
    private static $instance = false;

    /**
     * @return bool
     */
    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }

        $request = Request::getInstance();
        $config = new Configuration();

        // Force protocol
        if ($config->force_protocol) {
            $request->setProtocol($config->force_protocol);
        }

        // UI
        $uiInterface = Webvaloa::$properties['ui'];
        $ui = new $uiInterface();

        // File paths for the UI
        $pathHelper = new Path();
        $systemPaths = $pathHelper->getSystemPaths();

        $uiPaths = [
            'Layout',
            'Layout'.'/'.Webvaloa::$properties['layout'],
            'Layout'.'/'.Webvaloa::$properties['layout'].'/'.'Views',
            'Layout'.'/'.Webvaloa::$properties['layout'].'/'.$request->getMainController().'/'.'Views',
            'Controllers'.'/'.$request->getMainController().'/'.'Views',
            'Plugins',
        ];

        foreach ($systemPaths as $path) {
            foreach ($uiPaths as $uiPath) {
                $ui->addIncludePath($path.'/'.$uiPath);
            }
        }

        // Public media paths
        $ui->addIncludePath($pathHelper->getPublicPath().'/'.'Layout'.'/'.Webvaloa::$properties['layout']);
        $ui->addIncludePath($pathHelper->getPublicPath().'/'.'Layout');

        // Empty template for ajax requests
        if ($request->isAjax()) {
            $ui->setMainTemplate('empty');
        }

        // UI properties
        $ui->properties['route'] = $request->getCurrentRoute();
        if (isset($_SESSION['locale'])) {
            $ui->properties['locale'] = $_SESSION['locale'];
        }

        // Base paths
        $ui->properties['basehref'] = $request->getBaseUri();
        $ui->properties['basepath'] = $request->getPath();
        $ui->properties['layout'] = Webvaloa::$properties['layout'];

        // User info
        if (isset($_SESSION['UserID'])) {
            $ui->properties['userid'] = $_SESSION['UserID'];
        }
        if (isset($_SESSION['User'])) {
            $ui->properties['user'] = $_SESSION['User'];
        }

        // Headers
        $ui->addHeader('Content-type: '.$ui->properties['contenttype'].'; charset=utf-8');
        $ui->addHeader('Vary: Accept');

        return self::$instance = $ui;
    }
}
