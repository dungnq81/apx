<?php

use Defuse\Crypto\Core;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use ReCaptcha\ReCaptcha;

defined('BASEPATH') OR exit('No direct script access allowed.');

// -------------------------------------------------------------
// Youtube helper
// -------------------------------------------------------------

if (! function_exists('youtube_iframe'))
{
	/**
	 * @param $url
	 * @param $w
	 * @param $h
	 * @param int $autoplay
	 *
	 * @return string
	 */
	function youtube_iframe($url, $w = 'auto', $h = 'auto', $autoplay = 0)
	{
		parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        if (isset($vars['v']))
		{
			$idurl = $vars['v'];
			$string = "<iframe width='" . $w . "' height='" . $h . "' src='https://www.youtube.com/embed/" . $idurl . "?wmode=opaque&autoplay=" . $autoplay . "' allowfullscreen></iframe>";

			return $string;
		}

		return NULL;
	}
}

// -------------------------------------------------------------

if (! function_exists('youtube_embed_url')) {
    /**
     * @param $url
     *
     * @return string|null
     */
    function youtube_embed_url($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        if (isset($vars['v']))
        {
            $idurl = $vars['v'];
            $string = "https://www.youtube.com/embed/" . $idurl;

            return $string;
        }

        return NULL;
    }
}

// -------------------------------------------------------------

if (! function_exists('youtube_image'))
{
	/**
	 * @param $url
	 * @param array $resolution
	 *
	 * @return string
	 */
	function youtube_image($url, $resolution = [])
	{
		if (! is_array($resolution) OR is_empty($resolution))
		{
            $resolution = [
                'default',
                'hqdefault',
                'mqdefault',
                'maxresdefault',
                'sddefault',
            ];
		}

        $url_img = pixel_img();
		parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        if (isset($vars['v']))
        {
            $id = $vars['v'];
            for ( $x = 0; $x < sizeof( $resolution ); $x ++ )
            {
                $url_img = 'https://img.youtube.com/vi/' . $id . '/' . $resolution[ $x ] . '.jpg';
                $headers = get_headers( $url_img, 1 );
                if ( $headers == FALSE ) {}
                else if ( $headers[0] == 'HTTP/1.0 200 OK' ) break;
            }
        }

        return $url_img;
	}
}

// -------------------------------------------------------------

if (! function_exists('youtube_views'))
{
    /**
     * @param $url
     * @param string $key
     * @return string|null
     */
	function youtube_views($url, $key = '')
	{
		$CI = &get_instance();
		$CI->load->library('form_validation');

        if (!$CI->form_validation->valid_url($url))
            return NULL;

        $views = 0;
        parse_str( parse_url( $url, PHP_URL_QUERY ), $vars );
        if (isset($vars['v']))
        {
            $key OR $key = 'AIzaSyAmrJOshYMWICuo3QMjtQ4rWucvMHdOsYI';
            $id      = $vars['v'];
            $jsonURL = url_contents( "https://www.googleapis.com/youtube/v3/videos?id={$id}&key={$key}&part=statistics" );
            $json    = json_decode( $jsonURL );
            $views   = $json->{'items'}[0]->{'statistics'}->{'viewCount'};
        }

        return number_format( $views );
	}
}

// ------------------------------------------------------------------------
// pagination helper
// ------------------------------------------------------------------------

if (! function_exists('create_pagination'))
{
	/**
	 * The Pagination helper cuts out some of the bumf of normal pagination
	 *
	 * @param string $uri The current URI.
	 * @param int $total_rows The total of the items to paginate.
	 * @param int|null $limit How many to show at a time.
	 * @param int $uri_segment The current page.
	 *
	 * @return array The pagination array.
	 * @see Pagination::create_links()
	 */
	function create_pagination($uri, $total_rows, $limit = NULL, $uri_segment = 4)
	{
		$CI = &get_instance();
		$CI->load->library('pagination');

		$current_page = $CI->uri->segment($uri_segment, 0);
		$suffix = $CI->config->item('url_suffix');

		$limit = $limit === NULL ? $CI->setting->records_per_page : $limit;

		// Initialize pagination
		$CI->pagination->initialize([
			'suffix' => $suffix,
			'base_url' => (! $suffix) ? rtrim(site_url($uri), $suffix) : site_url($uri),
			'total_rows' => $total_rows,
			'per_page' => $limit,
			'uri_segment' => $uri_segment,
			'use_page_numbers' => TRUE,
			'reuse_query_string' => TRUE,
		]);

		$offset = $limit * ($current_page - 1);

		//avoid having a negative offset
		if ($offset < 0)
            $offset = 0;

		return [
			'current_page' => $current_page,
			'per_page' => $limit,
			'limit' => $limit,
			'offset' => $offset,
			'links' => $CI->pagination->create_links()
		];
	}
}

// ------------------------------------------------------------------------
// text helper
// ------------------------------------------------------------------------

if (! function_exists('remove_empty_tags'))
{
	/**
	 * @param $html
	 *
	 * @return null|string|string[]
	 */
	function remove_empty_tags($html)
	{
		do
		{
			$tmp = $html;
			$html = preg_replace('#<([^ >]+)[^>]*>([[:space:]]|&nbsp;)*</\1>#', '', $html);
		}
		while ($html !== $tmp);

		return $html;
	}
}

// -------------------------------------------------------------

if (! function_exists('json_encode_uni'))
{
	/**
	 *
	 * Unicode json_encode
	 *
	 * @param $arr
	 * @param bool $escape
	 *
	 * @return mixed|string
	 */
	function json_encode_uni($arr, $escape = FALSE)
	{
		if (is_php('5.4'))
			$result = json_encode($arr, JSON_UNESCAPED_UNICODE);
		else
			$result = (ICONV_ENABLED == TRUE) ? preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", json_encode($arr)) : json_encode($arr);

		if ($escape === TRUE)
		{
			$CI = &get_instance();
			return $CI->db->escape($result);
		}

		return $result;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('nl2p'))
{
	/**
	 * Replaces new lines with <p> HTML element.
	 *
	 * @param string $str The input string.
	 *
	 * @return string The HTML string.
	 */
	function nl2p($str)
	{
		return str_replace('<p></p>', '', '<p>' . nl2br(preg_replace('#(\r?\n){2,}#', '</p><p>', $str)) . '</p>');
	}
}

// -------------------------------------------------------------

if (! function_exists('br2nl'))
{
	/**
	 * @param string $buff
	 *
	 * @return mixed|string
	 */
	function br2nl($buff = '')
	{
		$buff = preg_replace('#<br[/\s]*>#si', "\n", $buff);
		$buff = trim($buff);

		return $buff;
	}
}

// -------------------------------------------------------------

if (! function_exists('js_escape'))
{
    /**
     * Normalize the string for JavaScript string value
     *
     * @param string $string
     * @return string
     */
    function js_escape($string = '')
    {
        return
            preg_replace('/\r?\n/', "\\n",
                str_replace('"', "\\\"",
                str_replace("'", "\\'",
                str_replace("\\", "\\\\",
                $string))));
    }
}

// ------------------------------------------------------------------------

if (! function_exists('process_data_jmr1'))
{

	// Set PCRE recursion limit to sane value = STACKSIZE / 500 (256KB stack. Win32 Apache or  8MB stack. *nix)
	ini_set('pcre.recursion_limit', (strtolower(substr(PHP_OS, 0, 3)) === 'win' ? '524' : '16777'));

	/**
	 * Process data JMR1
	 *
	 * Minifying final HTML output
	 *
	 * @param string $text The HTML output
	 *
	 * @return string  The HTML without white spaces or the input text if its is too big to your SO proccess.
	 * @author Alan Moore, ridgerunner
	 * @author Marcos Coelho <marcos@marcoscoelho.com>
	 * @see http://stackoverflow.com/q/5312349
	 */
	function process_data_jmr1($text = '')
	{
		$re = '%                            # Collapse whitespace everywhere but in blacklisted elements.
        (?>                                 # Match all whitespans other than single space.
          [^\S]\s*                          # Either one [\t\r\n\f\v] and zero or more ws,
          |\s{2,}                           # or two or more consecutive-any-whitespace.
        )				                    # Note: The remaining regex consumes no text at all...
        (?=                                 # Ensure we are not in a blacklist tag.
          [^<]*+                            # Either zero or more non-"<" {normal*}
          (?:                               # Begin {(special normal*)*} construct
            <                               # or a < starting a non-blacklist tag.
            (?!/?(?:textarea|pre|script)\b)
            [^<]*+                          # more non-"<" {normal*}
          )*+                               # Finish "unrolling-the-loop"
          (?:                               # Begin alternation group.
            <                               # Either a blacklist start tag.
            (?>textarea|pre|script)\b
            |\z                             # or end of file.
          )                                 # End alternation group.
        )                                   # If we made it here, we are not in a blacklist tag.
        %Six';

		if (($data = preg_replace($re, ' ', $text)) === NULL)
		{
			log_message('error', 'PCRE Error! Output of the page "' . uri_string() . '" is too big.');
			return $text;
		}

		return $data;
	}
}

// ------------------------------------------------------------------------
// string helper
// ------------------------------------------------------------------------

if (! function_exists('trim_s'))
{
    /**
     * @param string $str
     * @return string|string[]|null
     */
    function trim_s($str = '')
    {
        return preg_replace('/\s+/', '', $str);
    }
}

// -------------------------------------------------------------

if (! function_exists('uuid'))
{
	/**
	 * Universally Unique Identifier
	 *
	 * A UUID is a 16-octet (128-bit) number.
	 * In its canonical form, a UUID is represented by 32 hexadecimal digits, displayed in five groups separated by hyphens,
	 * in the form 8-4-4-4-12 for a total of 36 characters (32 alphanumeric characters and four hyphens).
	 *
	 * @return string
	 */
	function uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0C2f) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0x2Aff), mt_rand(0, 0xffD3), mt_rand(0, 0xff4B)
		);
	}
}

// -------------------------------------------------------------

if (! function_exists('normalize_filename'))
{
    /**
     * Normalize given filename. Accented characters becomes non-accented and
     * removes any other special characters. Usable for non-unicode filesystems
     *
     * @param $filename
     * @return string
     */
    function normalize_filename($filename)
    {
        $string = htmlentities($filename, ENT_QUOTES, 'UTF-8');
        if (strpos($string, '&') !== FALSE)
            $filename = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');

        $filename = trim(preg_replace('~[^0-9a-z\.\- ]~i', "_", $filename));
        return $filename;
    }
}

// -------------------------------------------------------------

if (! function_exists('salt'))
{
    /**
     * @param int $size
     * @return string
     * @throws EnvironmentIsBrokenException
     */
	function salt($size = 32)
	{
	    $size OR $size = Key::KEY_BYTE_SIZE;
		return bin2hex(Core::secureRandom($size));
	}
}

// -------------------------------------------------------------

if (! function_exists('strposa'))
{
	/**
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 *
	 * @return bool
	 */
	function strposa($haystack, $needle, $offset = 0)
	{
		if (! is_array($needle))
			$needle = [$needle];

		foreach ($needle as $query)
		{
			if (strpos($haystack, $query, $offset) !== FALSE)
				return TRUE; // stop on first true result
		}

		return FALSE;
	}
}

// -------------------------------------------------------------

if (! function_exists('str_to_bool'))
{
	/**
	 * Converts various string bools to a true bool
	 *
	 * @param string $value
	 * @return bool
	 */
	function str_to_bool($value = '')
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
}

// -------------------------------------------------------------

if (! function_exists('str_replace_first'))
{
	/**
	 * @param $find
	 * @param string $replace
	 * @param $subject
	 *
	 * @return string
	 */
	function str_replace_first($find, $replace = '', $subject = '')
	{
		if (! is_string($replace) OR ! is_string($subject))
			return $subject;

		// stolen from the comments at PHP.net/str_replace
		// Splits $subject into an array of 2 items by $find,
		// and then joins the array with $replace
		return implode($replace, explode($find, $subject, 2));
	}
}

// -------------------------------------------------------------

if (! function_exists('standard_phone'))
{
	/**
	 * @param $phone
	 *
	 * @return string
	 */
	function standard_phone($phone)
	{
		return preg_replace('/[^0-9]+/', '', $phone);
	}
}

// -------------------------------------------------------------

if (! function_exists('verified_phone'))
{
	/**
	 * @param $phone
	 *
	 * @return string
	 */
	function verified_phone($phone)
	{
		$preg = preg_replace('/[^0-9]+/', '', $phone);
		return substr($preg, -9, 9);
	}
}

// -------------------------------------------------------------

if (! function_exists('compare_phone'))
{
	/**
	 * @param $dbphone
	 * @param $phone
	 *
	 * @return bool
	 */
	function compare_phone($dbphone, $phone)
	{
		$dbphone = preg_replace('/[^0-9]+/', '', $dbphone);
		$phone = preg_replace('/[^0-9]+/', '', $phone);

		if (strcmp(substr($dbphone, -9, 9), substr($phone, -9, 9)) === 0)
			return TRUE;

		return FALSE;
	}
}

// -------------------------------------------------------------
// file helper
// -------------------------------------------------------------

if (! function_exists('pixel_img')) {
    /**
     * @param string $img_url
     *
     * @return string
     */
    function pixel_img($img_url = '')
    {
        if (file_exists($img_url))
            return $img_url;

        return site_url('/') . "uploads/pixel.png";
    }
}

// -------------------------------------------------------------

if (! function_exists('valid_image'))
{
    /**
     * @param $src_file_name
     * @param array $supported_ext_image
     * @return bool
     */
	function valid_image($src_file_name, $supported_ext_image = [])
	{
		if (! is_array($supported_ext_image) OR empty($supported_ext_image))
		{
            $supported_ext_image = [
				'gif',
				'jpg',
				'jpeg',
				'png'
			];
		}

		$ext = get_file_extension($src_file_name, FALSE);
		if (in_array($ext, $supported_ext_image))
			return TRUE;

		return FALSE;
	}
}

// -------------------------------------------------------------

if (! function_exists('get_file_extension'))
{
	/**
	 * @param $filename
	 * @param bool $include_dot
	 *
	 * @return string
	 */
	function get_file_extension($filename, $include_dot = TRUE)
	{
		$dot = '';
		if ($include_dot == TRUE)
			$dot = '.';

		if (is_php('5.4'))
			return $dot . strtolower((new SplFileInfo($filename))->getExtension());

		return $dot . strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	}
}

// -------------------------------------------------------------

if (! function_exists('get_file_name'))
{
	/**
	 * @param $filename
	 * @param bool $include_ext
	 *
	 * @return string
	 */
	function get_file_name($filename, $include_ext = FALSE)
	{
		if (is_php('5.4'))
			return $include_ext ? (new SplFileInfo($filename))->getFilename() : (new SplFileInfo($filename))->getBasename(get_file_extension($filename));

		return $include_ext ? pathinfo($filename, PATHINFO_FILENAME) . get_file_extension($filename) : pathinfo($filename, PATHINFO_FILENAME);
	}
}

// -------------------------------------------------------------
// directory helper
// -------------------------------------------------------------

if (! function_exists('recurse_copy'))
{
	/**
	 * @param $src
	 * @param $dst
	 */
	function recurse_copy($src, $dst)
	{
		$dir = opendir($src);
		if (! file_exists($dst))
			mkdir($dst, 0755, TRUE);

		while (FALSE !== ($file = readdir($dir)))
		{
			if (($file != '.') && ($file != '..'))
			{
				if (is_dir($src . '/' . $file))
					recurse_copy($src . '/' . $file, $dst . '/' . $file);
				else
					copy($src . '/' . $file, $dst . '/' . $file);
			}
		}

		closedir($dir);
	}
}

// -------------------------------------------------------------

if (! function_exists('create_directory'))
{
	/**
	 * recursively create a long directory path
	 *
	 * @param $path
	 *
	 * @return bool
	 */
	function create_directory($path)
	{
		if (is_dir($path))
			return TRUE;

		$prev_path = substr($path, 0, strrpos($path, '/', - 2) + 1);
		$return = create_directory($prev_path);

		return ($return && is_really_writable($prev_path)) ? mkdir($path) : FALSE;
	}
}

// -------------------------------------------------------------

if (! function_exists('normalize_path'))
{
    /**
     * Normalize the given path. On Windows servers backslash will be replaced
     * with slash. Removes unnecessary double slashes and double dots. Removes
     * last slash if it exists.
     *
     * Examples:
     * path::normalize("C:\\any\\path\\") returns "C:/any/path"
     * path::normalize("/your/path/..//home/") returns "/your/home"
     * @param string $path
     * @return string
     */
    function normalize_path($path)
    {
        // Backslash to slash convert
        if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN") {
            $path = preg_replace('/([^\\\])\\\+([^\\\])/s', "$1/$2", $path);
            if (substr($path, -1) == "\\") $path = substr($path, 0, -1);
            if (substr($path, 0, 1) == "\\") $path = "/" . substr($path, 1);
        }

        $path = preg_replace('/\/+/s', "/", $path);

        $path = "/$path";
        if (substr($path, -1) != "/")
            $path .= "/";

        $expr = '/\/([^\/]{1}|[^\.\/]{2}|[^\/]{3,})\/\.\.\//s';
        while (preg_match($expr, $path))
            $path = preg_replace($expr, "/", $path);

        $path = substr($path, 0, -1);
        $path = substr($path, 1);
        return $path;
    }
}

// -------------------------------------------------------------

if (! function_exists('make_upload_file'))
{
	/**
	 * @param $new_path
	 * @param $new_file_name
	 * @param $tmp_file
	 *
	 * @return bool
	 */
	function make_upload_file($new_path, $new_file_name, $tmp_file)
	{
		if (create_directory($new_path))
		{
			$new_path_file = rtrim($new_path, '/') . '/' . $new_file_name;
			if (move_uploaded_file($tmp_file, $new_path_file) === TRUE)
				return TRUE;
		}

		return FALSE;
	}
}

// -------------------------------------------------------------
// array helper
// -------------------------------------------------------------

if (! function_exists('array_object_merge'))
{
	/**
	 * Merge an array or an object into another object
	 *
	 * @param object $object The object to act as host for the merge.
	 * @param object|array $array The object or the array to merge.
	 */
	function array_object_merge(&$object, $array)
	{
		// Make sure we are dealing with an array.
        is_array($array) OR $array = get_object_vars($array);
        foreach ($array as $key => $value)
            $object->{$key} = $value;
	}

}

// ------------------------------------------------------------------------

if (!function_exists('array_for_select'))
{
    /**
     * @return array|bool
     */
    function array_for_select()
    {
        $args = func_get_args();
        $return = [];
        switch (count($args))
        {
            case 3:
                foreach ($args[0] as $itteration):
                    if (is_object($itteration))
                        $itteration = (array)$itteration;
                    $return[$itteration[$args[1]]] = $itteration[$args[2]];
                endforeach;
                break;

            case 2:
                foreach ($args[0] as $key => $itteration):
                    if (is_object($itteration))
                        $itteration = (array)$itteration;
                    $return[$key] = $itteration[$args[1]];
                endforeach;
                break;

            case 1:
                foreach ($args[0] as $itteration):
                    $return[$itteration] = $itteration;
                endforeach;
                break;

            default:
                return FALSE;
        }

        return $return;
    }

}

// ------------------------------------------------------------------------

if (! function_exists('in_array_r'))
{
	/**
	 * Recursively search an array
	 * This method was copied and pasted from this URL (http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array)
	 * Real credit goes to (http://stackoverflow.com/users/427328/elusive)
	 *
	 * @param $needle
	 * @param $haystack
	 * @param bool $strict
	 *
	 * @return bool
	 */
	function in_array_r($needle, $haystack, $strict = FALSE)
	{
		foreach ($haystack as $item)
		{
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict)))
				return TRUE;
		}

		return FALSE;
	}
}

// -------------------------------------------------------------

if (! function_exists('assoc_array_prop'))
{
    /**
     * Associative array property
     *
     * Reindexes an array using a property of your elements. The elements should
     * be a collection of array or objects.
     *
     * Note: To give a full result all elements must have the property defined
     * in the second parameter of this function.
     *
     * @author Marcos Coelho
     * @param array $arr
     * @param string $prop Should be a common property with value scalar, as id, slug, order.
     * @return array
     */
    function assoc_array_prop(array &$arr = NULL, $prop = 'id')
    {
        $newarr = [];
        foreach ($arr as $old_index => $element)
        {
            if (is_array($element))
            {
                if (isset($element[$prop]) && is_scalar($element[$prop]))
                    $newarr[$element[$prop]] = $element;
            }
            elseif (is_object($element))
            {
                if (isset($element->{$prop}) && is_scalar($element->{$prop}))
                    $newarr[$element->{$prop}] = $element;
            }
        }

        return $arr = $newarr;
    }
}

// -------------------------------------------------------------

if (! function_exists('object_to_array'))
{
	/**
	 * @param $object
	 *
	 * @return array
	 */
	function object_to_array($object)
	{
		if (! is_object($object) && ! is_array($object))
			return $object;

        return array_map('object_to_array', (array)$object);
	}
}

// -------------------------------------------------------------

if (! function_exists('array_to_object'))
{
	/**
	 * @param array $arr
	 *
	 * @return array|object
	 */
	function array_to_object($arr = [])
	{
		/**
		 * Return array converted to object
		 * Using __FUNCTION__ (Magic constant)
		 * for recursive call
		 */
        return is_array($arr) ? (object)array_map(__FUNCTION__, $arr) : $arr;
	}
}

// -------------------------------------------------------------
// validate helper
// -------------------------------------------------------------

if (! function_exists('string_empty'))
{
    /**
     * @param string $str
     * @return bool
     */
    function string_empty($str = '')
    {
        if(!is_string($str))
            return FALSE;

        $val = preg_replace('/\s+/', '', $str);
        return empty($val) ? TRUE : FALSE;
    }
}

// -------------------------------------------------------------

if (! function_exists('string_not_empty'))
{
    /**
     * @param $str
     * @return bool
     */
    function string_not_empty($str)
    {
        if(! is_string($str))
            return FALSE;

        return ! string_empty($str);
    }
}

// -------------------------------------------------------------

if (! function_exists('is_empty'))
{
	/**
	 * @param string $cnt
	 * @param string $excluded_tags
	 *
	 * @return bool
	 */
	function is_empty($cnt = '', $excluded_tags = NULL)
	{
		if (empty($cnt))
			return TRUE;

        if(is_object($cnt))
            $cnt = object_to_array($cnt);

		if (is_array($cnt))
			return is_empty_array($cnt);

        $val = $cnt;
        if(is_string($cnt))
        {
            $val = strip_tags($cnt, $excluded_tags);
            $val = preg_replace('/\s+/', '', $val);
        }

		return empty($val) ? TRUE : FALSE;
	}
}

// -------------------------------------------------------------

if (! function_exists('is_empty_array'))
{
	/**
	 * @param array $arr
	 *
	 * @return bool
	 */
	function is_empty_array($arr = [])
	{
		if (is_array($arr))
		{
			foreach ($arr as $value)
			{
				if (! is_empty_array($value))
					return FALSE;
			}
		}
		elseif (! is_empty($arr))
			return FALSE;

		return TRUE;
	}
}

// -------------------------------------------------------------
// inflector helper
// -------------------------------------------------------------

if (!function_exists('keywords'))
{
    /**
     * Keywords
     *
     * Takes multiple words separated by spaces and changes them to keywords
     * Makes sure the keywords are separated by a comma followed by a space.
     *
     * @param string $str The keywords as a string, separated by whitespace.
     * @return string The list of keywords in a comma separated string form.
     */
    function keywords($str)
    {
        return preg_replace('/[\s]+/', ', ', trim($str));
    }
}

// -------------------------------------------------------------
// user helper
// -------------------------------------------------------------

if (! function_exists('role_or_die'))
{
	/**
	 * Checks if role has access to controller or returns error
	 *
	 * @param $controller
	 * @param $role
	 * @param string $redirect_to
	 * @param string $message
	 *
	 * @return bool
	 */
	function role_or_die($controller, $role, $redirect_to = 'admin', $message = '')
	{
        if (ci()->input->is_ajax_request() AND !group_has_role($controller, $role))
		{
			echo json_encode(['error' => ($message ? $message : __('cp:access_denied'))]);
			return FALSE;
		}
        elseif (!group_has_role($controller, $role))
		{
			ci()->session->set_flashdata('error', ($message ? $message : __('cp:access_denied')));
			redirect($redirect_to);
		}

		return TRUE;
	}
}

// -------------------------------------------------------------

if (! function_exists('group_has_role'))
{
	/**
	 * Checks if a group has access to controller or role
	 *
	 * @param $controller
	 * @param $role
	 *
	 * @return bool
	 */
	function group_has_role($controller, $role)
	{
        if (!ci()->current_user)
			return FALSE;

        if ('administrator' == ci()->current_user->group_name)
			return TRUE;

		// List available controller permissions for this user
        $permissions = ci()->permission_m->get_group(ci()->current_user->group_id);
        if (empty($permissions[$controller]) OR empty($permissions[$controller][$role]))
			return FALSE;

		return TRUE;
	}
}

// -------------------------------------------------------------
// recaptcha verify helper
// -------------------------------------------------------------

if (! function_exists('recaptcha_verify'))
{
    /**
     * @param string $response
     * @return bool
     */
    function recaptcha_verify($response = '')
    {
        string_not_empty($response) OR $response = ci()->input->post('g-recaptcha-response');
        if ($response)
        {
            // Create an instance of the service using your secret
            $recaptcha = new ReCaptcha(ci()->setting->recaptcha_secretkey);
            if (!function_exists('file_get_contents'))
            {
                // This makes use of fsockopen() instead.
                $recaptcha = new ReCaptcha(ci()->setting->recaptcha_secretkey, new \ReCaptcha\RequestMethod\SocketPost());
            }

            // Make the call to verify the response and also pass the user's IP address
            $resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])->verify($response, ip_address());
            if ($resp->isSuccess())
            {
                return TRUE;
            }
        }

        return FALSE;
    }
}

// -------------------------------------------------------------
// view helper
// -------------------------------------------------------------

if (! function_exists('_post'))
{
    /**
     * @param string $name
     * @param string $default
     * @param bool $_escape
     *
     * @return mixed|string
     */
    function _post($name, $default = '', $_escape = TRUE)
    {
        if(!isset($_POST[$name]))
            return $default;

        if($_escape == TRUE)
            return html_escape(ci()->input->post($name, TRUE));

        return ci()->input->post($name, TRUE);
    }
}

// -------------------------------------------------------------

if (! function_exists('_get'))
{
    /**
     * @param $name
     * @param string $default
     * @return mixed|string
     */
    function _get($name, $default = '', $_escape = TRUE)
    {
        if(!isset($_GET[$name]))
            return $default;

        if($_escape == TRUE)
            return html_escape(ci()->input->get($name, TRUE));

        return ci()->input->get($name, TRUE);
    }
}

// -------------------------------------------------------------
// other helper
// -------------------------------------------------------------

if (! function_exists('asset_js'))
{
    /**
     * @param $script
     * @param bool $script_min
     * @param string $group
     */
    function asset_js($script, $script_min = FALSE, $group = 'global')
    {
        echo get_asset_js($script, $script_min, $group);
    }
}

// -------------------------------------------------------------

if (! function_exists('get_asset_js'))
{
    /**
     * @param $script
     * @param bool $script_min
     * @param string $group
     * @return string
     */
    function get_asset_js($script, $script_min = FALSE, $group = 'global')
    {
        if(!class_exists('Asset'))
            return __return_empty_string();

        Asset::js($script, $script_min, $group);
        return Asset::render_js($group);
    }
}

// -------------------------------------------------------------

if (! function_exists('asset_css'))
{
    /**
     * @param $sheet
     * @param bool $sheet_min
     * @param string $group
     */
    function asset_css($sheet, $sheet_min = FALSE, $group = 'global')
    {
        echo get_asset_css($sheet, $sheet_min, $group);
    }
}

// -------------------------------------------------------------

if (! function_exists('get_asset_css'))
{
    /**
     * @param $sheet
     * @param bool $sheet_min
     * @param string $group
     * @return string
     */
    function get_asset_css($sheet, $sheet_min = FALSE, $group = 'global')
    {
        if(!class_exists('Asset'))
            return __return_empty_string();

        Asset::css($sheet, $sheet_min, $group);
        return Asset::render_css($group);
    }
}

// -------------------------------------------------------------

/**
 * @return bool
 */
function __return_true()
{
    return TRUE;
}

// -------------------------------------------------------------

/**
 * @return bool
 */
function __return_false()
{
    return FALSE;
}

// -------------------------------------------------------------

/**
 * @return int
 */
function __return_zero()
{
    return 0;
}

// -------------------------------------------------------------

/**
 * @return array
 */
function __return_empty_array()
{
    return [];
}

// -------------------------------------------------------------

/**
 * @return null
 */
function __return_null()
{
    return NULL;
}

// -------------------------------------------------------------

/**
 * @return string
 */
function __return_empty_string()
{
    return '';
}
