<?php

abstract class ImageServe_Plugin {

    protected $_imageserve = null;

    public function __construct(ImageServe $imageserve)
    {
        $this->_imageserve = $imageserve;
    }

    protected function _addHook($hookName, $callback) 
    {
        $className = get_class($this);
        $pluginName = str_replace('ImageServe_Plugin_', '', $className);

        if(!empty($pluginName)) {
            $this->_imageserve->addHook($hookName, $pluginName, $callback);
        }
    }

    abstract public function init();
}