ImageServe
==========

Lightweight extendable application for serving resized images.
Easy configuration and usage, extendable with plugins.

**This project is still in alpha stadium and should not be used in a productive environment!**

Intro
-----

ImageServe uses mod_rewrite to deliver dynamic resized versions for a requested image.

Features & ToDos
----------------

* Return resized image
* Rendered images can be chached
* Debugging/Logging
* Todo: Default image
* Todo: Garbage collection & cache lifetimes
* Todo: Plugins
* Todo: Crop mode

Basic Usage
-----------

* Copy files to a subdomain or subfolder in your htdocs (e.g. img.domain.com or domain.com/img)
* Customize config and define packages
* Store images in storage folder
* Set propper rights for cache folder
* Request image 
    e.g. <img src="http://localhost/img/banana_thumbnail.png" />

See wiki for detailed information

Requirements
------------

* PHP 5.3 
* Apache mod_rewrite
* GD Library
* Plugins may have additional requirements

Configuration & Packages
------------------------

Hook into the core and customize with plugins
---------------------------------------------

Debugging/Logging
-----------------

License
-------
CC BY-SA 
http://creativecommons.org/licenses/by-sa/3.0