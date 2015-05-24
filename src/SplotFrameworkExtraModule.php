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

    public function loadModules() {
        return array(
            new SplotTwigModule()
        );
    }

    public function configure() {
        parent::configure();
        
        $config = $this->getConfig();
        $container = $this->getContainer();

        if ($config->get('router.use_request')) {
            $this->configureRouterRequestConfigurator();
        }

        // go through all plugins enable settings and run initialization methods for active ones
        foreach(array(
            'ajax.enable' => 'configureAjax',
            'databridge.enable' => 'configureDataBridge',
            'form.simple_handler.enable' => 'configureFormSimpleHandler',
            'imaging.enable' => 'configureImaging',
            'mailer.enable' => 'configureMailer',
            'filestorage.simple.enable' => 'configureSimpleFileStorage'
        ) as $option => $configureMethod) {
            if ($config->get($option)) {
                call_user_func_array(array($this, $configureMethod), array());
            }
        }
    }

    public function run() {
        if ($this->getConfig()->get('databridge.enable')) {
            if ($this->container->has('twig') && $this->container->has('databridge')) {
                $this->container->get('twig')->addExtension(new DataBridgeExtension($this->container->get('databridge')));
            }
            if ($this->container->has('javascripts')) {
                $this->container->get('javascripts')->addAsset('SplotFrameworkExtraModule::databridge.js', 'lib');
            }
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
        $this->container->set('databridge', function($c) {
            return new DataBridge();
        });
    }

    protected function configureImaging() {
        $this->container->set('imaging', function($c) {
            return new Imaging();
        });
    }

    protected function configureFormSimpleHandler() {
        $config = $this->getConfig();

        $this->container->set('form.simple_handler', function($c) use ($config) {
            return new SimpleHandler($c->get('knit'), $config->get('ajax.json.http_code_key'));
        });
    }

    protected function configureMailer() {
        $config = $this->getConfig();

        $useBackgroundMailer = $config->get('mailer.use_worker');
        $mailer_id = $useBackgroundMailer ? 'mailer.foreground' : 'mailer';

        $this->container->set($mailer_id, function($c) use ($config) {
            return new Mailer(
                $c->get('resource_finder'),
                $c->get('twig'),
                $c->get('logger_provider')->provide('Mailer'),
                $config->get('mailer')
            );
        });

        if ($useBackgroundMailer) {
            $this->container->set('mailer', function($c) use ($config) {
                return new BackgroundMailer(
                    $c->get('resource_finder'),
                    $c->get('twig'),
                    $c->get('logger_provider')->provide('Mailer'),
                    $config->get('mailer'),
                    $c->get('work_queue')
                );
            });
        }
    }

    protected function configureSimpleFileStorage() {
        $config = $this->getConfig();

        $this->container->set('filestorage.simple', function($c) use ($config) {
            return new SimpleStorage(
                $c->get('filesystem'),
                $config->get('filestorage.simple.web') ? $c->getParameter('web_dir') : $c->getParameter('root_dir'),
                $config->get('filestorage.simple.dir')
            );
        });
    }

}