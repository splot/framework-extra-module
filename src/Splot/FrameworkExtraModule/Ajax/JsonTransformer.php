<?php
namespace Splot\FrameworkExtraModule\Ajax;

use MD\Foundation\Utils\ArrayUtils;

use Splot\Framework\Events\ControllerDidRespond;
use Splot\Framework\HTTP\JsonResponse;
use Splot\Framework\HTTP\Request;

class JsonTransformer
{

    protected $httpCodeKey = '__code';

    public function __construct($httpCodeKey = '__code') {
        $this->httpCodeKey = $httpCodeKey;
    }

    public function transform(array $data) {
        $code = isset($data[$this->httpCodeKey]) ? $data[$this->httpCodeKey] : 200;
        $arrayResponse = ArrayUtils::fromObject($data);

        $response = new JsonResponse($arrayResponse, $code);
        return $response;
    }

    public function onControllerDidRespond(ControllerDidRespond $event) {
        $request = $event->getRequest();

        if (!is_object($request) || !$request instanceof Request) {
            return;
        }

        $controllerResponse = $event->getControllerResponse();
        $response = $controllerResponse->getResponse();

        if (!is_array($response) || !$request->isXmlHttpRequest()) {
            return;
        }

        $ajaxResponse = $this->transform($response);
        $controllerResponse->setResponse($ajaxResponse);
    }

}