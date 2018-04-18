<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\apache\log4php\src\main\php\Logger.php';

class Bibcite_Logger
{
	// Hold an instance of the class
	private static $instance;

	// The root logger.
	private $log;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Bibcite_Logger
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function instance() : Bibcite_Logger {
		if (!isset(self::$instance)) {
			self::$instance = new Bibcite_Logger();
		}
		return self::$instance;
	}

	private function __construct() {

		// Use an anonymous object 
		$configuration = array();
		Logger::configure(
			$configuration, 
			new class implements LoggerConfigurator {
				public function configure(LoggerHierarchy $hierarchy, $input = null) {

					// A simple layour
					$layout = new LoggerLayoutPattern();
					$layout->setConversionPattern("%date [%level] %msg%newline");
					$layout->activateOptions();
							
					// Create an appender which logs to file
					$appFile = new LoggerAppenderFile('foo');
					$appFile->setFile(dirname(__FILE__) . "\..\logs/bibcite-sc.log");
					$appFile->setAppend(true);
					$appFile->setThreshold('all');
					$appFile->setLayout($layout);
					$appFile->activateOptions();
							
					// Add both appenders to the root logger
					$this->root = $hierarchy->getRootLogger();
					$this->root->addAppender($appFile);
				}
			}
		);

		$this->log = Logger::getLogger('bibcite-sc');
	}

	public function debug($message) {
		$this->log->debug($message);
	}

	public function info($message) {
		$this->log->info($message);
	}

	public function warn($message) {
		$this->log->warn($message);
	}

	public function error($message) {
		$this->log->error($message);
	}
}