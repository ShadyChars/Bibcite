<?php

namespace Bibcite\Common;

require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * A singleton logging class.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Logger
{
	// Hold an instance of the class
	private static $instance;

	// The root logger.
	private $log;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Logger
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function instance() : Logger {
		if (!isset(self::$instance)) {
			self::$instance = new Logger();
		}
		return self::$instance;
	}

	/**
	 * Get the URL of the log file.
	 *
	 * @return string URL of log file
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function getLogFileUrl() : string {

		// Get the URL of the log file directory and append the base file name
		$log_file_path = self::getLogFilePath();
		return plugin_dir_url($log_file_path) . \basename($log_file_path);
	}

	/**
	 * Get the path to the log file.
	 *
	 * @return string path to the log file
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function getLogFilePath() : string {
		return plugin_dir_path(dirname(__FILE__)) . "/logs/bibcite.log";
	}

	private function __construct() {

		// Use an anonymous object 
		$configuration = array();
		\Logger::configure(
			$configuration, 
			new class implements \LoggerConfigurator {
				public function configure(
					\LoggerHierarchy $hierarchy, $input = null
				) {

					// A simple layout
					$layout = new \LoggerLayoutPattern();
					$layout->setConversionPattern(
						"%date [%level] %msg%newline"
					);
					$layout->activateOptions();
							
					// Create an appender which logs to file
					$appFile = 
						new \LoggerAppenderRollingFile('file-appender');
					$appFile->setFile(Logger::getLogFilePath());
					$appFile->setAppend(true);
					$appFile->setThreshold('all');
					$appFile->setLayout($layout);
					$appFile->setMaxBackupIndex(5);
					$appFile->setMaxFileSize("1MB");
					$appFile->activateOptions();
							
					// Add both appenders to the root logger
					$this->root = $hierarchy->getRootLogger();
					$this->root->addAppender($appFile);
				}
			}
		);

		$this->log = \Logger::getLogger('bibcite');
	}

	/**
	 * Log a message at debug level.
	 *
	 * @param string $message message to be logged
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function debug(string $message) {
		$this->log->debug($message);
	}

	/**
	 * Log a message at info level.
	 *
	 * @param string $message message to be logged
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function info(string $message) {
		$this->log->info($message);
	}

	/**
	 * Log a message at warning level.
	 *
	 * @param string $message message to be logged
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function warn(string $message) {
		$this->log->warn($message);
	}

	/**
	 * Log a message at error level.
	 *
	 * @param string $message message to be logged
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function error(string $message) {
		$this->log->error($message);
	}
}