<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Upload
 * https://github.com/stvnthomas/CodeIgniter-Multi-Upload/blob/master/MY_Upload.php
 *
 * @property CI_Image_lib $image_lib
 */
class MY_Upload extends CI_Upload
{
    /**
     * Allowed file types
     *
     * @var	string
     */
    public $allowed_types = '*';

    /**
     * @var string upload path
     */
    public $upload_path = '';
    public $upload_url = '';

    /**
     * @var array thumbnail options
     */
    public $is_thumbnail = FALSE;
    public $thumbnails = [
        'upload_path' => '',
        'upload_url' => '',
        'crop' => FALSE,
        'jpeg_quality' => 75,
        'max_width' => 0, // either specify width, or set to 0. Then width is automatically adjusted - keeping aspect ratio to a specified max_height.
        'max_height' => 0, // either specify height, or set to 0. Then height is automatically adjusted - keeping aspect ratio to a specified max_width.
        'thumb_size' => NULL,
        'thumb_width' => NULL,
        'thumb_height' => NULL,
    ];

    /**
     * @var bool watermark path
     */
    public $watermark_text = '';
    public $watermark_path = FALSE;
    public $watermark_padding = 0;

    /**
     * Force filename extension to lowercase
     *
     * @var	string
     */
    public $file_ext_tolower = TRUE;

    /**
     * Maximum file size
     *
     * @var	int
     */
    public $max_size = 2048;

    /**
     * @var array for multi-data
     */
    protected $_multi_upload_data = [];
    protected $_multi_file_name_override = '';

    /**
     * MY_Upload constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $config['upload_path'] = FCPATH . 'uploads/';
        $config['upload_url'] = site_url('/') . 'uploads/';
        $config['thumbnails'] = [
            'upload_path' => FCPATH . 'thumbs/',
            'upload_url' => site_url('/') . 'thumbs/',
            'crop' => TRUE,
            'jpeg_quality' => 75,
            'max_width' => 122,
            'max_height' => 91,
            'thumb_size' => NULL,
            'thumb_width' => NULL,
            'thumb_height' => NULL,
        ];
        $config['watermark_text'] = '@apx';
        $config['watermark_path'] = FALSE;
        $config['watermark_padding'] = 20;

        $config['file_ext_tolower'] = TRUE;
        $config['max_size'] = 4096; // 4MB

        parent::__construct($config);

        // load image lib
        $this->_CI->load->library('Image_lib');
    }

    /**
     * Initialize preferences
     *
     * @param	array	$config
     * @param	bool	$reset
     * @return	CI_Upload
     */
    public function initialize(array $config = [], $reset = TRUE)
    {
        parent::initialize($config, $reset);

        // Multiple file upload.
        if(is_array($this->file_name))
        {
            $this->_file_name_override = '';

            // Set multiple file name override.
            $this->_multi_file_name_override = $this->file_name;
        }

        return $this;
    }

    /**
     * Finalized Data Array
     *
     * Returns an associative array containing all of the information
     * related to the upload, allowing the developer easy access in one array.
     *
     * @param	string	$index
     * @return	mixed
     */
    public function data($index = NULL)
    {
        $_data = [
            'file_url' => $this->upload_url,
            'is_thumbnail' => (bool) $this->is_thumbnail,
            'thumbnails' => [
                'file_path' => $this->thumbnails['upload_path'],
                'file_url' => $this->thumbnails['upload_url'],
                'thumb_width' => (int) $this->thumbnails['thumb_width'],
                'thumb_height' => (int) $this->thumbnails['thumb_height'],
                'thumb_size' => $this->thumbnails['thumb_size'],
            ],
        ];

        $_data = array_merge($_data, parent::data(NULL));
        if (!empty($index))
        {
            return isset($_data[$index]) ? $_data[$index] : NULL;
        }

        return $_data;
    }

    /**
     * Multi Data Array
     *
     * @param null $index
     * @return array
     */
    public function multi_data($index = NULL)
    {
        $this->_multi_upload_data[] = $this->data(NULL);
        if (!empty($index))
        {
            $_data = [];
            foreach ($this->_multi_upload_data as $_val)
            {
                $_data[] = isset($_val[$index]) ? $_val[$index] : NULL;
            }

            return $_data;
        }

        return $this->_multi_upload_data;
    }

    /**
     * File MIME type
     *
     * Detects the (actual) MIME type of the uploaded file, if possible.
     * The input array is expected to be $_FILES[$field]
     *
     * @param array $file
     * @param int $count
     * @return    void
     */
    protected function _file_mime_type($file, $count = 0)
    {
        // Mutliple file?
        if(is_array($file["name"]))
        {
            $tmp_name = $file["tmp_name"][$count];
            $type = $file["type"][$count];
        }
        else
        {
            $tmp_name = $file["tmp_name"];
            $type = $file["type"];
        }

        // We'll need this to validate the MIME info string (e.g. text/plain; charset=us-ascii)
        $regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';

        /**
         * Fileinfo extension - most reliable method
         *
         * Apparently XAMPP, CentOS, cPanel and who knows what
         * other PHP distribution channels EXPLICITLY DISABLE
         * ext/fileinfo, which is otherwise enabled by default
         * since PHP 5.3 ...
         */
        if (function_exists('finfo_file'))
        {
            $finfo = @finfo_open(FILEINFO_MIME);
            if (is_resource($finfo)) // It is possible that a FALSE value is returned, if there is no magic MIME database file found on the system
            {
                $mime = @finfo_file($finfo, $tmp_name);
                finfo_close($finfo);

                /* According to the comments section of the PHP manual page,
                 * it is possible that this function returns an empty string
                 * for some files (e.g. if they don't exist in the magic MIME database)
                 */
                if (is_string($mime) && preg_match($regexp, $mime, $matches))
                {
                    $this->file_type = $matches[1];
                    return;
                }
            }
        }

        /* This is an ugly hack, but UNIX-type systems provide a "native" way to detect the file type,
         * which is still more secure than depending on the value of $_FILES[$field]['type'], and as it
         * was reported in issue #750 (https://github.com/EllisLab/CodeIgniter/issues/750) - it's better
         * than mime_content_type() as well, hence the attempts to try calling the command line with
         * three different functions.
         *
         * Notes:
         *	- the DIRECTORY_SEPARATOR comparison ensures that we're not on a Windows system
         *	- many system admins would disable the exec(), shell_exec(), popen() and similar functions
         *	  due to security concerns, hence the function_usable() checks
         */
        if (DIRECTORY_SEPARATOR !== '\\')
        {
            $cmd = function_exists('escapeshellarg')
                ? 'file --brief --mime '.escapeshellarg($tmp_name).' 2>&1'
                : 'file --brief --mime '.$tmp_name.' 2>&1';

            if (function_usable('exec'))
            {
                /* This might look confusing, as $mime is being populated with all of the output when set in the second parameter.
                 * However, we only need the last line, which is the actual return value of exec(), and as such - it overwrites
                 * anything that could already be set for $mime previously. This effectively makes the second parameter a dummy
                 * value, which is only put to allow us to get the return status code.
                 */
                $mime = @exec($cmd, $mime, $return_status);
                if ($return_status === 0 && is_string($mime) && preg_match($regexp, $mime, $matches))
                {
                    $this->file_type = $matches[1];
                    return;
                }
            }

            if ( ! ini_get('safe_mode') && function_usable('shell_exec'))
            {
                $mime = @shell_exec($cmd);
                if (strlen($mime) > 0)
                {
                    $mime = explode("\n", trim($mime));
                    if (preg_match($regexp, $mime[(count($mime) - 1)], $matches))
                    {
                        $this->file_type = $matches[1];
                        return;
                    }
                }
            }

            if (function_usable('popen'))
            {
                $proc = @popen($cmd, 'r');
                if (is_resource($proc))
                {
                    $mime = @fread($proc, 512);
                    @pclose($proc);
                    if ($mime !== FALSE)
                    {
                        $mime = explode("\n", trim($mime));
                        if (preg_match($regexp, $mime[(count($mime) - 1)], $matches))
                        {
                            $this->file_type = $matches[1];
                            return;
                        }
                    }
                }
            }
        }

        // Fall back to mime_content_type(), if available (still better than $_FILES[$field]['type'])
        if (function_exists('mime_content_type'))
        {
            $this->file_type = @mime_content_type($tmp_name);
            if (strlen($this->file_type) > 0) // It's possible that mime_content_type() returns FALSE or an empty string
            {
                return;
            }
        }

        $this->file_type = $type;
    }

    /**
     * Perform the file upload
     *
     * @param	string	$field
     * @return	bool
     */
    public function do_upload($field = 'userfile')
    {
        if(parent::do_upload($field))
        {
            // thumbs + watermark
            if(string_not_empty($this->watermark_text))
            {
                $_full_path = $this->upload_path . $this->file_name;

                //
            }
        }
    }
}
