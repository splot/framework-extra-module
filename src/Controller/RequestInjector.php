<?php
/**
 * Allows for "magic" injecting of the request object to controller arguments.
 * 
 * @package SplotFrameworkExtraModule
 * @subpackage Controller
 * @author Michał Pałys-Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2015, Michał Pałys-Dudek
 * @license MIT
 */
namespace Splot\FrameworkExtraModule\Controller;

use MD\Foundation\Debug\Debugger;
use MD\Foundation\Utils\ArrayUtils;

use Splot\Framework\Events\ControllerWillRespond;
use Splot\Framework\HTTP\Request;
use Splot\Framework\Routes\Router;

class RequestInjector
{

    /**
     * Router.
     * 
     * @var Router
     */
    protected $router;

    /**
     * Request.
     * 
     * @var Request
     */
    protected $request;

    /**
     * Constructor.
     * 
     * @param Router $router Router.
     * @param Request $request Request.
     */
    public function __construct(Router $router, Request $request) {
        $this->router = $router;
        $this->request = $request;
    }

    /**
     * When the found controller method wants to have the request injected,
     * this method will do it.
     * 
     * @param  ControllerWillRespond $event Event triggered before execution of controller.
     */
    public function injectRequest(ControllerWillRespond $event) {
        $route = $this->router->getRoute($event->getControllerName());

        // find the method's meta data
        $methods = $route->getMethods();
        $i = ArrayUtils::search($methods, 'method', $event->getMethod());
        if ($i === false) {
            return;
        }
        $method = $methods[$i];

        $arguments = $event->getArguments();

        foreach($method['params'] as $i => $param) {
            if ($param['class'] && Debugger::isExtending($param['class'], Request::__class(), true) && !($arguments[$i] instanceof Request)) {
                $arguments[$i] = $this->request;
            }
        }

        $event->setArguments($arguments);
    }

}
