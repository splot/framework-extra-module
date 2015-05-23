<?php
/**
 * Configures the router (namely host, protocol and port) based on
 * request received.
 * 
 * @package SplotFrameworkExtraModule
 * @subpackage Router
 * @author Michał Pałys-Dudek <michal@michaldudek.pl>
 * 
 * @copyright Copyright (c) 2015, Michał Pałys-Dudek
 * @license MIT
 */
namespace Splot\FrameworkExtraModule\Router;

use Splot\Framework\Events\DidReceiveRequest;
use Splot\Framework\Routes\Router;

class RouterRequestConfigurator
{

    /**
     * Router.
     * 
     * @var Router
     */
    protected $router;

    /**
     * Constructor.
     * 
     * @param Router $router Router.
     */
    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * On request received event, configure the router.
     * 
     * @param  DidReceiveRequest $event Event triggered when a request was received.
     */
    public function onRequest(DidReceiveRequest $event) {
        $request = $event->getRequest();
        $protocol = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        if (!empty($protocol)) {
            $this->router->setProtocol($protocol);
        }

        if (!empty($host)) {
            $this->router->setHost($host);
        }

        if (!empty($port)) {
            $this->router->setPort($port);
        }
    }

}