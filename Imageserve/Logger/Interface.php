<?php

/**
 * Logger interface.
 * 
 * @author Orkan Alat <orkan@about-orkan.de>
 * @copyright Orkan Alat <orkan@about-orkan.de>
 * @category Imageserve
 * @package Logger
 */
interface ImageServe_Logger_Interface
{
    public function log($logLevel, $message);
}