<?php defined('BASEPATH') OR exit('No direct script access allowed.');

/**
 * This overrides Codeigniter's helpers/url_helper.php file.
 */

// ------------------------------------------------------------------------

if (! function_exists('site_uri'))
{
	/**
	 * Site URI
	 *
	 * Create a local URI based on your basepath.
	 *
	 * @param    string $uri
	 *
	 * @return    string
	 */
	function site_uri($uri = '')
	{
		return get_instance()->config->site_uri($uri);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('url_title'))
{
	/**
	 * Takes a string as input and creates a
	 * human-friendly URL string with a "separator" string
	 * as the word separator.
	 *
	 * @param string $str The string
	 * @param string $separator The separator, dash or underscore.
	 * @param boolean $lowercase Whether it should be converted to lowercase.
	 *
	 * @return string The URL slug
	 *
	 * @override
	 */
	function url_title($str, $separator = '-', $lowercase = TRUE)
	{
		if ($separator === 'dash')
		{
			$separator = '-';
		}
		elseif ($separator === 'underscore')
		{
			$separator = '_';
		}
		elseif (empty($separator))
		{
			$separator = ' ';
		}

		$q_separator = preg_quote($separator, '#');
		$trans = [
			'&.+?;' => '',
			'[^\w\d _-]' => '',
			'\s+' => $separator,
			'(' . $q_separator . ')+' => $separator,
			$separator . '$' => '',
			'^' . $separator => '',
		];

		$str = convert_accented_characters($str);
		$str = strip_tags($str);
		foreach ($trans as $key => $val)
		{
			$str = preg_replace('#' . $key . '#i', $val, $str);
		}

		if ($lowercase === TRUE)
		{
			if (function_exists('mb_convert_case'))
			{
				$str = mb_convert_case($str, MB_CASE_LOWER, "UTF-8");
			}
			else
			{
				$str = strtolower($str);
			}
		}

		$CI = &get_instance();
		$str = preg_replace('#[^' . $CI->config->item('permitted_uri_chars') . ']#i', '', $str);

		return trim(stripslashes($str));
	}
}

// ------------------------------------------------------------------------

if (! function_exists('redirect'))
{
	/**
	 * Header Redirect
	 *
	 * Header redirect in two flavors
	 * For very fine grained control over headers, you could use the Output
	 * Library's set_header() function.
	 *
	 * @param    string $uri URL
	 * @param    string $method Redirect method
	 *            'auto', 'location' or 'refresh'
	 * @param    int $code HTTP Response status code
	 *
	 * @return    void
	 *
	 * @override
	 */
	function redirect($uri = '', $method = 'auto', $code = NULL)
	{
		if (! preg_match('#^(\w+:)?//#i', $uri))
		{
			$uri = site_url($uri);
		}

		if (! headers_sent())
		{
			// IIS environment likely? Use 'refresh' for better compatibility
			if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== FALSE)
			{
				$method = 'refresh';
			}
			elseif ($method !== 'refresh' && (empty($code) OR ! is_numeric($code)))
			{
				if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1')
				{
					$code = ($_SERVER['REQUEST_METHOD'] !== 'GET')
						? 303    // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
						: 307;
				}
				else
				{
					$code = 302;
				}
			}

			switch ($method)
			{
				case 'refresh':
					header('Refresh:0;url=' . $uri);
					break;
				default:
					header('Location: ' . $uri, TRUE, $code);
					break;
			}
			exit;
		}
		else
		{
			echo '<script type="text/javascript">';
			echo 'window.location.href="' . $uri . '";';
			echo '</script>';
			echo '<noscript>';
			echo '<meta http-equiv="refresh" content="0;url=' . $uri . '" />';
			echo '</noscript>';
		}
	}
}

// ------------------------------------------------------------------------

if (! function_exists('shorten_url'))
{

	/**
	 * Shorten URL
	 *
	 * Takes a long url and uses the TinyURL API to return a shortened version.
	 * Supports Cyrillic characters.
	 *
	 * @param  string $url long url
	 *
	 * @return string Short url
	 */
	function shorten_url($url = '')
	{
		if (! $url)
		{
			$url = site_url(uri_string());
		} // If no a protocol in URL, assume its a CI link
		elseif (! preg_match('!^\w+://! i', $url))
		{
			$url = site_url($url);
		}

		return url_contents('http://tinyurl.com/api-create.php?url=' . $url);
	}
}

// -------------------------------------------------------------

if (! function_exists('ip_server'))
{
	/**
	 * @return bool
	 */
	function ip_server()
	{
		$CI = &get_instance();

		$ip = $_SERVER['SERVER_ADDR'];
		if (! $CI->input->valid_ip($ip))
		{
			// Windows IIS
			$ip = $_SERVER['LOCAL_ADDR'];
		}
		if (! $CI->input->valid_ip($ip))
		{
			return FALSE;
		}

		return $ip;
	}
}

// ------------------------------------------------------------------------

if (! function_exists('ip_address'))
{

	/**
	 * @return mixed
	 */
	function ip_address()
	{
		$CI = &get_instance();

		return $CI->input->ip_address();
	}
}

// ------------------------------------------------------------------------

if (! function_exists('server_host'))
{
	/**
	 * This method was copied from URL
	 *
	 * (https://exceptionshub.com/php-_serverhttp_host-vs-_serverserver_name-am-i-understanding-the-man-pages-correctly.html)
	 *
	 * @param bool $remove_port
	 *
	 * @return string
	 */
	function server_host($remove_port = TRUE)
	{
		$possibleHostSources = ['HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR'];
		$sourceTransformations = [
			"HTTP_X_FORWARDED_HOST" => function ($value) {
				$elements = explode(',', $value);

				return trim(end($elements));
			}
		];

		$host = '';
		foreach ($possibleHostSources as $source)
		{
			if (! empty($host))
			{
				break;
			}

			if (empty($_SERVER[$source]))
			{
				continue;
			}

			$host = $_SERVER[$source];
			if (array_key_exists($source, $sourceTransformations))
			{
				$host = $sourceTransformations[$source]($host);
			}
		}

		// Remove port number from host
		if ($remove_port == TRUE)
		{
			$host = preg_replace('/:\d+$/', '', $host);
		}

		return trim($host);
	}
}

// ------------------------------------------------------------------------

if (! function_exists('gravatar'))
{
	/**
	 * Gravatar func
	 *
	 * @param string $email The Email address used to generate the gravatar
	 * @param int $size The size of the gravatar in pixels. A size of 50 would return a gravatar with a width and height of 50px.
	 * @param string $rating The rating of the gravatar. Possible values are g, pg, r or x
	 * @param boolean $url_only Set this to true if you want the plugin to only return the gravatar URL instead of the HTML.
	 * @param boolean $default Url to image used instead af Gravatars default when email has no gravatar
	 *
	 * @return string The gravatar's URL or the img HTML tag ready to be used.
	 */
	function gravatar($email = '', $size = 50, $rating = 'g', $url_only = FALSE, $default = FALSE)
	{
		$base_url = (is_https() ? 'https://secure.gravatar.com' : 'http://www.gravatar.com') . '/avatar/';
		$email = empty($email) ? '3b3be63a4c2a439b013787725dfce802' : md5(strtolower(trim($email)));
		$size = '?s=' . $size;
		$rating = '&amp;r=' . $rating;
		$default = ! $default ? '' : '&amp;d=' . urlencode($default);

		$gravatar_url = $base_url . $email . $size . $rating . $default;
		// URL only or the entire block of HTML ?
		if ($url_only == TRUE)
		{
			return $gravatar_url;
		}

		return '<img src="' . $gravatar_url . '" alt="Gravatar" class="gravatar" />';
	}
}

// -------------------------------------------------------------

if (! function_exists('url_tokens'))
{
	/**
	 * Get full url ignore query string
	 * value http://vietnhan.com/tim-kiem/?s=abc&p=2
	 * return http://vietnhan.com/tim-kiem/
	 *
	 * @param null $url
	 *
	 * @return string
	 */
	function url_tokens($url = NULL)
	{
		if (empty($url))
		{
			return NULL;
		}

		$CI = &get_instance();
		$CI->load->library('form_validation');

		if (! $CI->form_validation->valid_url($url))
		{
			return $url;
		}

		$url = filter_var($url, FILTER_SANITIZE_URL);
		return strtok($url, '?');
	}
}

// -------------------------------------------------------------

if (! function_exists('valid_url'))
{
    /**
     * @param $url
     *
     * @return mixed
     */
    function valid_url($url)
    {
        $CI = &get_instance();
        $CI->load->library('form_validation');

        return $CI->form_validation->valid_url($url);
    }
}

// -------------------------------------------------------------

if (! function_exists('check_https'))
{
    /**
     * @param null $url
     * @return bool
     */
    function check_https($url = NULL)
    {
        if(is_empty($url)) return is_https();
        if(substr($url, 0, 5) === 'https') return TRUE;

        return FALSE;
    }
}

// -------------------------------------------------------------

if (! function_exists('url_contents'))
{
    /**
     * url_contents
     *
     * @param $url
     *
     * @param bool $html_escape
     * @return false|string|string[]|null
     */
	function url_contents($url, $html_escape = FALSE)
	{
	    $data = NULL;

	    // url or filename
	    if(valid_url($url))
        {
            $url = filter_var(preg_replace('/\s+/', '', $url), FILTER_SANITIZE_URL);

            // Curl if exists
            if (function_exists('curl_version'))
            {
                $handle = curl_init();
                $options = [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => TRUE,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/x-www-form-urlencoded',
                    ],
                    CURLINFO_HEADER_OUT => FALSE,
                    CURLOPT_HEADER => FALSE,
                    CURLOPT_RETURNTRANSFER => TRUE,
                    CURLOPT_SSL_VERIFYHOST => TRUE,
                    CURLOPT_SSL_VERIFYPEER => FALSE,
                ];

                curl_setopt_array($handle, $options);
                $data = curl_exec($handle);
                curl_close($handle);
            }
            else if (function_exists('file_get_contents'))
            {
                $http = (check_https($url)) ? 'https' : 'http';
                $options = [
                    $http => [
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        // Force the peer to validate (not needed in 5.6.0+, but still works)
                        'verify_peer' => TRUE,
                    ],
                ];
                $context = stream_context_create($options);
                $data = file_get_contents($url, FALSE, $context);
            }
        }
	    else if (function_exists('file_get_contents'))
        {
            $data = file_get_contents($url, FALSE, NULL);
        }

	    // return NULL if empty
        if (is_empty($data))
        {
            return NULL;
        }

        //$string_encoding = mb_detect_encoding($data, "UTF-8, ISO-8859-1, ISO-8859-15", TRUE);
        //$string_utf8 = mb_convert_encoding($data, "UTF-8", $string_encoding);
        if($html_escape == TRUE)
        {
            return htmlspecialchars($data, ENT_COMPAT, "UTF-8", TRUE);
        }

        return $data;
	}
}
