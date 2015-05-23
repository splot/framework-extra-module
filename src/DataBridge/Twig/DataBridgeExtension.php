<?php
namespace Splot\FrameworkExtraModule\DataBridge\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

use Splot\FrameworkExtraModule\DataBridge\DataBridge;

class DataBridgeExtension extends Twig_Extension
{

    protected $databridge;

    public function __construct(DataBridge $databridge) {
        $this->databridge = $databridge;
    }

    /**
     * Returns Twig functions registered by this extension.
     * 
     * @return array
     */
    public function getFunctions() {
        return array(
            new Twig_SimpleFunction('databridge', array($this, 'printDataBridge'), array('is_safe' => array('html')))
        );
    }

    /**
     * Returns the name of this extension.
     * 
     * @return string
     */
    public function getName() {
        return 'databridge';
    }

    /*
     * EXTENSION FUNCTIONS
     */
    public function printDataBridge() {
        $html = '<script type="text/javascript">';
        $html .= '(function(w,u){';
        $html .= 'if(w.Splot===u||w.Splot.DataBridge===u)return;';
        $html .= 'w.Splot.DataBridge.setData('. $this->databridge->toJson() .');';
        $html .= '})(window);';
        $html .= '</script>';
        return $html;
    }

}