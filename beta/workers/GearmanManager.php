<?php

/**
 * 实现对Gearman Worker的管理功能
 * 
 * @category   Manager
 * @package    workers
 * @author     liuwan <liwuan@ucweb.com>
 * @version    $Id:$
 * @copyright  优视动景  2010 版权所有
 * @link       http://u.uc.cn/
 * @since      File available since Release 2.1.0
 *
 */

declare ( ticks = 1 );

namespace framework\workers;

error_reporting ( E_ALL | E_STRICT );

/**
 * Class that handles all the process management
 */
class GearmanManager {
	
	/**
	 * Log levels can be enabled from the command line with -v, -vv, -vvv
	 */
	const LOG_LEVEL_INFO = 1;
	const LOG_LEVEL_PROC_INFO = 2;
	const LOG_LEVEL_WORKER_INFO = 3;
	const LOG_LEVEL_DEBUG = 4;
	const LOG_LEVEL_CRAZY = 5;
	
	const PID_PATH = "workermanager.pid";
	
	/**
	 * Holds the worker configuration
	 */
	protected static $config;
	
	/**
	 * 保存当前worker的配置信息
	 * @var array
	 */
	protected $workerInfo;
	
	protected $phpPath;
	
	/**
	 * Boolean value that determines if the running code is the parent or a child
	 */
	protected $isParent = true;
	
	/**
	 * When true, workers will stop look for jobs and the parent process will
	 * kill off all running children
	 */
	protected $stopWork = false;
	
	/**
	 * The timestamp when the signal was received to stop working
	 */
	protected $stopTime = 0;
	
	/**
	 * Holds the resource for the log file
	 */
	protected $logFileHandle;
	
	/**
	 * Flag for logging to syslog
	 */
	protected $log_syslog = false;
	
	/**
	 * Verbosity level for the running script. Set via -v option
	 */
	protected $verbose = 0;
	
	/**
	 * The array of running child processes
	 */
	protected $children = array ();
	
	/**
	 * The array of jobs that have workers running
	 */
	protected $jobs = array ();
	
	/**
	 * The PID of the running process. Set for parent and child processes
	 */
	protected $pid = 0;
	
	/**
	 * PID file for the parent process
	 */
	protected $pidFile = "";
	
	/**
	 * Holds the last timestamp of when the code was checked for updates
	 */
	protected $lastCheckTime = 0;
	
	/**
	 * When forking helper children, the parent waits for a signal from them
	 * to continue doing anything
	 */
	protected $waitForSignal = false;
	
	/**
	 * Maximum time a worker will run
	 */
	protected $lifeTime = 300;
	
	/**
	 * Maximum count of the handled jobs 
	 * 
	 */
	protected $handleNum = 0;
	
	
	protected $workerConfFile;
	
	
	/**
	 * PID of helper child
	 */	
	protected $deployPid = 0;
	
	/**
	 * Creates the manager and gets things going
	 *
	 */
	public function __construct() {
		
		if (! class_exists ( "GearmanWorker" )) {
			$this->showHelp ( "GearmanWorker class not found. Please ensure the gearman extenstion is installed" );
		}
		
		if (! function_exists ( "posix_kill" )) {
			$this->showHelp ( "The function posix_kill was not found. Please ensure POSIX functions are installed" );
		}
		
		if (! function_exists ( "pcntl_fork" )) {
			$this->showHelp ( "The function pcntl_fork was not found. Please ensure Process Control functions are installed" );
		}
		
		$this->phpPath = getenv('UZONE_PHP_PATH');
		
		if (! $this->phpPath) {
			echo 'please set environment variable: UZONE_PHP_PATH \n';
			exit(0);
		}
		
		$this->pid = getmypid ();
		
		/**
		 * Parse command line options. Loads the config file as well
		 */
		$this->getopt ();
		
		echo "pid: " . $this->pid . "\n";
		
		$this->initConfig ();
		
		/**
		 * Register signal listeners
		 */
		$this->registerTicks ();
		
		/**
		 * 通过Forked出一个子进程来检查是否需要热部署
		 */
		//$this->forkParent ( "hotDeploy" );
		
		$this->log ( "Started with pid $this->pid", GearmanManager::LOG_LEVEL_PROC_INFO );
		
		/**
		 * Start the initial workers and set up a running environment
		 */
		$this->bootstrap ();
		
		/**
		 * Main processing loop for the parent process
		 */
		$i = 0;
		while ( ! $this->stopWork || count ( $this->children ) ) {
			
			$status = null;
			
			/**
			 * Check for exited children
			 * WNOHANG： 如果没有子进程退出，则马上返回。
			 * 返回值： 
			 * 	 退出的子进程的ID， 
			 *   -1:表示错误，
			 *   0:表示没有子进程
			 */
			$exited = pcntl_wait ( $status, WNOHANG );
			/**
			 * We run other children, make sure this is a worker
			 */
//			if ($this->deployPid != 0 && $this->deployPid == $exited) {
//				$this->log ( "Child $exited exited (deploy)", GearmanManager::LOG_LEVEL_PROC_INFO );
//				$this->forkParent("hotDeploy");
//			}			
			
			if (isset ( $this->children [$exited] )) {
				/**
				 * If they have exited, remove them from the children array
				 * If we are not stopping work, start another in its place
				 */
				if ($exited) {
					$worker = $this->children [$exited];
					unset ( $this->children [$exited] );
					$this->log ( "Child $exited exited ($worker)", GearmanManager::LOG_LEVEL_PROC_INFO );					
				}								
			}
			if (! $this->stopWork && self::$config) {
				$this->bootstrap();
			}		
			
			if ($this->stopWork && time () - $this->stopTime > 60) {
				$this->log ( "Children have not exited, killing.", GearmanManager::LOG_LEVEL_PROC_INFO );
				$this->stopChildren ( SIGKILL );
			}
			
			/**
			 * php will eat up your cpu if you don't have this
			 */
			usleep ( 500000 );            
		}		
		/**
		 * Kill the helper if it is running
		 */
//		if (isset ( $this->deployPid )) {
//			posix_kill ( $this->deployPid, SIGKILL );
//		}		
		$this->log ( "Exiting" );
	}
	
	/**
	 * Handles anything we need to do when we are shutting down
	 *
	 */
	public function __destruct() {
		if ($this->isParent) {
			$path = self::PID_PATH;
			if (! empty ( $path ) && file_exists ( $path )) {
				unlink ( $path );
			}
		}
	}
	
	/**
	 * Parses the command line options
	 *
	 */
	protected function getopt() {
		
		$opts = getopt ( "dHv::" );
		
		if (isset ( $opts ["H"] )) {
			$this->showHelp ();
		}
		
		/**
		 * If we want to daemonize, fork here and exit
		 */
		if (isset ( $opts ["d"] )) {
			$pid = pcntl_fork ();
			if ($pid > 0) {
				$this->isParent = false;
				exit ();
			}
			$this->pid = getmypid ();
		}
		
		$fp = @fopen ( self::PID_PATH, "w" );
		if ($fp) {
			fwrite ( $fp, $this->pid );
			fclose ( $fp );
		} else {
			$this->showHelp ( "Unable to write PID to " . self::PID_PATH );
		}
		if (isset ( $opts ["v"] )) {
			switch ($opts ["v"]) {
				case false :
					$this->verbose = GearmanManager::LOG_LEVEL_INFO;
					break;
				case "v" :
					$this->verbose = GearmanManager::LOG_LEVEL_PROC_INFO;
					break;
				case "vv" :
					$this->verbose = GearmanManager::LOG_LEVEL_WORKER_INFO;
					break;
				case "vvv" :
					$this->verbose = GearmanManager::LOG_LEVEL_DEBUG;
					break;
				default :
				case "vvvv" :
					$this->verbose = GearmanManager::LOG_LEVEL_CRAZY;
					break;
			}
		}
				
		$logFile = __DIR__ . "/../../logs/worker.GearmanManager_" . date ('Ymd') . ".log";
		$this->logFileHandle = @fopen ( $logFile, "a" );
		if (! $this->logFileHandle) {
			$this->showHelp ( "Could not open log file $logFile" );
		}
	}
	
	/**
	 * Forks the process and runs the given method. The parent then waits
	 * for the child process to signal back that it can continue
	 *
	 * @param   string  $method  Class method to run after forking
	 *
	 */
	protected function forkParent($method) {
		$this->waitForSignal = true;
		$pid = pcntl_fork ();
		switch ($pid) {
			case 0 :
				$this->isParent = false;				
				$this->$method ();
				break;
			case - 1 :
				$this->log ( "Failed to fork" );
				$this->stopWork = true;
				break;
			default :
				$this->deployPid = $pid;				
				while ( $this->waitForSignal && ! $this->stopWork ) {
					usleep ( 5000 );
				}
				break;
		}
	}
	
	/**
	 * 通过Forked的子进程来检查是否需要workers的热部署。
	 *
	 */
	protected function hotDeploy() {
		
		$this->log ( "Helper forked", GearmanManager::LOG_LEVEL_PROC_INFO );
		
		/**
		 * Since we got here, all must be ok, send a CONTINUE
		 */
		posix_kill ( $this->pid, SIGCONT );
		
        $last_check_time = time(); 
        while(1) {			
        	$this->log ( "Check config file: $this->workerConfFile", GearmanManager::LOG_LEVEL_DEBUG );
			clearstatcache();
            $mtime = filemtime($this->workerConfFile);
            if($mtime > $last_check_time){            	
            	#$this->log ( "Reload config file: $this->workerConfFile", GearmanManager::LOG_LEVEL_PROC_INFO );
            	posix_kill($this->pid, SIGHUP);
			}
            $last_check_time = time();
            sleep(10);
		}
        
	}
	
	/**
	 *   初始化配置
	 */
	protected function initConfig($reload=false) {
		if ($reload && self::$config) {
			self::$config = null;
		}
		if (! self::$config) {
			$this->workerConfFile = dirname ( __FILE__ ) . '/../resources/configs/Workers.inc.php'; 
			$tmp = require $this->workerConfFile;
			self::$config = array ();
			foreach ( $tmp as $workerType => $workerInfo ) {
				if ($workerInfo ['handler'] && count ( $workerInfo ['handler'] ) == 2) {
					$class = $workerInfo ['handler'] [0];
					$method = $workerInfo ['handler'] [1];
					
					$file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
					$this->log ( "Loading file: $file", self::LOG_LEVEL_INFO );
					if (!file_exists($file)) {
						$this->log ( "No such file or directory in $class", self::LOG_LEVEL_INFO );
						continue;
					}
					if (class_exists ( $class ) && method_exists ( $class, $method )) {
					
						self::$config [$workerType] = $workerInfo;
					} else {
						$this->log ( "Function $class->$method not found", self::LOG_LEVEL_INFO );
					}
				} else {
					$this->log ( "WorkerType: $workerType is not under controlled by WorkerManager", self::LOG_LEVEL_INFO );
				}
			}			
						
			unset ( $tmp );
		}
		return true;
	}
	/**
	 * Bootstap a set of workers and any vars that need to be set
	 *
	 */
	protected function bootstrap() {
		
		$procNum = array ();
		
		/**
		 * Next we loop the workers and ensure we have enough running
		 * for each worker
		 */		
		foreach ( self::$config as $workerType => $workerInfo ) {			
			if (empty ( $procNum [$workerType] )) {
				$procNum [$workerType] = 0;
			}
			
			while ( $procNum [$workerType] < $workerInfo ["threadNum"] ) {				
				if ($this->canCreateWorker($workerType)) {
					$this->startWorker ( $workerType );
				}
				$procNum [$workerType] ++;
			}
			/**
			 * php will eat up your cpu if you don't have this
			 */
			usleep ( 50000 );
		}
		
		/**
		 * Set the last code check time to now since we just loaded all the code
		 */
		$this->lastCheckTime = time ();	
	}
	
	/**
	 * 检查当前的Worker是否存在于配置文件中，或者Worker数是否足够。
	 * 
	 * @param string $workerType
	 * @return bool true：可以创建该worker，false：不能创建
	 */
	protected function canCreateWorker($workerType) {
		if(!self::$config) return false;
		//如果进程表中有该worker类型，但是配置文件中没有，表示该worker已经删除，不能再该worker
		if (!isset(self::$config[$workerType])) {
			$this->log ( "Worker $workerType has been removed from the config file", GearmanManager::LOG_LEVEL_WORKER_INFO );
			return false;
		}
		$countArr = array_count_values($this->children);				
		$workerInfo = self::$config[$workerType];		
		/*
		 * 1.如果配置文件中存在该Worker，但是进程表中没有该worker类型，可以创建
		 * 2.如果进程表中该worker类型数量少于在配置文件中的数量，可以创建
		 */
		if (!isset($countArr[$workerType]) ||
		    ($countArr[$workerType] < $workerInfo["threadNum"])) {
			return true;
		}
		#print_r($countArr);		
		return false;
	}
	
	protected function startWorker($workerType) {
						
		$ds = date ( "Y-m-d H:i:s" );
		echo "[$ds] Start worker  $workerType \n";
		
		$pid = pcntl_fork ();
		
		switch ($pid) {
			
			case 0 :
				
				$this->isParent = false;
				
				$this->registerTicks ( false );
				
				$this->pid = getmypid ();
				
				$this->log ( "$this->phpPath Runner.php -w $workerType", GearmanManager::LOG_LEVEL_WORKER_INFO );

				pcntl_exec($this->phpPath, array('Runner.php','-w',$workerType));
				
				exit ();
				
				/*
				$this->runWorker ( $workerType );
				$this->log ( "Child exiting", GearmanManager::LOG_LEVEL_WORKER_INFO );
				exit ();
				*/				
				break;
			
			case - 1 :
				
				$this->log ( "Could not fork" );
				$this->stopWork = true;
				$this->stopChildren ();
				break;
			
			default :
				/**
				 * The current proccess is parent.
				 * $pid is child proccess id.
				 */ 
				$this->log ( "Started child $pid ($workerType)", GearmanManager::LOG_LEVEL_PROC_INFO );
				$this->children [$pid] = $workerType;
		}
	
	}
	
	/**
	 * Stops all running children
	 */
	protected function stopChildren($signal = SIGTERM) {
		$this->log ( "Stopping children", GearmanManager::LOG_LEVEL_PROC_INFO );		
		foreach ( $this->children as $pid => $worker ) {
			$this->log ( "Stopping child $pid ($worker)", GearmanManager::LOG_LEVEL_PROC_INFO );
			posix_kill ( $pid, $signal );
		}	
	}
	
	/**
	 * Registers the process signal listeners
	 */
	protected function registerTicks($parent = true) {
		
		if ($parent) {
			$this->log ( "Registering signals for parent", GearmanManager::LOG_LEVEL_DEBUG );
			/**
			 * 注册信号，把信号和具体的函数或者方法绑定起来。
			 * 如：当监听到 SIGTERM，就执行$this->signal这个方法
			 */
			pcntl_signal ( SIGTERM, array ($this, "signal" ) );
			pcntl_signal ( SIGINT, array ($this, "signal" ) );
			pcntl_signal ( SIGUSR1, array ($this, "signal" ) );
			pcntl_signal ( SIGUSR2, array ($this, "signal" ) );
			pcntl_signal ( SIGCONT, array ($this, "signal" ) );
			pcntl_signal ( SIGHUP, array ($this, "signal" ) );
		} else {
			$this->log ( "Registering signals for child", GearmanManager::LOG_LEVEL_DEBUG );
			
			$res = pcntl_signal ( SIGTERM, array ($this, "signal" ) );
			if (! $res) {
				$this->log ( "Registering signals-SIGTERM for child fail", GearmanManager::LOG_LEVEL_DEBUG );
				exit ();
			}
			
		}
	}
	
	/**
	 * Handles signals
	 */
	public function signal($signo) {
		$this->log ( "isParent: $this->isParent, pid: $this->pid ", GearmanManager::LOG_LEVEL_PROC_INFO);
		
		if (! $this->isParent) {
			
			$this->stopWork = true;
		
		} else {			
			switch ($signo) {
				case SIGUSR1 :
					$this->showHelp ( "No worker files could be found" );
					break;
				case SIGUSR2 :
					$this->showHelp ( "Error validating worker functions" );					
					break;
				case SIGCONT :
					$this->waitForSignal = false;
					break;
				case SIGINT :
				case SIGTERM :
					echo "Receive a SIGTERM signal \n";
					$this->log ( "Shutting down..." );
					$this->stopWork = true;
					$this->stopTime = time ();					
					$this->stopChildren ( SIGKILL );					
					break;
				case SIGHUP :
					$this->log ( "Reloading config file to restart children", GearmanManager::LOG_LEVEL_PROC_INFO );					
					$this->initConfig(true);					
					$this->stopChildren ();
					break;
				default :
				// handle all other signals
			}
		}
	
	}
	
	/**
	 * Logs data to disk or stdout
	 */
	protected function log($message, $level = GearmanManager::LOG_LEVEL_INFO) {
		
		static $init = false;
		
		if ($level > $this->verbose)
			return;
		
		if ($this->log_syslog) {
			$this->syslog ( $message, $level );
			return;
		}
		
		if (! $init) {
			$init = true;
			
			if ($this->logFileHandle) {
				$ds = date ( "Y-m-d H:i:s" );
				fwrite ( $this->logFileHandle, "Date                  PID   Type   Message\n" );
			} else {
				echo "PID   Type   Message\n";
			}
		
		}
		
		$label = "";
		
		switch ($level) {
			case GearmanManager::LOG_LEVEL_INFO :
				$label = "INFO  ";
				break;
			case GearmanManager::LOG_LEVEL_PROC_INFO :
				$label = "PROC  ";
				break;
			case GearmanManager::LOG_LEVEL_WORKER_INFO :
				$label = "WORKER";
				break;
			case GearmanManager::LOG_LEVEL_DEBUG :
				$label = "DEBUG ";
				break;
		}
		
		$log_pid = str_pad ( $this->pid, 5, " ", STR_PAD_LEFT );
		
		if ($this->logFileHandle) {
			$ds = date ( "Y-m-d H:i:s" );
			fwrite ( $this->logFileHandle, "[$ds] $log_pid $label $message\n" );
		} else {
			echo "$log_pid $label $message\n";
		}
	
	}
	
	/**
	 * Logs data to syslog
	 */
	protected function syslog($message, $level) {
		switch ($level) {
			case GearmanManager::LOG_LEVEL_INFO :
			case GearmanManager::LOG_LEVEL_PROC_INFO :
			case GearmanManager::LOG_LEVEL_WORKER_INFO :
			default :
				$priority = LOG_INFO;
				break;
			case GearmanManager::LOG_LEVEL_DEBUG :
				$priority = LOG_DEBUG;
				break;
		}
		
		if (! syslog ( $priority, $message )) {
			echo "Unable to write to syslog\n";
		}
	}
	
	/**
	 * Shows the scripts help info with optional error message
	 */
	protected function showHelp($msg = "") {
		if ($msg) {
			echo "ERROR:\n";
			echo "  " . wordwrap ( $msg, 72, "\n  " ) . "\n\n";
		}
		echo "Gearman worker manager script\n\n";
		echo "USAGE:\n";
		echo "php ManagerRunner -H | [-l LOG_FILE] [-d] [-v]\n\n";
		echo "OPTIONS:\n";
		echo "  -d             Daemon, detach and run in the background\n";
		echo "  -H             Shows this help\n";
		echo "  -l LOG_FILE    Log output to LOG_FILE or use keyword 'syslog' for syslog support\n";
		echo "  -v             Increase verbosity level by one\n";
		echo "\n";
		exit ();
	}

}
?>
