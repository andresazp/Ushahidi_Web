<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Minify Library
 *
 * $Id: libraries/Minify.php $
 *
 * @package    Minify
 * @author     Tom Morton
 * @copyright  (c) 2009 Tom Morton
 */
 
class Minify_Core {
    
    public function __construct($type)
    {
         // normalize!
        $this->type = strtolower($type);
        
        // check we have a type that can be handled ok
        if(!in_array($this->type,array('js','css')))
            throw new Kohana_Exception('minify.unhandled_filetype',$this->type);
            
    }
    
    public function headers()
    {
        // this line is a hack because it means Minify cant support more than 2 file types
        // will fix when I am awake :)
        $ctype = ($this->type == 'css') ? 'text/css' : 'application/javascript';
        
        // Gzip
        if ( Kohana::config('config.output_compression') === FALSE )
        {
            ob_start ("ob_gzhandler");
        }
        
        // Headers
        // THIS one is important to make sure everything works ok
        header('Content-type: '.$ctype);
        // and some expirey stuff per Yahoo's extensive research :P
        header('Cache-Control: must-revalidate');#
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + Kohana::config('minify.expires')) . ' GMT');
    }
        
    public function compress($file,$noheaders=False) 
    {
        // check again because people tend to break stuff
        if(!in_array($this->type,array('js','css')))
            throw new Kohana_Exception('minify.unhandled_filetype',$this->type);
            
        // handle file extensions
        //$file = explode(".",$file)[0];
        //$file = explode(".",$file);
        // NOTE TO SELF: PHP !== Python
        // you cant string things like that together, even in PHP
        //$file = $file[0];
        // UPDATE 1.5; issue #1715 / #3 supporting dots in filenames
        $file = str_replace('.'.$this->type, '', $file);
        
        // Extended to include caching of the data
        $cache = Cache::instance();

        // have we got it cached? (or are we using caching)
        if(IN_PRODUCTION == False || ($cached = $cache->get($this->type.'_'.sha1($file))) === NULL)
        {
            // driver name
            $driver = 'Minify_'.ucfirst($this->type).'_Driver';
            
            // blah blah load stuff (stolen from Kohana core libs)
            if ( ! Kohana::auto_load($driver))
                throw new Kohana_Exception('core.driver_not_found', ucfirst($this->type), 'Minify');

            $Kfile = DOCROOT.$file.'.'.$this->type;
            if (!file_exists($Kfile))
                throw new Kohana_404_Exception($Kfile);
            
            // load the driver
            $minify = new $driver(file_get_contents($Kfile));
            
            // minify the page
            $cached = $minify->min();
            
            // set the cache
            $cache->set($this->type.'_'.sha1($file), $cached, array(),Kohana::config('minify.cache_lifetime'));
            
        }
        
        // headers
        if(!$noheaders)
            $this->headers();
        
        // return the minified data
        return $cached;
    }
    
    /*
     *  Returns a group of files based on your configuration settings
     *
     *  Finds the group then runs through each file (caching them individually)
     *  before finally returning all of the data strung together
     *  
     */
    public static function group($group, $noheaders=false)
    {
        // load the group from our configuration file
        $grouplist = Kohana::config('minify.groups');
        
        // error if we dont have a group
        if(!array_key_exists($group,$grouplist))
            throw new Kohana_Exception('minify.no_group',$group);
        
        // put the group in a local var
        $group = $grouplist[$group];
        
        // get a generic object we can minify with
        $min = new Minify($group['type']);
        
        // initialize the string
        $group_minified = '';
        
        if(array_key_exists('dir',$group))
        {
            foreach(scandir($group['dir']) as $file)
            {
                if(!is_dir($file))
                {
                    $group_minified .= $min->compress($file,True);
                }
            }
        }
        if(array_key_exists('files',$group))
        {
            // iterate on the files
            foreach($group['files'] as $file)
            {
                // compress each file
                $group_minified .= $min->compress($file,True);
            }
        }
        
        // send headers
        if (! $no_headers)
          $this->headers();
        
        return $group_minified;
    }
  }