<?php
use \Workerman\Worker;
use \Workerman\Lib\Timer;
/**
 * workerman-filemonitor for windows
 *
 * 监控文件更新并自动reload workerman
 *
 * 使用方法:
 * require_once __DIR__ . '/FileMonitor.php';
 * new FileMonitor($worker, $dir, $timer);
 */
class FileMonitor
{
    //待监听的项目目录
    private $_monitor_dir = '';
    //热更新间隔时间,默认3s
    private $_interval = 0;
    //最后一次同步时间
    private $_last_time = 0;
    function __construct ($worker, $dir, $timer = 3)
    {
        // watch Applications catalogue
        $this->_monitor_dir = __DIR__ .'/'. $dir;
        $this->_interval = $timer;
        $this->_last_time = time();
        // Emitted when data received
        $worker->reloadable = false;
        // Emitted when data received
        $worker->onWorkerStart = function()
        {
            // watch files only in daemon mode
            if (Worker::$daemonize === false)
            {
                // chek mtime of files per second 
                Timer::add($this->_interval, [$this, 'monitor']);
            }
        };
    }
    //监听器，kill进程
    public function monitor ()
    {
        // recursive traversal directory
        $iterator = new RecursiveDirectoryIterator($this->_monitor_dir);
        $iterator = new RecursiveIteratorIterator($iterator);
        foreach ($iterator as $file)
        {
            // only check php files
            if (pathinfo($file, PATHINFO_EXTENSION) != 'php') continue;
            // check mtime
            if ($this->_last_time < $file->getMTime())
            {
                exec('taskkill -f -pid '. getmypid());
                $this->_last_time = $file->getMTime();
                return true;
            }
        }
    }
}