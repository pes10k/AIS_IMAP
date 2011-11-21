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
 *
 */

/**
 * AIS_IMAP objects represent and wrap connections to IMAP servers.
 * Each server can contain zero or more mailboxes.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 * @extends  AIS_Debugable
 */
class AIS_IMAP extends AIS_Debugable
{
    // ========================
    // ! Protected Properties
    // ========================

    /**
     * The connected IMAP stream, once
     * established through the connect method
     *
     * @var resource|null
     * @access protected
     */
     protected $imap_stream = null;

    /**
     * The string used to connect the IMAP host
     *
     * @var string
     * @access protected
     */
    protected $imap_connection_description;

    /**
     * The port to connect to the the IMAP server
     *
     * (default value: 143)
     *
     * @var int
     * @access protected
     */
    protected $imap_port = 143;

    /**
     * The host of the IMAP server to connect to
     *
     * (default value: 'localhost')
     *
     * @var string
     * @access protected
     */
    protected $imap_host = 'localhost';

    /**
     * Password to use when connecting to the IMAP server
     *
     * (default value: '')
     *
     * @var string
     * @access protected
     */
    protected $imap_password = '';

    /**
     * Username to use when connecting the the IMAP server
     *
     * (default value: '')
     *
     * @var string
     * @access protected
     */
    protected $imap_username = '';

    /**
     * An array of currently available connections to
     * contained IMAP mailboxes, represented by AIS_IMAP_Mailbox
     * objects
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $current_mailbox_connections = array();

    /**
     * The type of connection being used.  This is one of the connection
     * protocols supported by the IMAP extensions (either 'pop3' or 'imap').
     *
     * (default value: 'imap')
     *
     * @var string
     * @access protected
     */
    protected $imap_connection_type = 'imap';

    /**
     * Whether to require a secure connection.  Defaults to false,
     * since many hosts do not support this option.
     *
     * (default value: false)
     *
     * @var bool
     * @access protected
     */
    protected $imap_use_secure_connection = false;

    // =================
    // ! Pubic Methods
    // =================

    /**
     * Class constructor allows for opitonal passing an array of
     * connection parameters that will be set with the setConnectionParameters
     * method
     *
     * @param bool|array $params (default: FALSE) An array of connection parameters
     *    for connecting to the IMAP server.
     *
     * @return void
     * @see setConnectionParameters()
     */
    public function __construct($params = false)
    {
        if ( ! empty($params) && is_array($params)) {

            $this->setConnectionParameters($params);
        }
    }

    /**
     * Method to allow for setting multiple connection parameters
     * with an array.  The array can have one or more of the following
     * keys:
     *  - password
     *  - username
     *  - host
     *  - port
     *  - connection type: either 'imap' or 'pop3'.  Defaults to 'imap'
     *  - secure: a boolean value of whether to connect securely.  Defaults to
     *      'false'  
     *
     * @param array $params a list of connection parameters to use when connecting
     *    to the IMAP server.  The valid parameters are listed above
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setConnectionParameters($params)
    {
        $possible_params = array(
            'password'  =>  'setPassword',
            'username'  =>  'setUsername',
            'host'      =>  'setHost',
            'port'      =>  'setPort',
            'type'      =>  'setConnectionType',
            'secure'    =>  'setUseSecureConnection',
        );

        foreach ($possible_params as $param_key => $setter_name) {

            if ( ! empty($params[$param_key])) {

                $this->$setter_name($params[$param_key]);
            }
        }

        return $this;
    }

    /**
     * Attemps to connect to the IMAP server, given the provided connection
     * parameters.  Returns the result of the PHP imap_open function, which
     * will either be a valid stream resouce or an error
     *
     * @access public
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function connect()
    {
        $this->imap_connection_description = sprintf(
            '{%s:%s%s%s%s}',
            $this->imap_host,
            $this->imap_port,
            ($this->imap_connection_type === 'pop3') ? '/pop3' : '/imap',
            $this->imap_use_secure_connection ? '/ssl' : '',
            $this->isInDebugMode() ? '/debug' : ''
        );

        $this->setDebugInformation(
            'attempting to connect to IMAP mailbox',
            array(
                'imap_connection_description' => $this->imap_connection_description,
                'imap_username' => $this->imap_username,
                'imap_password' => $this->imap_password,
            )
        );

        $this->imap_stream = imap_open(
            $this->imap_connection_description,
            $this->imap_username,
            $this->imap_password
        );

        if ($this->imap_stream) {

            $this->setDebugInformation('Successfully connected to mailbox');

        } else {

            $this->setDebugInformation('Unable to connect to the mailbox');
        }

        return $this;
    }

    /**
     * Returns a boolean description of whether this object currently
     * represents a live, valid connection to an mail server.
     *
     * @return true if the object is connected to a server, otherwise false
     */
    public function isConnected()
    {
        return ! empty($this->imap_stream);
    }

    /**
     * Returns an array of the names of the mailboxes in the current
     * connection, at all depths
     * Raises an exception if no connection has been created yet.
     *
     * @return array An array of strings, each of which are the name of a mailbox
     *    the current connection has access to.
     */
    public function listAllMailboxes()
    {
        return $this->listMailboxes(true);
    }

    /**
     * Returns an array of the names of the mailboxes in the current
     * connection that are children of the root mailbox
     * Raises an exception if no connection has been created yet.
     *
     * @return array An array of strings, each of which are the name of a mailbox
     *    in the root directory that the current connection has access to.
     */
    public function listChildMailboxes()
    {
        return $this->listMailboxes(false);
    }

    /**
     * Connects to a mailbox in the current IMAP string, represented by the
     * returned AIX_IMAP_Mailbox object.  Raises an exception if the current
     * IMAP connection doesn't recognize the provided mailbox name
     *
     * @param string $mailbox_name The name of the inbox to open (ex. INBOX)
     *
     * @return AIS_IMAP_Mailbox
     */
    public function openMailbox($mailbox_name)
    {
        // First check to see that we don't already have an established
        // connection to this mailbox
        if (isset($this->current_mailbox_connections[$mailbox_name])) {

            return $this->current_mailbox_connections[$mailbox_name];

        } else {

            include_once 'IMAP/Mailbox.php';

            $this->setDebugInformation(
                'Attempting to open mailbox "' . $mailbox_name . '"',
                array(
                    'mailbox_name' => $mailbox_name,
                    'imap_username' => $this->imap_username,
                    'imap_password' => $this->imap_password,
                )
            );

            $mailbox_imap_stream = imap_open(
                $this->imap_connection_description . $mailbox_name,
                $this->imap_username,
                $this->imap_password
            );

            if ( ! $mailbox_imap_stream) {

                $excep_message = sprintf(
                    'Unable to connect to mailbox "%s"',
                    $mailbox_name
                );

                throw new Exception($excep_message);

            } else {

                $debug_message = sprintf(
                    'Successfully connected to mailbox "%s"',
                    $mailbox_name
                );

                $this->setDebugInformation($debug_message);

                $IMAP_Mailbox = new AIS_IMAP_Mailbox(
                    $mailbox_imap_stream,
                    $mailbox_name,
                    $this,
                    $this->imap_connection_description.$mailbox_name
                );

                if ($this->isInDebugMode()) {

                    $IMAP_Mailbox->setDebugger($this->debugger);
                }

                $this->current_mailbox_connections[$mailbox_name] = $IMAP_Mailbox;

                return $IMAP_Mailbox;
            }
        }
    }

    /**
     * Closes the current connection to the IMAP mailbox.  If
     * $expunge is set to true, all messages marked for deletion
     * will me deleted from the mailbox
     *
     * @param mixed $expunge (default: false)  Whether to delete
     *    all messages marked for deletion on closing the mailbox
     *
     * @return bool
     */
    public function close($expunge = false)
    {
        $rs = ($expunge)
            ? imap_close($this->imap_stream)
            : imap_close($this->imap_stream, CL_EXPUNGE);

        if ($rs) {

            $this->imap_stream = null;
        }

        return $rs;
    }

    // =====================
    // ! Protected Methods
    // =====================

    /**
     * Returns an array of strings of the names of the mailboxes in the
     * current IMAP connection, either just 1st level / child mailboxes,
     * or all mailboxes
     *
     * @param boolean $only_children Either true to return just mailboxes in the
     *    root of the mailbox, or any other value to return mailboxes.   Defaults
     *    to true.
     *
     * @return array An array of strings, each of which are the name of a mailbox
     *    the current connection has access to.
     */
    protected function listMailboxes($only_children = true)
    {
        if ( ! $this->imap_stream) {

            $excep_message = 'Cannot list mailboxes.  ';
            $excep_message .= 'No connection has been established yet.';
            throw new Exception($excep_message);

        } else {

            $mailbox_flag = ($only_children === true) ? '%' : '*';

            $mailboxes = imap_getmailboxes(
                $this->imap_stream,
                $this->imap_connection_description,
                $mailbox_flag
            );

            // We want to return just the names of the mailboxes,
            // so we strip out the connection description string from
            // each one first
            $mailbox_names = array();

            foreach ($mailboxes as $item) {

                $mailbox_names[] = str_replace(
                    $this->imap_connection_description,
                    '',
                    $item->name
                );
            }

            return $mailbox_names;
        }
    }

    /**
     * Deletes all messages in the current mailbox marked for deletion by
     * either being deleted directly, or moved out of the current mailbox
     *
     * @return bool
     */
    public function expunge()
    {
        return imap_expunge($this->imap_stream);
    }

    // ===========
    // ! Setters
    // ===========

    /**
     * Set the password used to connect to the IMAP server.
     *
     * @param string $password The password of the IMAP account
     *    being connected to.
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setPassword($password)
    {
        $this->imap_password = $password;
        return $this;
    }

    /**
     * Set the username used to connect to the IMAP server.
     *
     * @param string $username The user name for the IMAP account
     *   being connected to.
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setUsername($username)
    {
        $this->imap_username = $username;
        return $this;
    }

    /**
     * Set the port used to connect to the IMAP server.
     *
     * @param int $port the TCP port to connect to the IMAP server at.
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setPort($port)
    {
        $this->imap_port = $port;
        return $this;
    }

    /**
     * Set the host used to connect to the IMAP server.
     *
     * @param string $host the host / domain that the IMAP server
     *    is located at
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setHost($host)
    {
        $this->imap_host = $host;
        return $this;
    }

    /**
     * Set the type of connection being used (IMAP or POP3)
     *
     * @param string $type the type of server being connected to.
     *      If 'pop3' is passed, POP3 is used.  Otherwise uses
     *      'imap'
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setConnectionType($type)
    {
        $this->imap_connection_type = (strtolower($type) === 'pop3')
            ? 'pop3'
            : 'imap';

        return $this;
    }

    /**
     * Set whether to use SSL/TSL when connecting to the mail server
     *
     * @param bool $use_secure_connection true if a secure connection
     *      to the mail server should be attempted.  Otherwise false.
     *
     * @return AIS_IMAP Returns the current object for chaining
     */
    public function setUseSecureConnection($use_secure_connection)
    {
        $this->imap_use_secure_connection = !! $use_secure_connection;
        return $this;
    }

    // ===========
    // ! Getters
    // ===========

    /**
     * Returns the IMAP streem resource being represented / wrapped
     * by the current object.
     *
     * @return NULL|resource stream to IMAP server
     * @see http://www.php.net/manual/en/function.imap-open.php
     */
    public function IMAPStream()
    {
        return $this->imap_stream;
    }

    /**
     * Returns the IMAP connection description string, in
     * the format of {imap.example.com}
     *
     * @return string
     */
    public function IMAPConnectionDescription()
    {
        return $this->imap_connection_description;
    }
}