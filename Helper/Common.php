<?php

if (!function_exists('debug')) {
    function debug($data, $exit = false, $options = false)
    {
        echo '<pre>';
        switch ($options) {
            case 'dump':
                var_dump($data);

                break;
            default:
                print_r($data);

                break;
        }
        echo '</pre>';

        if ($exit) {
            exit;
        }
    }
}

if (!function_exists('url_title')) {
    /*
    | function url_title()
    | @input : string
    | @output : return string
    */
    function url_title($title)
    {
        $replace = '-';

        $pattern = [
            '&\#\d+?;' => '',
            '&\S+?;' => '',
            '\s+' => $replace,
            '[^a-z0-9\-\._]' => '',
            $replace.'+' => $replace,
            $replace.'$' => $replace,
            '^'.$replace => $replace,
            '\.+$' => '',
        ];

        $title = strip_tags($title);

        foreach ($pattern as $key => $val) {
            $title = preg_replace('#'.$key.'#i', $val, $title);
        }

        $title = strtolower($title);

        return trim(stripslashes(str_replace([',', '.'], ['', ''], $title)));
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $status = 302)
    {
        header('Location: '.$url, true, $status);
        die();
    }
}

if (!function_exists('get_config')) {
    function get_config($data = '')
    {
        $new_config = [];

        if (!$data) {
            $config = require _CONFIG.'Config.php';

            foreach ($config as $key => $val) {
                if (is_array($val)) {
                    $new_config[$key] = get_config($val);
                } else {
                    $new_config[$key] = is_string($val) ? $val : (object) $val;
                }
            }
        } else {
            foreach ($data as $key => $val) {
                $new_config[$key] = is_string($val) ? $val : (object) $val;
            }
        }

        //debug($new_config);
        return (object) $new_config;
    }
}

if (!function_exists('check_session')) {
    function check_session($sess)
    {
        if (isset($_SESSION[$sess]) && !empty($_SESSION[$sess])) {
            return $_SESSION;
        }

        return false;
    }
}

if (!function_exists('current_path')) {
    function current_path()
    {
        return $_SERVER['REQUEST_URI'];
    }
}

if (!function_exists('uri_segment')) {
    function uri_segment($segment)
    {
        $config = get_config();

        $uri_path = (!empty($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS']) ? 'https' : 'http'
                    .'://'.$_SERVER['SERVER_NAME'].parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace($config->site->base_url, '', $uri_path);
        $uri_segments = explode('/', $uri);

        return isset($uri_segments[$segment]) ? $uri_segments[$segment] : false;
    }
}

if (!function_exists('input_get')) {
    function input_get($name = '')
    {
        if ($name) {
            return isset($_GET[$name]) ? $_GET[$name] : '';
        }

        return $_GET;
    }
}

if (!function_exists('input_post')) {
    function input_post($name = '')
    {
        if ($name) {
            return isset($_POST[$name]) ? $_POST[$name] : '';
        }

        return $_POST;
    }
}

if (!function_exists('set_flashdata')) {
    function set_session($key, $value)
    {
        if (!empty($key) && !empty($value)) {
            $_SESSION[$key] = $value;
        } else {
            return false;
        }
    }
}

if (!function_exists('get_flashdata')) {
    function get_session($key)
    {
        if (!empty($key)) {
            if (isset($_SESSION[$key])) {
                $data = $_SESSION[$key];
                unset($_SESSION[$key]);

                return $data;
            }

            return '';
        }

        return '';
    }
}

if (!function_exists('send_email')) {
    function send_email($from, $to, $subject, $message = '')
    {
        $encoding = 'utf-8';

        // Preferences for Subject field
        $subject_preferences = [
            'input-charset' => $encoding,
            'output-charset' => $encoding,
            'line-length' => 76,
            'line-break-chars' => "\r\n",
        ];

        if (is_array($from)) {
            $from_name = $from['name'];
            $from_email = $from['email'];
        } else {
            return false;
        }

        // Message
        $email_message = '
		<html>
		<body>
		  '.$message.'
		</body>
		</html>
		';

        // Mail header
        $header = 'Content-type: text/html; charset='.$encoding." \r\n";
        $header .= 'From: '.$from_name.' <'.$from_email."> \r\n";
        $header .= "MIME-Version: 1.0 \r\n";
        $header .= "Content-Transfer-Encoding: 8bit \r\n";
        $header .= 'Date: '.date('r (T)')." \r\n";
        //$header .= iconv_mime_encode("Subject", $subject, $subject_preferences);

        // Send mail
        return mail($to, $subject, $email_message, $header);
    }
}

if (!function_exists('base_random_string')) {
    function base_random_string($length = 6)
    {
        $seed = str_split('bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ'.date('dHs'));
        shuffle($seed);
        $rand = '';
        foreach (array_rand($seed, $length) as $k) {
            $rand .= $seed[$k];
        }

        return $rand;
    }
}

if (!function_exists('is_really_writable')) {
    /**
     * Tests for file writability.
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @see	https://bugs.php.net/bug.php?id=54709
     *
     * @param	string
     * @param mixed $file
     *
     * @return bool
     */
    function is_really_writable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') or !ini_get('safe_mode'))) {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file)) {
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            if (false === ($fp = @fopen($file, 'ab'))) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);

            return true;
        }
        if (!is_file($file) or false === ($fp = @fopen($file, 'ab'))) {
            return false;
        }

        fclose($fp);

        return true;
    }
}

if (!function_exists('get_mimes')) {
    /**
     * Returns the MIME types array from config/mimes.php.
     *
     * @return array
     */
    function &get_mimes()
    {
        static $_mimes;

        if (empty($_mimes)) {
            if (file_exists(_CONFIG.'mimes.php')) {
                $_mimes = include _CONFIG.'mimes.php';
            } else {
                $_mimes = [];
            }
        }

        return $_mimes;
    }
}

if (!function_exists('is_php')) {
    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value.
     *
     * @param	string
     * @param mixed $version
     *
     * @return bool TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if (!isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}

if (!function_exists('make_url')) {
    function make_url($url)
    {
        $config = get_config();

        return $config->site->base_url.'/'.$url;
    }
}

if (!function_exists('make_image_url')) {
    function make_image_url($image, $size)
    {
        $config = get_config();

        return $config->site->base_url.'/'.str_replace('real/', $size.'/', $image);
    }
}

if (!function_exists('make_yt_image_url')) {
    function make_yt_image_url($embed, $type = 0)
    {
        preg_match('d\/(\w+)\?rel=\d+"', $embed, $code);
        if (!empty($code)) {
            return "https://img.youtube.com/vi/${code}/${type}.jpg";
        }

        return false;
    }
}

if (!function_exists('show_error')) {
    function show_error($type = 404, $return = false)
    {
        $template = require_once ROOT._VIEW.'error'.DS.$type.'.html';

        $error = error_get_last();
        $type = $error['type'];
        $message = $error['message'];
        if (64 == $type && !empty($message)) {
            echo '
				<strong>
				  <font color="red">
				  Fatal error captured:
				  </font>
				</strong>
			';
            echo '<pre>';
            print_r($error);
            echo '</pre>';
        } else {
            if ($return) {
                return $template;
            }

            exit($template);
        }
    }
}

if (!function_exists('function_usable')) {
    /**
     * Function usable.
     *
     * Executes a function_exists() check, and if the Suhosin PHP
     * extension is loaded - checks whether the function that is
     * checked might be disabled in there as well.
     *
     * This is useful as function_exists() will return FALSE for
     * functions disabled via the *disable_functions* php.ini
     * setting, but not for *suhosin.executor.func.blacklist* and
     * *suhosin.executor.disable_eval*. These settings will just
     * terminate script execution if a disabled function is executed.
     *
     * The above described behavior turned out to be a bug in Suhosin,
     * but even though a fix was committed for 0.9.34 on 2012-02-12,
     * that version is yet to be released. This function will therefore
     * be just temporary, but would probably be kept for a few years.
     *
     * @see	http://www.hardened-php.net/suhosin/
     *
     * @param string $function_name Function to check for
     *
     * @return bool TRUE if the function exists and is safe to call,
     *              FALSE otherwise
     */
    function function_usable($function_name)
    {
        static $_suhosin_func_blacklist;

        if (function_exists($function_name)) {
            if (!isset($_suhosin_func_blacklist)) {
                $_suhosin_func_blacklist = extension_loaded('suhosin')
                    ? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
                    : [];
            }

            return !in_array($function_name, $_suhosin_func_blacklist, true);
        }

        return false;
    }
}

if (!function_exists('create_dir')) {
    function create_dir($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        } else {
            return false;
        }
    }
}
