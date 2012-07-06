<?php

class ImageServe_Plugin_Test extends ImageServe_Plugin
{
    protected $_config = array(

        'pluginName' => 'Test'
    );

    public function init() {
        
        $this->_addHook('pre_getRequest', 'modifyRequestInput');
    }

    public function modifyRequestInput(&$params)
    {
        $params['pluginExecuted'] = true;
        $this->_imageserve->logger->logDebug('ImageServe_Plugin_Test::modifyRequestInput executed.');
    }
}