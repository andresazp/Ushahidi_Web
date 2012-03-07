<?php defined('SYSPATH') or die('No direct script access.');

/* 
 * File expirey time
 * Recommend this is set HIGH (1 yr or more) in production sites
 * and super low (10s) for developments sites
 * Remember if you screw this up and set it too long you need to change the filename
 * because otherwise it wont refresh :)
 *                                                                                 ?     1 yr             :   10s
 */ 
$config['expires'] = 31536000;


/* 
 * Time in seconds to cache the files
 * as these are fairly static files you can properly
 * set it quite high for IN_PRODUCTION sites
 * Whereas on development sites we want it fairly
 * low (and take the performance hit)
 * Remember 0 == Cache forever
 *                                                                                                 ?     12hrs   :   30s
 */
$config['cache_lifetime'] = 43200;

/*
 *  Groups Setup
 *
 */
$config['groups'] = array();