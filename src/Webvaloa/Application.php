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

use Libvaloa\Debug\Debug;
use Webvaloa\Controller\Request;
use stdClass;
use Exception;

/**
 * Class Application.
 */
class Application
{
    /**
     * @var bool
     */
    protected $params = false;

    /**
     * @param $k
     *
     * @return stdClass|string|void|Request|DB|Plugin
     */
    public function __get($k)
    {
        // Core classes available for controllers/applications

        if ($k === 'request') {
            $this->request = Request::getInstance();
            $config = new Configuration();

            // Force protocol
            if (!empty($config->force_protocol)) {
                $this->request->setProtocol($config->force_protocol);
            }

            return $this->request;
        } elseif ($k === 'ui') {
            $this->ui = ApplicationUI::getInstance();

            return $this->ui;
        } elseif ($k === 'view') {
            $this->view = new stdClass();

            return $this->view;
        } elseif ($k === 'db') {
            return \Webvaloa\Webvaloa::DBConnection();
        } elseif ($k === 'locale') {
            return \Webvaloa\Webvaloa::getLocale();
        } elseif ($k === 'plugin') {
            $this->plugin = new Plugin();

            return $this->plugin;
        } elseif (!empty($this->params)) {
            $this->parseParameters();
        }

        if (isset($this->{$k})) {
            return $this->{$k};
        }

        trigger_error('Call to an undefined property '.get_class($this)."::\${$k}", E_USER_WARNING);

        return;
    }

    /**
     * @param $k
     *
     * @return bool
     */
    public function __isset($k)
    {
        if (!empty($this->params)) {
            $this->parseParameters();
        }

        return isset($this->{$k});
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Set page root (template name)
        if (!$this->ui->getPageRoot() && $this->request->getMethod()) {
            $this->ui->setPageRoot($this->request->getMethod());
        }

        try {
            // Plugin event: onAfterController
            $this->plugin->request = &$this->request;

            if ($this->plugin->hasRunnablePlugins()) {
                $this->plugin->setEvent('onAfterController');

                // Give stuff for plugins to modify
                $this->plugin->ui = &$this->ui;
                $this->plugin->view = &$this->view;
                $this->plugin->controller = false; // Controller cannot be modified at this point
                $this->plugin->xhtml = false; // Xhtml output is not available at this point
                $this->plugin->_properties = false;

                // Run plugins
                $this->plugin->runPlugins();
            }

            // Set view data from the controller after plugins are adone
            $this->ui->addObject($this->view);

            if ($this->request->getChildController()) {
                // Load resources for child controller, /application_subapplication
                $this->ui->addTemplate($this->request->getChildController());
            } else {
                // Load resources for main application, /application
                $this->ui->addTemplate($this->request->getMainController());
            }

            // Preprocess the XSL template
            $this->ui->preProcessTemplate();

            // Plugin event: onBeforeRender
            if ($this->plugin->hasRunnablePlugins()) {
                $this->plugin->setEvent('onBeforeRender');

                // Run plugins
                $this->plugin->runPlugins();
            }

            // Page complete, send headers and output:

            // Headers
            if ($headers = $this->ui->getHeaders()) {
                foreach ($headers as $header) {
                    header($header);
                }
            }

            // Rendered XHTML
            $xhtml = (string) $this->ui;

            // Plugin event: onAfterRender
            if ($this->plugin->hasRunnablePlugins()) {
                $this->plugin->setEvent('onAfterRender');

                // Give stuff for plugins to modify
                $this->plugin->ui = false; // UI cannot be modified at this point
                $this->plugin->view = $this->view; // View is available after render for reading, but not modifiable at this point
                $this->plugin->controller = false; // Controller cannot be modified at this point
                $this->plugin->xhtml = &$xhtml;
                $this->plugin->_properties = false;

                // Run plugins
                $this->plugin->runPlugins();
            }

            Debug::__print('Executed '.\Libvaloa\Db\Db::$queryCount.' sql queries.');
            Debug::__print('Webvaloa finished with peak memory usage: '.round(memory_get_peak_usage(false) / 1024 / 1024, 2).' MB');

            return $xhtml;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     *
     */
    private function parseParameters()
    {
        if (is_array($this->params)) {
            foreach ($this->params as $k => $v) {
                if ($v) {
                    $this->{$v} = $this->request->getParam($k);
                }
            }
        }

        $this->params = false;
    }
}
