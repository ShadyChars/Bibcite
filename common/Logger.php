<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * A singleton logging class.
 * 
 * Implementation based on 
 * https://www.sitepoint.com/implementing-psr-3-with-log4php/.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Logger implements \Psr\Log\LoggerInterface
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

	/**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = array()) {
		$this->log->fatal($this->interpolate($message, $context));
	}

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert($message, array $context = array()) {
		$this->log->fatal($this->interpolate($message, $context));
	}

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical($message, array $context = array()) {
		$this->log->error($this->interpolate($message, $context));
	}

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = array()) {
		$this->log->error($this->interpolate($message, $context));
	}

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning($message, array $context = array()) {
		$this->log->warn($this->interpolate($message, $context));
	}

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = array()) {
		$this->log->info($this->interpolate($message, $context));
	}

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info($message, array $context = array()) {
		$this->log->info($this->interpolate($message, $context));
	}

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug($message, array $context = array()) {
		$this->log->debug($this->interpolate($message, $context));
	}

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
	public function log($level, $message, array $context = array()) {
		throw new Exception('Please call specific logging method');
	}
	
	/**
   * Interpolates context values into the message placeholders.
   * Taken from PSR-3's example implementation.
   */
  protected function interpolate($message, array $context = array()) {
      // build a replacement array with braces around the context
      // keys
      $replace = array();
      foreach ($context as $key => $val) {
          $replace['{' . $key . '}'] = $val;
      }

      // interpolate replacement values into the message and return
      return strtr($message, $replace);
  }

	private function __construct() {
		$this->log = new \Monolog\Logger('bibcite');
    $this->log->pushHandler(
      new \Monolog\Handler\StreamHandler(Logger::getLogFilePath(), \Monolog\Logger::DEBUG)
    );
	}
}