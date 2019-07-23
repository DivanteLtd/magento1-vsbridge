<?php

class Divante_VueStorefrontBridge_Controller_Router extends Mage_Core_Controller_Varien_Router_Standard
{
    public function match(Zend_Controller_Request_Http $request)
    {
        //checking before even try to find out that current module
        //should use this router
        if (!$this->_beforeModuleMatch()) {
            return false;
        }

        $this->fetchDefault();

        $front = $this->getFront();
        $path = trim($request->getPathInfo(), '/');

        if ($path) {
            $p = explode('/', $path);
        } else {
            $p = explode('/', $this->_getDefaultPath());
        }

        // get module name
        if ($request->getModuleName()) {
            $module = $request->getModuleName();
        } else {
            if (!empty($p[0])) {
                $module = $p[0];
            } else {
                $module = $this->getFront()->getDefault('module');
                $request->setAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS, '');
            }
        }
        if (!$module) {
            if (Mage::app()->getStore()->isAdmin()) {
                $module = 'admin';
            } else {
                return false;
            }
        }

        $store = null;

        if (!empty($p[1])) {
            $store = Mage::getModel('core/store')->load($p[1]);
        }

        $index = 1;

        if ($store && $store->getId()) {
            Mage::app()->setCurrentStore($store->getCode());
            $index++;
        }

        /**
         * Searching router args by module name from route using it as key
         */
        $modules = $this->getModuleByFrontName($module);

        if ($modules === false) {
            return false;
        }

        // checks after we found out that this router should be used for current module
        if (!$this->_afterModuleMatch()) {
            return false;
        }

        /**
         * Going through modules to find appropriate controller
         */
        $found = false;
        foreach ($modules as $realModule) {
            $request->setRouteName($this->getRouteByFrontName($module));

            // get controller name
            if ($request->getControllerName()) {
                $controller = $request->getControllerName();
            } else {
                if (!empty($p[$index])) {
                    $controller = $p[$index];
                } else {
                    $controller = $front->getDefault('controller');
                    $request->setAlias(
                        Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                        ltrim($request->getOriginalPathInfo(), '/')
                    );
                }
            }
            // get action name
            if (empty($action)) {
                if ($request->getActionName()) {
                    $action = $request->getActionName();
                } else {
                    $action = !empty($p[$index + 1]) ? $p[$index + 1] : $front->getDefault('action');
                }
            }

            //checking if this place should be secure
            $this->_checkShouldBeSecure($request, '/' . $module . '/' . $controller . '/' . $action);

            $controllerClassName = $this->_validateControllerClassName($realModule, $controller);
            if (!$controllerClassName) {
                continue;
            }

            // instantiate controller class
            $controllerInstance = Mage::getControllerInstance($controllerClassName, $request, $front->getResponse());

            if (!$this->_validateControllerInstance($controllerInstance)) {
                continue;
            }

            if (!$controllerInstance->hasAction($action)) {
                continue;
            }

            $found = true;
            break;
        }

        /**
         * if we did not found any suitable
         */
        if (!$found) {
            if ($this->_noRouteShouldBeApplied()) {
                $controller = 'index';
                $action = 'noroute';

                $controllerClassName = $this->_validateControllerClassName($realModule, $controller);
                if (!$controllerClassName) {
                    return false;
                }

                // instantiate controller class
                $controllerInstance = Mage::getControllerInstance(
                    $controllerClassName,
                    $request,
                    $front->getResponse()
                );

                if (!$controllerInstance->hasAction($action)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // set values only after all the checks are done
        $request->setModuleName($module);
        $request->setControllerName($controller);
        $request->setActionName($action);
        $request->setControllerModule($realModule);

        // set parameters from pathinfo
        for ($i = 4, $l = sizeof($p); $i < $l; $i += 2) {
            $request->setParam($p[$i], isset($p[$i + 1]) ? urldecode($p[$i + 1]) : '');
        }

        // dispatch action
        $request->setDispatched(true);
        $controllerInstance->dispatch($action);

        return true;
    }
}