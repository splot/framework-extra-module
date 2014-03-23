<?php
namespace Splot\FrameworkExtraModule\Ajax;

use Splot\EventManager\EventManager;

use Splot\Framework\Events\ControllerDidRespond;
use Splot\Framework\Events\DidFindRouteForRequest;

use Splot\FrameworkExtraModule\Ajax\ControllerAccess;
use Splot\FrameworkExtraModule\Ajax\JsonTransformer;

class Ajax
{

    protected $eventManager;

    protected $jsonTransformer;

    protected $enableJsonTransformer = true;

    protected $controllerAccess;

    protected $enableControllerAccess = true;

    public function __construct(
        EventManager $eventManager,
        JsonTransformer $jsonTransformer,
        $enableJsonTransformer = true,
        ControllerAccess $controllerAccess,
        $enableControllerAccess = true
    ) {
        $this->eventManager = $eventManager;
        $this->jsonTransformer = $jsonTransformer;
        $this->enableJsonTransformer = $enableJsonTransformer;
        $this->controllerAccess = $controllerAccess;
        $this->enableControllerAccess = $enableControllerAccess;
    }

    public function init() {
        $self = $this;

        $this->eventManager->subscribe(ControllerDidRespond::getName(), function($event) use ($self) {
            return $self->enableJsonTransformer ? $self->jsonTransformer->onControllerDidRespond($event) : null;
        }, 1024);

        if ($this->enableControllerAccess) {
            $this->eventManager->subscribe(DidFindRouteForRequest::getName(), function($event) use ($self) {
                return $self->controllerAccess->onDidFindRouteForRequest($event);
            });
        }
    }

    public function setEnableJsonTransformer($enable = true) {
        $this->enableJsonTransformer = $enable;
    }

    public function getEnableJsonTransformer() {
        return $this->enableJsonTransformer;
    }

}