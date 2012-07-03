<?php

namespace tsdtsdtsd\ImageServe;

require_once LIB_PATH . '/Logger/Interface.php';

/**
 * File logger
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright Orkan Alat <orkan@about-orkan.de>
 * @category ImageServe
 * @package Logger
 * @subpackage File
 */
class ImageServe_Logger_File implements ImageServe_Logger_Interface
{
    // @todo log file rotation
    // @todo log file naming
    
    /**
     * Default options
     * 
     * @var array
     */
    private $_options = array(
        'logLevel' => ImageServe_Logger::LEVEL_ERROR
    );
    
    /**
     * Path to log files directory
     * 
     * @var string
     */
    private $_filePath = null;
    
    /**
     * Stores the file handler
     * 
     * @var stream
     */
    private $_fileHandler = null;

    /**
     * Constructor
     * 
     * @param array $options
     * @throws Exception 
     */
    public function __construct($options = array())
    {
        $this->_options = array_merge($this->_options, $options);

        if (empty($options['file']))
        {
            throw new \Exception('File logger needs a file.');
        }

        if (!file_exists($options['file']))
        {
            // @todo chmod/chown
            touch($options['file']);
        }

        if (!file_exists($options['file']))
        {
            throw new \Exception('Log file "' . $options['file'] . '" does not exist and can not be created.');
        }

        $this->_fileHandler = fopen($options['file'], 'a+');
    }

    /**
     * Destructor 
     */
    public function __destruct()
    {
        fclose($this->_fileHandler);
        $this->_fileHandler = null;
    }

    /**
     * Logs a message
     * 
     * @param int $logLevel
     * @param string $message
     * @return boolean 
     */
    public function log($logLevel, $message)
    {
        if ($logLevel > $this->_options['logLevel'])
        {
            return false;
        }

        $date = !empty($message) ? '[' . date('Y-m-d H:i:s') . '] ' : '';
        $data = $date . $message . PHP_EOL;
        fwrite($this->_fileHandler, $data);

        return true;
    }
}