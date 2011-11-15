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
 * This class wraps provides a simple record of what objects are doing.
 * It can include code level information (file, line number, etc.), along
 * with textual descriptions of behaviors.  Instances of the class can be
 * configured to echo out debugging information in realtime, store it
 * internally for later use, among other options.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
class AIS_Debugger
{
    /**
     * Make the debugger display messages as they are generated
     * 
     * (default value: true)
     * 
     * @var bool
     * @access protected
     */
    protected $display_messages_in_realtime = true;

    /**
     * Whether the debugger should capture informatino about when logged
     * events take place
     *
     * (default value: false)
     *
     * @var bool
     * @access protected
     */
    protected $capture_timing_information = false;

    /**
     * Whether the debugger should capture code level
     * (files, line #s, etc.) as its gathering data
     *
     * (default value: false)
     *
     * @var bool
     * @access protected
     */
    protected $capture_code_level_information = false;

    /**
     * Whether the debugger should capture application level
     * information about the applications actions, usually
     * in the form of application provided strings
     *
     * (default value: true)
     *
     * @var bool
     * @access protected
     */
    protected $capture_application_level_information = true;

    /**
     * An array of zero or more AIS_Debugger_Message items
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $debug_info = array();

    // ==================
    // ! Public Methods
    // ==================

    /**
     * Allow for passing in a set of configuration options
     * at construction
     *
     * @param array $options (default: array())  An optional array of
     *      configuration options that are passed along to the object
     *      after construction.
     *
     * @return void
     * @see setOptions()
     */
    public function __construct($options = array())
    {
        if ( ! empty($options)) {

            $this->setOptions($options);
        }
    }

    /**
     * Add a debugging message to the record
     *
     * @param string $message A string description that should be logged.
     * @param array  $context (default: false)  An associative array of
     *      optional variables that should also be sent and logged.
     *
     * @return AIS_Debugger A refernece to the current object, to
     *      allow for method chaining
     */
    public function addMessage($message, $context = false)
    {
        $entry = new AIS_Debugger_Entry($this);

        if ($this->capture_timing_information) {

            $entry->recordTime();
        }

        if ($this->capture_code_level_information) {

            $backtrace = debug_backtrace();
            $entry->populateWithBacktrace($backtrace[2]);
        }

        if ($this->capture_application_level_information) {

            $entry->setDescription($message);

            if ($context) {

                $entry->setArguments($context);
            }
        }

        $this->debug_info[] = $entry;
        
        if ($this->display_messages_in_realtime) {

            echo $entry->summary() . PHP_EOL;
        }

        return $this;
    }

    /**
     * Returns an array of AIS_Debugger_Entry objects, in the order that
     * they were created
     *
     * @return array an array of AIS_Debugger_Entry objects
     */
    public function messages()
    {
        return $this->debug_info;
    }

    /**
     * Return a string with information about each message thats' be logged so far,
     * one on each line.
     *
     * @access public
     * @return void
     */
    public function displayMessages()
    {
        $string = '';

        foreach ($this->debug_info as $item) {

            $string .= $item->summary() . PHP_EOL;
        }

        return $string;
    }

    /**
     * Returns the position of the given debugging entry in the
     * debugger's log of messages.  
     * 
     * @param AIS_Debugger_Entry $entry A AIS_Debugger_Entry object
     *
     * @return int|bool the position of the given debug entry, or false
     *      if the entry doesn't exist in the current collection
     */
    public function indexOfEntry($entry)
    {
        return array_search($entry, $this->debug_info);
    }

    /**
     * Provide different configuration options to the debugger,
     * in the form of an array.  Accepts zero or more of the following
     * keys
     *  - code_level (bool): whether the debugger should capture code level
     *      information about the application.  This is information about
     *      where certain functions were called in the code (line number,
     *      method names, etc.)
     *  - app_level (bool): whether the debugger should capture
     *      application provided strings about its functioning
     *  - timing (bool): whether the debugger should capture
     *      information about when different events take place (microseconds)
     *  - print (bool): whether messages should be stored for future
     *      use.  Otherwise messages will be echoed out as they're received
     *
     * @param array $options (default: array())  An array of configuration options
     *      for the debugger
     *
     * @return void
     */
    public function setOptions($options = array())
    {
        $option_translation = $this->optionTranslaitons();

        foreach ($option_translation as $config_option => $local_property) {

            if (isset($options[$config_option])) {

                $this->$local_property = $options[$config_option];
            }
        }
    }

    /**
     * Return the debugger's setting for a given option, or -1 
     * if the option is not understood.
     * 
     * @param string $option_key One of the valid configuration settings.
     *
     * @return bool|-1
     * @see setOptions()
     */
    public function getOption($option_key)
    {
        $option_translation = $this->optionTranslaitons();

        if (isset($option_translation[$option_key])) {

            return $this->{$option_translation[$option_key]};
        }

        return -1;
    }
    
    /**
     * Removes all messages that have been logged from the current object.
     * 
     * @access public
     * @return void
     */
    public function clear()
    {
        $this->debug_info = array();
    }

    // ==================
    // ! Private Methods
    // ==================

    /**
     * Returns an array of string -> string associations
     * that are used to map between short hand configuration settings
     * and those settings internal representation in the object.
     * 
     * @return array
     */    
    protected function optionTranslaitons()
    {
        static $option_translation = array(
            'code_level' => 'capture_code_level_information',
            'app_level' => 'capture_application_level_information',
            'timing' => 'capture_timing_information',
            'print' => 'display_messages_in_realtime',
        );

        return $option_translation;
    }
}