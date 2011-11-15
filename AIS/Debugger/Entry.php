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
 * A class that represents debugging information.  Each instance of the class
 * contains some information about a potentially signficant event, such as
 * the file that contains the code that the event occured in, the relevant line
 * number, and a string describing the event.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 * 
 */
class AIS_Debugger_Entry
{
    /**
     * Reference to the root debugger object that is tracking this entry
     * 
     * @var AIS_Debugger
     * @access protected
     */
    protected $debugger;

    /**
     * The name of the class, if one exists, that generated
     * the debugging information
     * 
     * @var null|string
     * @access protected
     */
    protected $class;

    /**
     * The name of the file where the code that generated the
     * debugging information was built from
     * 
     * @var null|string
     * @access protected
     */
    protected $file;

    /**
     * The line number where the debugging message was fired from
     * 
     * @var null|int
     * @access protected
     */
    protected $line;
    
    /**
     * The name of the method from which the debugging message was fired.
     * If the debugging call was made outside of a object, will
     * include the function name, if possible
     * 
     * @var null|string
     * @access protected
     */
    protected $method;

    /**
     * Application provided description of whats being debugged
     * 
     * @var mixed
     * @access protected
     */
    protected $description;

    /**
     * The time, with microsecond granularity, that the message was debugged
     * 
     * @var float
     * @access protected
     */
    protected $time;

    /**
     * Arbitrary context passed to the debugger associated with this entry
     * 
     * @var mixed
     * @access protected
     */
    protected $arguments;

    /**
     * Conveince constructor that allows for setting the refernece to the
     * root debugger object, along with an optional string description
     * of the event being logged, at initialization.
     * 
     * @param AIS_Debugger $debugger    A reference to the root debugger object.
     *                                  Usually there will only be one of these
     *                                  per application.
     * @param string       $description (default: false) An optional, human
     *                                  readable description of the event being
     *                                  logged.
     *
     * @return void
     */
    public function __construct($debugger, $description = false)
    {
        $this->debugger = $debugger;

        if ($description) {

            $this->setDescription($description);
        }
    }

    /**
     * Return a text description of the debugging message.
     * 
     * @return string A human readable description of the event that was logged
     */
    public function summary()
    {    
        $debugger = $this->debugger;
        
        if ( ! $debugger) {

            return $this->description();

        } else {

            $output_string = '';
            
            if ($index = $this->debugger->indexOfEntry($this)) {

                $output_string .= $index . '. ';
            }
            
            if ($this->debugger->getOption('code_level')) {
    
                $output_string .= sprintf(
                    '%s::%s.%s - ',
                    $this->className(),
                    $this->method(),
                    $this->line()
                );
            }
    
            if ($this->debugger->getOption('app_level')) {
    
                $output_string .= $this->description() . ' ';
            }
    
            if ($this->debugger->getOption('timing')) {
    
                $output_string .= '(' . $this->time() . ')';
            }
    
            return $output_string;
        }
    }

    // ======================= 
    // ! Getters and Setters   
    // ======================= 

    /**
     * Returns a string of the name of the class that logged this event.
     * 
     * @return string
     */
    public function className()
    {
        return $this->class;
    }

    /**
     * Returns a string of the file that contains the code that logged
     * this event.
     * 
     * @return string
     */
    public function file()
    {
        return $this->file;
    }

    /**
     * Returns a string of the method that logged this event.
     * 
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Returns a client provided, human readable description of this event.
     * 
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Returns the time, in microseconds, when this event occured.
     * 
     * @return string
     * @see http://php.net/manual/en/function.microtime.php
     */
    public function time()
    {
        return $this->time;
    }

    /**
     * Returns the line of the code that generated this event.
     * 
     * @return int
     */
    public function line()
    {
        return $this->line;
    }

    /**
     * Returns an array of additional information, if any, was provided
     * along with this event
     *
     * @return array|null
     */
    public function arguments()
    {
        return $this->arguments;
    }

    // =========== 
    // ! Setters   
    // =========== 

    /**
     * Sets the debugger instance that will collect and track this event.
     * 
     * @param AIS_Debugger $debugger A reference to the root debugger object.
     *      Usually there will only be one of these per application.
     *
     * @return AIS_Debugger_Entry a refernece to the current object, to all
     *      for method chaining
     */
    public function setDebugger($debugger)
    {
        $this->debugger = $debugger;
    }

    /**
     * Stores an array of additional information / arguments that give context
     * to the event being logged (ex passed parameters, current user informaiton
     * etc.) 
     * 
     * @param array $arguments an array of additional information / arguments
     *
     * @return AIS_Debugger_Entry a refernece to the current object, to all
     *      for method chaining
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Application provided description of whats being debugged
     * 
     * @param string $description (default: false) An optional, human
     *      readable description of the event being logged.
     *
     * @return AIS_Debugger_Entry a refernece to the current object, to all
     *      for method chaining
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Populate the current debug item with information from an array
     * returned by debug_backtrace()
     * 
     * @param array $backtrace an array of backtrace information
     *
     * @return AIS_Debugger_Entry a refernece to the current object, to all
     *      for method chaining
     * @see http://php.net/manual/en/function.debug-backtrace.php
     */
    public function populateWithBacktrace($backtrace)
    {
        static $property_mapping = array(
            'class' => 'class',
            'function' => 'method',
            'line' => 'line',
            'file' => 'file',
        );

        foreach ($property_mapping as $backtrace_key => $local_property) {
        
            if (isset($backtrace[$backtrace_key])) {

                $this->$local_property = $backtrace[$backtrace_key];
            }
        }

        return $this;
    }

    /**
     * "Stamps" the current object with the current time, with microsecond
     * granularity.
     * 
     * @return AIS_Debugger_Entry a refernece to the current object, to all
     *      for method chaining
     */
    public function recordTime()
    {
        $this->time = microtime(true);
        return $this;
    }
}