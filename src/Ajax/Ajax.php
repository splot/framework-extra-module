<?php
namespace Splot\FrameworkExtraModule\Ajax;

use Splot\Framework\Events\ControllerDidRespond;
use Splot\Framework\Events\DidFindRouteForRequest;

use Splot\FrameworkExtraModule\Ajax\ControllerAccess;
use Splot\FrameworkExtraModule\Ajax\JsonTransformer;

class Ajax
{

    protected $jsonTransformer;

    protected $enableJsonTransformer = true;

    protected $controllerAccess;

    protected $enableControllerAccess = true;

    public function __construct(
        JsonTransformer $jsonTransformer,
        $enableJsonTransformer = true,
        ControllerAccess $controllerAccess,
        $enableControllerAccess = true
    ) {
        $this->jsonTransformer = $jsonTransformer;
        $this->enableJsonTransformer = $enableJsonTransformer;
        $this->controllerAccess = $controllerAccess;
        $this->enableControllerAccess = $enableControllerAccess;
    }

    public function responseToJson(ControllerDidRespond $event) {
        if ($this->enableJsonTransformer) {
            return $this->jsonTransformer->onControllerDidRespond($event);
        }
    }

    public function checkControllerAccess(DidFindRouteForRequest $event) {
        if ($this->enableControllerAccess) {
            return $this->controllerAccess->onDidFindRouteForRequest($event);
        }
    }

    public function setEnableJsonTransformer($enable = true) {
        $this->enableJsonTransformer = $enable;
    }

    public function getEnableJsonTransformer() {
        return $this->enableJsonTransformer;
    }

}