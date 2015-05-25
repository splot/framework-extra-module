<?php
/**
 * Module class for SplotFrameworkExtraModule.
 * 
 * @package SplotFrameworkExtraModule
 */
namespace Splot\FrameworkExtraModule;

use MD\Foundation\Debug\Debugger;
use MD\Foundation\Exceptions\NotFoundException;

use Splot\Framework\Modules\AbstractModule;
use Splot\Framework\Events\ControllerWillRespond;
use Splot\Framework\Events\DidReceiveRequest;
use Splot\Framework\HTTP\Request;

use Splot\TwigModule\SplotTwigModule;

use Splot\FrameworkExtraModule\Ajax\Ajax;
use Splot\FrameworkExtraModule\Ajax\ControllerAccess;
use Splot\FrameworkExtraModule\Ajax\JsonTransformer;
use Splot\FrameworkExtraModule\DataBridge\DataBridge;
use Splot\FrameworkExtraModule\DataBridge\Twig\DataBridgeExtension;
use Splot\FrameworkExtraModule\FileStorage\SimpleStorage;
use Splot\FrameworkExtraModule\Form\SimpleHandler;
use Splot\FrameworkExtraModule\Imaging\Imaging;
use Splot\FrameworkExtraModule\Mailer\Mailer;
use Splot\FrameworkExtraModule\Mailer\BackgroundMailer;

class SplotFrameworkExtraModule extends AbstractModule
{

    public function loadModules($env, $debug) {
        return array(
            new SplotTwigModule()
        );
    }

    public function configure() {
        parent::configure();
        
        $config = $this->getConfig();
        $container = $this->getContainer();

        // copy some params from config to container
        $this->container->setParameter('form.simple_handler.http_code_key', $config->get('ajax.json.http_code_key'));
        $this->container->setParameter('filestorage.simple.parent_dir', $config->get('filestorage.simple.web') ? '%web_dir%' : '%root_dir%');
        $this->container->setParameter('filestorage.simple.dir', $config->get('filestorage.simple.dir'));

        // conditional configurations

        if ($config->get('router.use_request')) {
            $this->configureRouterRequestConfigurator();
        }

        if ($config->get('ajax.enable')) {
            $this->configureAjax();
        }

        if ($config->get('databridge.enable')) {
            $this->configureDataBridge();
        }

        if ($config->get('mailer.enable')) {
            $this->configureMailer();
        }
    }

    /**************************************
     * CONFIGURATORS
     **************************************/

    protected function configureRouterRequestConfigurator() {
        $this->container->loadFromFile($this->getConfigDir() .'/services/router.request.yml');
    }

    protected function configureAjax() {
        $config = $this->getConfig();
        $this->container->loadFromFile($this->getConfigDir() .'/services/ajax.yml');
        $this->container->setParameter('ajax.json_transformer.enable', $config->get('ajax.json.enable'));
        $this->container->setParameter('ajax.json_transformer.http_code_key', $config->get('ajax.json.http_code_key'));
        $this->container->setParameter('ajax.controller_access.enable', $config->get('ajax.controller_access.enable'));
    }

    protected function configureDataBridge() {
        $this->container->loadFromFile($this->getConfigDir() .'/services/databridge.yml');
    }

    protected function configureMailer() {
        $config = $this->getConfig();
        $this->container->loadFromFile($this->getConfigDir() .'/services/mailer.yml');
        $this->container->setParameter('mailer.config', $config->get('mailer'));
        
        // and register @mailer as an alias to proper mailer
        $this->container->register('mailer', array(
            'alias' => $config->get('mailer.use_worker') ? 'mailer.background' : 'mailer.foreground'
        ));
    }

}
