<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class MY_Upload
 * https://github.com/stvnthomas/CodeIgniter-Multi-Upload/blob/master/MY_Upload.php
 *
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
     * @var array thumbnail options
     */
    public $is_thumbnail = TRUE;
    public $thumbnails = [
        'upload_path' => '',
        'jpeg_quality' => 70,
        'max_width' => 0, // either specify width, or set to 0. Then width is automatically adjusted - keeping aspect ratio to a specified max_height.
        'max_height' => 0, // either specify height, or set to 0. Then height is automatically adjusted - keeping aspect ratio to a specified max_width.
        'thumb_width' => NULL,
        'thumb_height' => NULL,
        'thumb_type' => NULL,
    ];

    /**
     * @var bool watermark path
     */
    public $is_watermark = TRUE;
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
        $config['thumbnails'] = [
            'upload_path' => FCPATH . 'thumbs/',
            'jpeg_quality' => 70,
            'max_width' => 122,
            'max_height' => 91,
        ];

        $config['watermark_text'] = '@apx';
        $config['watermark_path'] = FALSE;
        $config['watermark_padding'] = 20;

        $config['file_ext_tolower'] = TRUE;
        $config['max_size'] = 4096; // 4MB

        parent::__construct($config);

        // load image lib
        $this->_CI->load->libraries(['Image_lib', 'Image_moo']);
    }

    /**
     * Initialize preferences
     * https://github.com/stvnthomas/CodeIgniter-Multi-Upload/blob/master/MY_Upload.php
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
            'is_thumbnail' => (bool) $this->is_thumbnail,
            'thumbnails' => [
                'thumb_path' => $this->thumbnails['upload_path'],
                'thumb_full_path' => $this->thumbnails['upload_path'] . $this->file_name,
                'thumb_width' => $this->thumbnails['thumb_width'],
                'thumb_height' => $this->thumbnails['thumb_height'],
                'thumb_type' => $this->thumbnails['thumb_type'],
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
     * @param $path
     * @return MY_Upload
     */
    public function set_thumbnail_path($path)
    {
        // Make sure it has a trailing slash
        $this->thumbnails['upload_path'] = rtrim($path, '/') . '/';
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function set_thumbnail_properties($path = '')
    {
        if ($this->is_image() AND function_exists('getimagesize'))
        {
            if (FALSE !== ($D = @getimagesize($path)))
            {
                $types = [1 => 'gif', 2 => 'jpeg', 3 => 'png'];

                $this->thumbnails['thumb_width'] = $D[0];
                $this->thumbnails['thumb_height'] = $D[1];
                $this->thumbnails['thumb_type']	= isset($types[$D[2]]) ? $types[$D[2]] : 'unknown';
            }
        }

        return $this;
    }

    /**
     * @param bool $_is_thumbnail
     * @return MY_Upload
     */
    public function is_thumbnail($_is_thumbnail = TRUE)
    {
        is_bool($_is_thumbnail) AND $this->is_thumbnail = $_is_thumbnail;
        return $this;
    }

    /**
     * @param bool $_is_watermark
     * @return MY_Upload
     */
    public function is_watermark($_is_watermark = TRUE)
    {
        is_bool($_is_watermark) AND $this->is_watermark = $_is_watermark;
        return $this;
    }

    /**
     * Set Multiple Upload Data
     *
     * @access    protected
     * @return MY_Upload
     */
    protected function set_multi_data()
    {
        $this->_multi_upload_data[] = $this->data(NULL);
        return $this;
    }

    /**
     * Multi Data Array
     *
     * @param null $index
     * @return array
     */
    public function multi_data($index = NULL)
    {
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
     * https://github.com/stvnthomas/CodeIgniter-Multi-Upload/blob/master/MY_Upload.php
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
     * <input type="file" name="userfile" />
     *
     * @param string $field
     * @param string $wmv T M B
     * @param string $wmh L C R
     * @return    bool
     */
    public function do_upload($field = 'userfile', $wmv = 'M', $wmh = 'C')
    {
        if (parent::do_upload($field))
        {
            // watermark
            if ($this->is_watermark)
            {
                $this->_watermark($this->upload_path . $this->file_name, $wmv, $wmh);
            }

            // thumbnail
            if ($this->is_thumbnail)
            {
                $this->_thumbnails($this->thumbnails['upload_path'] . $this->file_name, TRUE);
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Perform the multi-files upload
     * https://github.com/stvnthomas/CodeIgniter-Multi-Upload/blob/master/MY_Upload.php
     *
     * <input type="file" name="userfiles[]" multiple />
     *
     * @param string $field
     * @param string $wmv T M B
     * @param string $wmh L C R
     * @return bool
     */
    public function do_multi_upload($field = 'userfiles', $wmv = 'M', $wmh = 'C')
    {
        // Clear multi_upload_data.
        $this->_multi_upload_data = [];

        // Is $_FILES[$field] set? If not, no reason to continue.
        if (!isset($_FILES[$field]))
        {
            $this->set_error('upload_no_file_selected', 'debug');
            return FALSE;
        }

        // Is this really a multi upload?
        if (!is_array($_FILES[$field]["name"]))
        {
            // Fallback to do_upload method.
            return $this->do_upload($field, $wmv, $wmh);
        }

        // Is the upload path valid?
        if (!$this->validate_upload_path())
        {
            // errors will already be set by validate_upload_path() so just return FALSE
            return FALSE;
        }

        // Every file will have a separate entry in each of the $_FILES associative array elements (name, type, etc).
        // Loop through $_FILES[$field]["name"] as representative of total number of files. Use count as key in
        // corresponding elements of the $_FILES[$field] elements.
        foreach ($_FILES[$field]["name"] as $i => $v)
        {
            // Was the file able to be uploaded? If not, determine the reason why.
            if(!is_uploaded_file($_FILES[$field]["tmp_name"][$i]))
            {
                //Determine error number.
                $error = (!isset($_FILES[$field]["error"][$i])) ? 4 : $_FILES[$field]["error"][$i];

                switch ($error)
                {
                    case UPLOAD_ERR_INI_SIZE:
                        $this->set_error('upload_file_exceeds_limit', 'info');
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->set_error('upload_file_exceeds_form_limit', 'info');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->set_error('upload_file_partial', 'debug');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->set_error('upload_no_file_selected', 'debug');
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->set_error('upload_no_temp_directory', 'error');
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->set_error('upload_unable_to_write_file', 'error');
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->set_error('upload_stopped_by_extension', 'debug');
                        break;
                    default:
                        $this->set_error('upload_no_file_selected', 'debug');
                        break;
                }

                // Return failed upload.
                return FALSE;
            }

            // Set current file data as class variables.
            $this->file_temp = $_FILES[$field]["tmp_name"][$i];
            $this->file_size = $_FILES[$field]["size"][$i];

            // Skip MIME type detection?
            if ($this->detect_mime !== FALSE)
            {
                $this->_file_mime_type($_FILES[$field], $i);
            }

            $this->file_type = preg_replace('/^(.+?);.*$/', '\\1', $this->file_type);
            $this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
            $this->file_name = $this->_prep_filename($_FILES[$field]["name"][$i]);
            $this->file_ext	 = $this->get_extension($this->file_name);
            $this->client_name = $this->file_name;

            // Is the file type allowed to be uploaded?
            if (!$this->is_allowed_filetype())
            {
                $this->set_error('upload_invalid_filetype', 'debug');
                return FALSE;
            }

            // If we're overriding, let's now make sure the new name and type is allowed.
            // Check if a filename was supplied for the current file. Otherwise, use it's given name.
            if (!empty($this->_multi_file_name_override[$i]))
            {
                $this->file_name = $this->_prep_filename($this->_multi_file_name_override[$i]);

                // If no extension was provided in the file_name config item, use the uploaded one.
                if (strpos($this->_multi_file_name_override[$i], ".") === FALSE)
                {
                    $this->file_name .= $this->file_ext;
                }
                else
                {
                    // An extension was provided, let's have it!
                    $this->file_ext = $this->get_extension($this->_multi_file_name_override[$i]);
                }

                if (!$this->is_allowed_filetype(TRUE))
                {
                    $this->set_error('upload_invalid_filetype', 'debug');
                    return FALSE;
                }
            }

            // Convert the file size to kilobytes.
            if ($this->file_size > 0)
            {
                $this->file_size = round($this->file_size / 1024, 2);
            }

            // Is the file size within the allowed maximum?
            if ( ! $this->is_allowed_filesize())
            {
                $this->set_error('upload_invalid_filesize', 'info');
                return FALSE;
            }

            // Are the image dimensions within the allowed size?
            // Note: This can fail if the server has an open_basdir restriction.
            if (!$this->is_allowed_dimensions())
            {
                $this->set_error('upload_invalid_dimensions', 'info');
                return FALSE;
            }

            // Sanitize the file name for security.
            $this->file_name = $this->_CI->security->sanitize_filename($this->file_name);

            // Truncate the file name if it's too long
            if ($this->max_filename > 0)
            {
                $this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
            }

            // Remove white spaces in the name
            if ($this->remove_spaces === TRUE)
            {
                $this->file_name = preg_replace('/\s+/', '_', $this->file_name);
            }

            if ($this->file_ext_tolower && ($ext_length = strlen($this->file_ext)))
            {
                // file_ext was previously lower-cased by a get_extension() call
                $this->file_name = substr($this->file_name, 0, -$ext_length).$this->file_ext;
            }

            /*
             * Validate the file name
             * This function appends an number onto the end of
             * the file if one with the same name already exists.
             * If it returns false there was a problem.
             */
            $this->orig_name = $this->file_name;
            if (FALSE === ($this->file_name = $this->set_filename($this->upload_path, $this->file_name)))
            {
                return FALSE;
            }

            /*
             * Run the file through the XSS hacking filter
             * This helps prevent malicious code from being
             * embedded within a file. Scripts can easily
             * be disguised as images or other file types.
             */
            if ($this->xss_clean && $this->do_xss_clean() === FALSE)
            {
                $this->set_error('upload_unable_to_write_file', 'error');
                return FALSE;
            }

            /*
             * Move the file to the final destination
             * To deal with different server configurations
             * we'll attempt to use copy() first. If that fails
             * we'll use move_uploaded_file(). One of the two should
             * reliably work in most environments
             */
            if (!@copy($this->file_temp, $this->upload_path . $this->file_name))
            {
                if (!@move_uploaded_file($this->file_temp, $this->upload_path . $this->file_name))
                {
                    $this->set_error('upload_destination_error', 'error');
                    return FALSE;
                }
            }

            // watermark
            if ($this->is_watermark)
            {
                $this->_watermark($this->upload_path . $this->file_name, $wmv, $wmh);
            }

            // thumbnail
            if ($this->is_thumbnail)
            {
                $this->_thumbnails($this->thumbnails['upload_path'] . $this->file_name, TRUE);
            }

            /* Set the finalized image dimensions
             * This sets the image width/height (assuming the
             * file was an image).  We use this information
             * in the "data" function.
             */
            $this->set_image_properties($this->upload_path . $this->file_name);

            // Set current file data to multi_file_upload_data.
            $this->set_multi_data();
        }

        // Return all file upload data.
        return TRUE;
    }

    /**
     * https://www.codeigniter.com/user_guide/libraries/image_lib.html
     *
     * @param $full_path
     * @param string $v T M B
     * @param string $h L C R
     */
    private function _watermark($full_path, $v = 'M', $h = 'C')
    {
        $_config = [
            'source_image' => $full_path,
            'wm_padding' => $this->watermark_padding,
            'wm_vrt_alignment' => $v,
            'wm_hor_alignment' => $h,
            'wm_font_size' => 19,
            'wm_opacity' => 50,
        ];

        if (string_not_empty($this->watermark_text))
        {
            $_config['wm_type'] = 'text';
            $_config['wm_text'] = $this->watermark_text;
        }
        else if (string_not_empty($this->watermark_path))
        {
            $_config['wm_type'] = 'overlay';
            $_config['wm_overlay_path'] = $this->watermark_path;
        }

        $this->_CI->image_lib->initialize($_config);
        if (!$this->_CI->image_lib->watermark())
        {
            $this->set_error($this->_CI->image_lib->display_errors(), 'debug');
        }
    }

    /**
     * @param $thumb_path
     * @param bool $crop
     */
    private function _thumbnails($thumb_path, $crop = FALSE)
    {
        // Use Image_moo library
        $this->_CI->image_moo->allow_scale_up(TRUE);
        $this->_CI->image_moo->set_jpeg_quality($this->thumbnails['jpeg_quality']);

        $moo = $this->_CI->image_moo->load($this->upload_path . $this->file_name);
        if ($crop == TRUE)
        {
            if (!$moo->resize_crop($this->thumbnails['max_width'], $this->thumbnails['max_height'])->save($thumb_path, FALSE))
            {
                $this->set_error($moo->display_errors(), 'debug');
            }
        }
        else
        {
            if (!$moo->resize($this->thumbnails['max_width'], $this->thumbnails['max_height'])->save($thumb_path, FALSE))
            {
                $this->set_error($moo->display_errors(), 'debug');
            }
        }

        $this->set_thumbnail_properties($thumb_path);
    }
}
