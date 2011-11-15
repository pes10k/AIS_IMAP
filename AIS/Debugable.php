<?php
/**
 * This package contains a series of files that wraps around the functionality
 * provided by the PHP IMAP extension, and provide an OO interface for interacting
 * with IMAP mail servers, their messages, and those messages' attachments.
 *
 * This package was developed by AISLabs Inc. in Chicago, IL
 * and written by Peter Snyder.
 *
 * PHP Version 5
 *
 * @category Mail
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version  GIT: <git_id>
 * @link     http://aislabs.com/
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */

/**
 * Classes inheriting from this base class will get some default
 * debugging capabilities, and will pass on a single instance of an
 * AIS_Debugger object to all objects they create
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
abstract class AIS_Debugable
{
    /**
     * A reference to the debugger object (which is share between
     * all debuggable objects).
     * 
     * @var AIS_Debugger
     * @access protected
     */
    protected $debugger;

    /**
     * Set the object to gather debugging information about the current connection
     * 
     * @param AIS_Debugger $debugger A refernece to the debugger object,
     *      which this object should send debugging information to.
     *
     * @return AIS_IMAP Returns the current object for chaining
     */     
    public function setDebugger($debugger)
    {
        $this->debugger = $debugger;
        return $this;
    }

    /**
     * Returns a boolean description of whether the object
     * is in debugging mode
     * 
     * @access public
     * @return bool
     */
    public function isInDebugMode()
    {
        return isset($this->debugger);
    }

    /**
     * Returns an array of strings describing how the object has
     * handleded the current connection
     * 
     * @return AIS_Debugger
     */
    public function debugger()
    {
        return $this->debugger;
    }

    // =================== 
    // ! Private Methods   
    // ====================

    /**
     * Sends a message to the debugger object.
     * 
     * @param string $message A string description that should be logged.
     * @param array  $context (default: false)  An associative array of
     *      optional variables that should also be sent and logged.
     *
     * @return Object Returns a reference to the current object, to allow
     *      for method chaining
     */
    protected function setDebugInformation($message, $context = false)
    {
        if ($this->isInDebugMode()) {

            $this->debugger->addMessage($message, $context);
        }

        return $this;
    }
}