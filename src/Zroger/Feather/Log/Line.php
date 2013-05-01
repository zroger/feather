<?php

namespace Zroger\Feather\Log;

class Line
{
    /**
     * The log message.
     * @var string
     */
    protected $message;

    /**
     * The timestamp of the message.
     * @var int
     */
    protected $timestamp;

    /**
     * Log level of the message.
     * @var string
     */
    protected $level;

    public function __construct($message, $timestamp = null, $level = null)
    {
        $this->message = $message;
        $this->timestamp = $timestamp ?: time();
        $this->level = $level ?: 'info';
    }

    /**
     * Gets the The log message..
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the The log message..
     *
     * @param string $message the message
     *
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Gets the The timestamp of the message..
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets the The timestamp of the message..
     *
     * @param int $timestamp the timestamp
     *
     * @return self
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Gets the Log level of the message..
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Sets the Log level of the message..
     *
     * @param string $level the level
     *
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }
}
