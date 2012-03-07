<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Minify CSS driver interface.
 *
 * $Id: Css.php $
 *
 * @package    Minify
 * @author     Tom Morton
 *
 * BASED ON THE CSSMIN  CODE BY joe.scylla: http://code.google.com/p/cssmin
 *
 *  Changes
 *
 *      - Wrapped into a Kohana Driver
 *      - Changed syntax slightly
 *          + switched from static to instanced
 *          + minify($css,$opts) -> min()
 *          + added _construct($css)
 *      - cut out some uneeded code :)
 */

 
/**
 * cssmin.php - A simple CSS minifier.
 * --
 * 
 * <code>
 * include("cssmin.php");
 * file_put_contents("path/to/target.css", cssmin::minify(file_get_contents("path/to/source.css")));
 * </code>
 * --
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * --
 *
 * @package     cssmin
 * @author              Joe Scylla <joe.scylla@gmail.com>
 * @copyright   2008 Joe Scylla <joe.scylla@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @version     1.0.1.b3 (2008-10-02)
 */

 
 
class Minify_Css_Driver implements Minify_Driver {

    public function __construct($input) {
        $this->input       = str_replace("\r\n", "\n", $input);
    }
    
    public function min()
    {
        $css = $this->input;
        $options = "";
            
        $options = ($options == "") ? array() : (is_array($options) ? $options : explode(",", $options));

        // Remove comments
        $css = preg_replace("/\/\*[\d\D]*?\*\/|\t+/", " ", $css);
        // Replace CR, LF and TAB to spaces
        $css = str_replace(array("\n", "\r", "\t"), " ", $css);
        // Replace multiple to single space
        $css = preg_replace("/\s\s+/", " ", $css);
        // Remove unneeded spaces
        $css = preg_replace("/\s*({|}|\[|\]|=|~|\+|>|\||;|:|,)\s*/", "$1", $css);
        if (in_array("remove-last-semicolon", $options))
           {
            // Removes the last semicolon of every style definition
            $css = str_replace(";}", "}", $css);
            }
        $css = trim($css);
        return $css;
    }


    public function cssmin_array_clean(array $array)
    {
        $r = array();
        $c = count($v);
        if (cssmin_array_is_assoc($array))
        {
            foreach ($array as $key => $value)
            {
                $r[$key] = trim($value);
            }
        }
        else
        {
            foreach ($array as $value)
            {
                if (trim($value) != "")
                {
                    $r[] = trim($value);
                }
            }
        }
        return $r;
    }
}
