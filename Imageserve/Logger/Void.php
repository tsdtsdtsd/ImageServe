<?php

namespace tsdtsdtsd\ImageServe;

require_once LIB_PATH . '/Logger/Interface.php';

/**
 * Void logger
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright Orkan Alat <orkan@about-orkan.de>
 * @category Imageserve
 * @package Logger
 * @subpackage Void
 */
class Imageserve_Logger_Void implements ImageServe_Logger_Interface
{

    /**
     * Constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        
    }

    /**
     * Logs a message
     * 
     * @param int $logLevel
     * @param string $message
     * @return bool
     */
    public function log($logLevel, $message)
    {
        return true;
    }

}