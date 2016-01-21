<?php
/**
 * Created by PhpStorm.
 * User: blueworls
 * Date: 4/20/14
 * Time: 7:39 PM
 */


use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Class documentation
 * 实现PHP-FIG psr3 规范的日志
 */
class logger extends AbstractLogger
{
    /**
     * log 文件的路径
     * @var string
     */
    private $log_file_path = null;
    private $log_directory=null;

    /**
     * 当前logging的最低级别
     * @var integer
     */
    private $log_level_threshold = LogLevel::DEBUG;

    private $log_levels = array(
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    );

    /**
     * 用于储存log文件的实例
     * @var resource
     */
    private $file_handle = null;

    /**
     * 用于log文件的日期格式
     * @var string
     */
    private $date_format = 'Y-m-d G:i:s.u';

    /**
     * 默认log文件的操作权限(八进制)
     * @var integer
     */
    private $default_permissions = 0777;


    /**
     * Class constructor
     * @param string $log_directory 存放log文件的文件夹路径
     * @param int|string $log_level_threshold log级别
     * @throws RuntimeException
     */
    public function __construct($log_directory, $log_level_threshold = LogLevel::DEBUG)
    {
        $this->log_level_threshold = $log_level_threshold;

        $this->log_directory = rtrim($log_directory, '\\/');
        if (! file_exists($this->log_directory)) {
            mkdir($this->log_directory, $this->default_permissions, true);
        }

        $this->log_file_path = $this->log_directory.DIRECTORY_SEPARATOR.'log_'.date('Y-m-d').'.txt';
        if (file_exists($this->log_file_path) && !is_writable($this->log_file_path)) {
            throw new RuntimeException('无法写入指定文件请检查文件权限设置');
        }

        $this->file_handle = fopen($this->log_file_path, 'a');
        if ( ! $this->file_handle) {
            throw new RuntimeException('无法打开指定文件请检查文件权限设置');
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->file_handle) {
            fclose($this->file_handle);
        }
    }

    /**
     * 设置日期格式
     *
     * @param string $date_format 设置日期格式
     */
    public function set_date_format($date_format)
    {
        $this->date_format = $date_format;
    }

    /**
     * 设置log 级别
     *
     * @param $logLevelThreshold
     */
    public function set_log_level_threshold($logLevelThreshold)
    {
        $this->log_level_threshold = $logLevelThreshold;
    }

    /**
     * log 信息
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->log_levels[$this->log_level_threshold] < $this->log_levels[$level]) {
            return;
        }
        $message = $this->format_message($level, $message, $context);
        $this->write($message);
    }

    /**
     * 写入一条无格式的消息，没有timestamp
     *
     * @param $message
     * @throws RuntimeException
     * @return void
     */
    public function write($message)
    {
        //判断是否跨天
        $new_log_file_path = $this->log_directory.DIRECTORY_SEPARATOR.'log_'.date('Y-m-d').'.txt';
        if($new_log_file_path!=$this->log_file_path)
        {
            //关闭旧文件
            if ($this->file_handle) {
                fclose($this->file_handle);
            }
            
            //打开新文件
            $this->log_file_path = $new_log_file_path;
            if (file_exists($this->log_file_path) && !is_writable($this->log_file_path)) {
                throw new RuntimeException('无法写入指定文件请检查文件权限设置');
            }

            $this->file_handle = fopen($this->log_file_path, 'a');
            if ( ! $this->file_handle) {
                throw new RuntimeException('无法打开指定文件请检查文件权限设置');
            }            
        }
        
        if (! is_null($this->file_handle)) {
            if (fwrite($this->file_handle, $message) === false) {
                throw new RuntimeException('无法写入指定文件请检查文件权限设置');
            }
        }
    }

    /**
     * 构造用于log的消息
     *
     * @param  string $level   log级别
     * @param  string $message 用于log的消息
     * @param  array  $context log的附属信息
     * @return string
     */
    private function format_message($level, $message, $context)
    {
        $level = strtoupper($level);
        if (! empty($context)) {
            $message .= PHP_EOL.$this->indent($this->context_to_string($context));
        }
        return "[{$this->get_time_stamp()}] [{$level}] {$message}".PHP_EOL;
    }

    /**
     * 获取当前时间用于log
     *
     * @return string
     */
    private function get_time_stamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.'.$micro, $originalTime));

        return $date->format($this->date_format);
    }

    /**
     * 获取用于log的附属信息array，转换为string
     *
     * @param  array $context 附属信息
     * @return string
     */
    private function context_to_string($context)
    {
        $export = '';
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace(array(
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ), array(
                '=> $1',
                'array()',
                '    ',
            ), str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
    }

    /**
     * 调整string的缩进
     *
     * @param  string $string 用于缩进的string
     * @param  string $indent 缩进的格式
     * @return string
     */
    private function indent($string, $indent = '    ')
    {
        return $indent.str_replace("\n", "\n".$indent, $string);
    }
}
