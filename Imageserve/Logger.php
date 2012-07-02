<?php

namespace tsdtsdtsd\ImageServe;

require_once LIB_PATH . '/Logger/Interface.php';

/**
 * Logger composite class
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright Orkan Alat <orkan@about-orkan.de>
 * @category ImageServe
 * @package Logger
 * @subpackage Composite
 */
class ImageServe_Logger implements ImageServe_Logger_Interface
{
    /**
     * Logger levels
     */
    
    const LEVEL_SYSTEM = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_INFO = 4;
    const LEVEL_DEBUG = 5;
    const LEVEL_DEVELOPMENT = 100;
    
    /**
     * Stores the loggers
     * @var array
     */
    protected $_loggers = array();
    
    /**
     * Adds a logger to the stack
     * 
     * @param Imageserve_Logger_Interface $logger 
     */
    public function addLogger(ImageServe_Logger_Interface $logger)
    {
        $this->_loggers[] = $logger;
    }
    
    /**
     * Removes a logger from the stack
     * 
     * @param Imageserve_Logger_Interface $logger
     * @return boolean 
     */
    public function removeLogger(ImageServe_Logger_Interface $logger)
    {
        $key = array_search($logger, $this->_loggers);
        
        if($key === false)
        {
            return false;
        }
        
        unset($this->_loggers[$key]);
        
        return true;
    }
    
    /**
     * Triggers log method of all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function log($logLevel, $message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log($logLevel, $message);
        }
    }
    
    /**
     * Triggers logging of a system message for all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function logSystem($message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log(self::LEVEL_SYSTEM, $message);
        }
    }
    
    /**
     * Triggers logging of an error for all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function logError($message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log(self::LEVEL_ERROR, $message);
        }
    }
    
    /**
     * Triggers logging of a warning for all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function logWarning($message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log(self::LEVEL_WARNING, $message);
        }
    }
    
    /**
     * Triggers logging of a info message for all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function logInfo($message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log(self::LEVEL_INFO, $message);
        }
    }
    
    /**
     * Triggers logging of a debug message for all loggers in the stack
     * 
     * @param int $logLevel
     * @param string $message 
     */
    public function logDebug($message)
    {
        foreach($this->_loggers as $logger)
        {
            $logger->log(self::LEVEL_DEBUG, $message);
        }
    }
}
