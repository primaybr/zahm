<?php

namespace Core;

class Log
{
    private $log_file;
    private $pointer;
    private $time;
    private $file_exists = true;

    public function __construct()
    {
        $this->time = date('[Y-m-d H:i:s]');
    }

    // set log file (path and name)
    public function path($path)
    {
        $this->log_file = $path;

        return $this;
    }

    // write message to the log file
    public function write($message)
    {
        // if file pointer doesn't exist, then open log file
        if (!is_resource($this->pointer)) {
            $this->_open();
        }
        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);

        fwrite($this->pointer, "{$this->time} (${script_name}) ${message}".PHP_EOL);

        if (!$this->file_exists) {
            chmod($this->log_file, 644);
        }

        fclose($this->pointer);
    }

    private function _open()
    {
        $log_file_default = _LOG.'log_'.date('Ymd').'.log';
        // define log file from path method or use previously set default
        $this->log_file = $this->log_file ? $this->log_file : $log_file_default;

        if (!file_exists($this->log_file)) {
            $this->file_exists = false;
        }

        $this->pointer = fopen($this->log_file, 'a') or exit("Can't open ${path}!");
    }
}
