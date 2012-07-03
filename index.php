<?php

/**
 * ImageServe
 * 
 * Lightweight extendable application for serving resized images.
 * Easy configuration and usage, extendable with plugins.
 * 
 * REQUIREMENTS
 * PHP 5.3, Apache mod_rewrite, GD Library
 * 
 * LICENSE
 * CC BY-SA http://creativecommons.org/licenses/by-sa/3.0
 * http://creativecommons.org/licenses/by-sa/3.0/legalcode
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright 2012 Orkan Alat
 * @license CC BY-SA http://creativecommons.org/licenses/by-sa/3.0
 * @version 0.1
 */

error_reporting(E_ALL);

// Set some path constants
if(!defined('BASE_PATH'))
    define('BASE_PATH', dirname(__FILE__) . '/');

if(!defined('LIB_PATH'))
    define('LIB_PATH', dirname(__FILE__) . '/Imageserve/');

if(!defined('ENV'))
    define('ENV', 'development');

require_once LIB_PATH . 'Logger.php';

$logger = new ImageServe_Logger();

$imageServe = new ImageServe($logger);
$imageServe->run();

/**
 * ImageServe
 */
class ImageServe
{

    const MODE_RESIZE = 'resize';
    const MODE_FILL = 'fill';
    const PNG_QUALITY = 9;
    const JPG_QUALITY = 100;

    /**
     * Default config
     * 
     * @var array
     */
    protected $_config = array(
        'useCache' => false,
        'cachePath' => './Imageserve/storage/cache/',
        'storagePath' => './Imageserve/storage/',
        'packages' => array(
            'thumbnail' => array(
                'width' => 160,
                'height' => 160,
                'mode' => 'fill'
            )
        ),
        'pluginsPath' => './Imageserve/Plugins/',
        'plugins' => array(),
        'logger' => array(
            'type' => 'void',
            'file' => './Imageserve/log/log.txt',
            'logLevel' => ImageServe_Logger::LEVEL_ERROR
        )
    );

    /**
     * Defaults for a package
     * 
     * @var array
     */
    protected $_defaultPackage = array(
        'width' => 100,
        'height' => 100,
        'mode' => 'resize', // resize, crop, fill
        'proportional' => true,
        'allowUpscaling' => false,
        'canvasColor' => 'ffffff'
    );

    /**
     * Stores the request data
     * 
     * @var array
     */
    protected $_request = array();

    /**
     * Stores registered plugins
     * 
     * @var array
     */
    protected $_plugins = array();

    /**
     * Stores registered hooks
     * 
     * @var array
     */
    protected $_hooks = array();

    /**
     * Stores logger class
     * 
     * @var Imageserve_Logger_Interface 
     */
    protected $_logger = null;

    public function __construct(Imageserve_Logger_Interface $logger)
    {
        if(!function_exists('imagecreatetruecolor')) {
            throw new Exception('GD Library not available.');
        }

        $this->_logger = $logger;
    }

    /**
     * Main method, runs the gears
     * 
     * @return bool
     */
    public function run()
    {
        $this->_prepareConfig();
        $this->_prepareLogger();
        $this->_preparePlugins();

        $this->_request = $this->_getRequest($_GET);

        $this->_logger->logDebug('Processing request for file "' . $this->_request['filename'] . '".');

        if($this->_config['useCache'] && $this->_isCachedImage()) {

            $image = $this->_getImageFromCache();
            $this->_serveStringAsImage($image);
            $this->_logger->logDebug('Image served from cache.');

            return true;
        }

        $image = $this->_createImage();

        if($this->_config['useCache']) {
            $success = $this->_cacheImage($image);
            $this->_logger->logDebug($success ? 'Image saved to cache.' : 'Error while saving image to cache.');
        }

        $this->_serveResourceAsImage($image);

        return true;
    }

    /**
     * Checks for user config and merges with default config 
     */
    protected function _prepareConfig()
    {
        $configPath = LIB_PATH . 'config.php';
        $userConfig = array();

        if(file_exists($configPath)) {
            $userConfig = require $configPath;
        }

        $thumbnailPackage = $this->_config['packages']['thumbnail'];

        $this->_config = array_merge($this->_config, $userConfig);

        if(isset($this->_config['packages']['thumbnail'])) {
            $this->_config['packages']['thumbnail'] = array_merge($thumbnailPackage, $this->_config['packages']['thumbnail']);
        }
        else {
            $this->_config['packages']['thumbnail'] = $thumbnailPackage;
        }

        foreach($this->_config['packages'] as $packageKey => $package) {
            $this->_config['packages'][$packageKey] = array_merge($this->_defaultPackage, $package);
        }
    }

    /**
     *  Instanciates concrete logger class and adds it to the composite
     */
    protected function _prepareLogger()
    {
        $loggerClass = 'ImageServe_Logger_' . ucfirst($this->_config['logger']['type']);
        $loggerFile = LIB_PATH . 'Logger/' . ucfirst($this->_config['logger']['type']) . '.php';

        if(file_exists($loggerFile)) {
            require_once $loggerFile;
            $this->_logger->addLogger(new $loggerClass($this->_config['logger']));
        }
    }

    /**
     * 
     */
    protected function _preparePlugins()
    {
        if(!empty($this->_config['plugins']) && is_array($this->_config['plugins'])) {

            foreach($this->_config['plugins'] as $pluginName) {

                $pluginName = ucfirst($pluginName);
                $pluginFile = $this->_config['pluginsPath'] . $pluginName . '.php';
                $pluginClass = 'ImageServe_Plugin_' . $pluginName;

                if(file_exists($pluginFile)) {

                    require_once $pluginFile;

                    // @todo refactor to unblocking system
                    try {
                        $plugin = new $pluginClass($this);

                        if($plugin instanceof ImageServe_Plugin) {
                            $this->_registerPlugin($pluginName, $plugin);
                            $plugin->init();
                        }
                    }
                    catch(Exception $e) {
                        throw new Exception('Error while loading plugin "' . $pluginName . '": ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Checks the request and sets request data
     * 
     * @param array $params
     * @return array
     * @throws Exception 
     */
    protected function _getRequest($params)
    {
        if(!isset($params['file']) || !is_string($params['file'])) {
            throw new Exception('No file given.');
        }

        preg_match('/^(.*)_(.*)\.(png|gif|jpg|jpeg)$/', $params['file'], $fileParts);

        if(!isset($fileParts[1]) || !isset($fileParts[2]) || !isset($fileParts[3])) {
            throw new Exception('Invalid request syntax.');
        }

        $request = array(
            'blueprintFile' => $fileParts[1] . '.' . $fileParts[3],
            'package' => $fileParts[2],
            'filename' => $params['file']
        );

        if(!file_exists($this->_config['storagePath'] . $request['blueprintFile'])) {
            throw new Exception('Requested image does not exist.');
        }

        if(!isset($this->_config['packages'][$request['package']])) {
            throw new Exception('Package "' . $request['package'] . '" is not defined.');
        }

        $request['blueprintSize'] = getimagesize($this->_config['storagePath'] . $request['blueprintFile']);
        $request['mimeType'] = $request['blueprintSize']['mime'];
        $request['imageType'] = $request['blueprintSize'][2];

        return $request;
    }

    /**
     * Register given plugin
     * 
     * @param string $pluginName
     * @param ImageServe_Plugin $plugin
     */
    protected function _registerPlugin($pluginName, $plugin)
    {
        if(!isset($this->_plugins[$pluginName])) {
            $this->_plugins[$pluginName] = $plugin;
        }
    }

    protected function _callHook($hookName, $params = array())
    {
        
    }

    /**
     * Checks if requested image is cached
     * 
     * @return bool
     */
    protected function _isCachedImage()
    {
        // @todo max lifetime
        return file_exists($this->_config['cachePath'] . $this->_request['filename']);
    }

    /**
     * Read and return cached image file
     * 
     * @return string
     */
    protected function _getImageFromCache()
    {
        return file_get_contents($this->_config['cachePath'] . $this->_request['filename']);
    }

    /**
     * Saves a image resource to filesystem
     * 
     * @param resource $image
     * @return bool
     * @throws Exception 
     */
    protected function _cacheImage($image)
    {
        $cacheFile = $this->_config['cachePath'] . $this->_request['filename'];
        $success = false;

        // @todo non-blocking 
        try {

            if(preg_match('/^image\/(?:jpg|jpeg)$/i', $this->_request['mimeType'])) {
                $success = imagejpeg($image, $cacheFile, self::JPG_QUALITY);
            }
            else if(preg_match('/^image\/png$/i', $this->_request['mimeType'])) {
                $success = imagepng($image, $cacheFile, self::PNG_QUALITY);
            }
            else if(preg_match('/^image\/gif$/i', $this->_request['mimeType'])) {
                $success = imagegif($image, $cacheFile);
            }
        }
        catch(Exception $e) {
            throw new Exception('Error while caching image "' . $cacheFile . '": ' . $e->getMessage());
        }

        return $success;
    }

    /**
     * 
     */
    protected function _createImage()
    {
        $blueprintPath = $this->_config['storagePath'] . $this->_request['blueprintFile'];

        if(!preg_match('/^image\/(?:gif|jpg|jpeg|png)$/i', $this->_request['mimeType'])) {
            throw new Exception('Wrong MIME-Type.');
        }

        /*
         * Calculate dimensions (corresponds mode 'resize')
         */

        $newWidth = (int) $this->_getPackageOption('width', 0);
        $newHeight = (int) $this->_getPackageOption('height', 0);

        if($newWidth == 0 && $newHeight == 0) {
            throw new Exception('Misconfigured package dimensions.');
        }

        $blueprint = false;

        switch($this->_request['mimeType']) {
            case 'image/png':
                $blueprint = imagecreatefrompng($blueprintPath);
                break;

            case 'image/jpeg':
                $blueprint = imagecreatefromjpeg($blueprintPath);
                break;

            case 'image/jpg':
                $blueprint = imagecreatefromjpeg($blueprintPath);
                break;

            case 'image/gif':
                $blueprint = imagecreatefromgif($blueprintPath);
                break;
        }

        if($blueprint === false) {
            throw new Exception('Could not open the blueprint.');
        }

        $width = imagesx($blueprint);
        $height = imagesy($blueprint);
        $blueprintOffsetX = 0;
        $blueprintOffsetY = 0;
        $imageOffsetX = 0;
        $imageOffsetY = 0;

        if(!$this->_getPackageOption('allowUpscaling', false) && $width < $newWidth) {
            $newWidth = $width;
        }

        if(!$this->_getPackageOption('allowUpscaling', false) && $height < $newHeight) {
            $newHeight = $height;
        }

        if($newHeight == 0) {
            $newHeight = floor($height * ($newWidth / $width));
        }
        else if($newWidth == 0) {
            $newWidth = floor($width * ($newHeight / $height));
        }

        $resizedWidth = $newWidth;
        $resizedHeight = $newHeight;

        $hRatio = $newHeight / imagesy($blueprint);
        $wRatio = $newWidth / imagesx($blueprint);
        $ratio = min($hRatio, $wRatio);

        if(!$this->_getPackageOption('allowUpscaling', false) && $ratio > 1.0) {
            $ratio = 1.0;
        }

        /*
         * Consider fill mode
         */

        if($this->_getPackageOption('mode') == self::MODE_FILL) {

            $resizedWidth = floor($width * $ratio);
            $resizedHeight = floor($height * $ratio);

            $imageOffsetX = floor(($newWidth - $resizedWidth) / 2);
            $imageOffsetY = floor(($newHeight - $resizedHeight) / 2);
        }

        /*
         * Create new image
         */

        $image = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($image, false);

        $canvasColorRed = hexdec(substr($this->_getPackageOption('canvasColor'), 0, 2));
        $canvasColorGreen = hexdec(substr($this->_getPackageOption('canvasColor'), 2, 2));
        $canvasColorBlue = hexdec(substr($this->_getPackageOption('canvasColor'), 4, 2));

        $color = imagecolorallocatealpha($image, $canvasColorRed, $canvasColorGreen, $canvasColorBlue, 0);

        imagefill($image, 0, 0, $color);
        imagesavealpha($blueprint, true);
        imagecopyresampled($image, $blueprint, $imageOffsetX, $imageOffsetY, $blueprintOffsetX, $blueprintOffsetY, $resizedWidth, $resizedHeight, $width, $height);

        return $image;
    }

    /**
     * 
     */
    protected function _serveResourceAsImage($image)
    {
        // @todo temporary headers
        header('Content-Type: ' . $this->_request['mimeType']);
//        header('Content-Length: ' . '2180000');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header("Pragma: no-cache");

        if(preg_match('/^image\/(?:jpg|jpeg)$/i', $this->_request['mimeType'])) {
            imagejpeg($image, null, self::JPG_QUALITY);
        }
        else if(preg_match('/^image\/png$/i', $this->_request['mimeType'])) {
            imagepng($image, null, self::PNG_QUALITY);
        }
        else if(preg_match('/^image\/gif$/i', $this->_request['mimeType'])) {
            imagegif($image);
        }
    }

    /**
     * Serves given image data
     * 
     * @param string $image 
     */
    protected function _serveStringAsImage($image)
    {
        // @todo temporary headers
        header('Content-Type: ' . $this->_request['mimeType']);
        header('Content-Length: ' . strlen($image));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header("Pragma: no-cache");

        echo $image;
    }

    /**
     * Returns a package option if set or the default value
     * 
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    protected function _getPackageOption($option, $default = null)
    {
        if(isset($this->_config['packages'][$this->_request['package']][$option])) {
            return $this->_config['packages'][$this->_request['package']][$option];
        }

        return $default;
    }

    /**
     * Returns mime type of given file
     * 
     * @param string $file
     * @return string 
     */
    protected function _getMimeType($file)
    {
        $info = getimagesize($file);

        if(is_array($info) && isset($info['mime'])) {
            return $info['mime'];
        }

        return '';
    }

}
