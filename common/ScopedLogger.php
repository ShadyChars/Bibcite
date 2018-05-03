<?php

namespace Bibcite\Common;

require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

/**
 * A wrapper for a \Psr\Log\LoggerInterface allowing messages to be prefixed 
 * with an arbitrary string.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class ScopedLogger implements \Psr\Log\LoggerInterface
{
	// The root logger.
    private $log;
    
    // Our prefix.
    private $prefix;

    /**
     * Constructor.
     *
     * @param \Psr\Log\LoggerInterface $log
     * @param string $prefix
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @link https://github.com/OrkneyDullard/Bibcite
     * @package Bibcite
     * @since 1.0.0
     */
    public function __construct(\Psr\Log\LoggerInterface $log, string $prefix) {
        $this->log = $log;
        $this->prefix = $prefix;
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
		$this->log->emergency($this->prefix . $message, $context);
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
		$this->log->alert($this->prefix . $message, $context);
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
		$this->log->critical($this->prefix . $message, $context);
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
		$this->log->error($this->prefix . $message, $context);
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
		$this->log->warning($this->prefix . $message, $context);
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
		$this->log->notice($this->prefix . $message, $context);
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
		$this->log->info($this->prefix . $message, $context);
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
		$this->log->debug($this->prefix . $message, $context);
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
		$this->log($level, $this->prefix . $message, $context);
	}
}