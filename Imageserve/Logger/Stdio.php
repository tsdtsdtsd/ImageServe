<?php

namespace tsdtsdtsd\ImageServe;

require_once LIB_PATH . '/Logger/Interface.php';

/**
 * STDIO logger
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright Orkan Alat <orkan@about-orkan.de>
 * @category Imageserve
 * @package Logger
 * @subpackage Stdio
 */
class Imageserve_Logger_Stdio implements ImageServe_Logger_Interface
{

    /**
     * Default options
     * 
     * @var array
     */
    private $_options = array(
        'logLevel' => ImageServe_Logger::LEVEL_ERROR
    );

    /**
     * Constructor
     * 
     * @param array $options
     * @throws Exception 
     */
    public function __construct($options = array())
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Logs a message
     * 
     * @param int $logLevel
     * @param string $message
     */
    public function log($logLevel, $message)
    {
        if($logLevel > $this->_options['logLevel']) {
            return;
        }

        $date = !empty($message) ? '[' . date('Y-m-d H:i:s') . '] ' : '';
        echo $date . $message . PHP_EOL;
    }

}