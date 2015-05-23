<?php
namespace Splot\FrameworkExtraModule\Ajax;

use MD\Foundation\Exceptions\InvalidArgumentException;

use Splot\Framework\Events\DidFindRouteForRequest;
use Splot\Framework\HTTP\Request;

class ControllerAccess
{

    const AJAX_ONLY = 0;
    const NORMAL_ONLY = 1;
    const BOTH = 2;

    public function onDidFindRouteForRequest(DidFindRouteForRequest $event) {
        $route = $event->getRoute();
        $controllerClass = $route->getControllerClass();
        $request = $event->getRequest();

        return $this->willRespondToRequest($controllerClass, $request);
    }

    public function willRespondToRequest($controllerClass, Request $request) {
        if (!class_exists($controllerClass)) {
            throw new InvalidArgumentException('existing class name', $controllerClass);
        }

        if (!isset($controllerClass::$_ajaxAccess) || !is_array($controllerClass::$_ajaxAccess)) {
            return true;
        }

        $method = strtolower($request->getMethod());

        $accessControl = array_merge(array(
            'get' => self::BOTH,
            'post' => self::BOTH,
            'put' => self::BOTH,
            'delete' => self::BOTH
        ), $controllerClass::$_ajaxAccess);

        if (
            ($accessControl[$method] === self::NORMAL_ONLY && $request->isXmlHttpRequest())
            || ($accessControl[$method] === self::AJAX_ONLY && !$request->isXmlHttpRequest())
        ) {
            return false;
        }

        return true;
    }

}