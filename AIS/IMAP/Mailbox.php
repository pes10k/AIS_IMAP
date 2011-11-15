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
 * This class wraps around a connection to a single
 * IMAP mailbox using the native PHP IMAP string functions
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
class AIS_IMAP_Mailbox extends AIS_Debugable
{
    // ========================
    // ! Protected Properties
    // ========================

    /**
     * Reference to the root AIS_IMAP object that
     * spawned this mailbox connection
     *
     * @var AIS_IMAP
     * @access protected
     */
    protected $root_imap_connection;

    /**
     * The connected IMAP stream, once
     * established through the connect method
     *
     * @var resource
     * @access protected
     */
    protected $imap_stream;

    /**
     * Name of the mailbox in the IMAP stream
     * that object represents
     *
     * @var string
     * @access protected
     */
    protected $mailbox_name;

    /**
     * The string used to connect the IMAP mailbox
     *
     * @var string
     * @access protected
     */
    protected $imap_connection_description;

    /**
     * An array of currently available connections to
     * IMAP mailboxes that are children of the current one,
     * represented by AIS_IMAP_Mailbox objects
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $child_mailboxes = array();

    /**
     * Reference to the mailbox the current mailbox
     * resides in.  This will be empty if the current mailbox
     * is a parent level mailbox
     *
     * @var null|AIS_IMAP_Mailbox
     * @access protected
     */
    protected $parent_mailbox = null;

    /**
     * An array of AIS_IMAP_Email objects that represent
     * emails in the current mailbox
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $emails = array();

    // ==================
    // ! Public Methods
    // ==================

    /**
     * Class constructor isn't meant to be called directly, but only through
     * the AIS_IMAP class's openMailbox method.
     *
     * @param resource $imap_stream                 A PHP stream resource that
     *                                              connects to the IMAP folder
     * @param string   $mailbox_name                Simple name of the mailbox
     *                                              this connection represents
     * @param AIS_IMAP $root_imap_connection        A reference to the imap
     *                                              connection that this mailbox
     *                                              is being referenced over
     * @param string   $imap_connection_description The server specification
     *                                              as used with imap_open
     * @param mixed    $parent_mailbox              A refernece to the IMAP folder
     *                                              this folder is nested in, if
     *                                              one exists.
     *
     * @return void
     * @see http://www.php.net/manual/en/function.imap-open.php
     */
    public function __construct($imap_stream, $mailbox_name, $root_imap_connection, $imap_connection_description, $parent_mailbox = false)
    {
        $this->imap_stream = $imap_stream;
        $this->mailbox_name = $mailbox_name;
        $this->root_imap_connection = $root_imap_connection;
        $this->imap_connection_description = $imap_connection_description;

        if ($parent_mailbox) {

            $this->parent_mailbox = $parent_mailbox;
        }
    }

    /**
     * Returns an array of the names of the mailboxes in the current
     * connection that are children of the root mailbox
     * Raises an exception if no connection has been created yet.
     *
     * @access public
     * @return array
     */
    public function listChildMailboxes()
    {
        if ( ! $this->imap_stream) {

            $except_message = 'Cannot list mailboxes yet because no connection ';
            $except_message .= 'has been established yet';
            throw new Exception($except_message);

        } else {

            $mailboxes = imap_getmailboxes(
                $this->imap_stream,
                $this->imap_connection_description,
                '%'
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
     * Connects to a mailbox in the current IMAP Mailbox, represented by the
     * returned AIX_IMAP_Mailbox object.  Raises an exception if the current
     * IMAP connection doesn't recognize the provided mailbox name
     *
     * @param string $child_mailbox_name The name of a mailbox, in the current
     *                                   mailbox, to open
     *
     * @return AIS_IMAP_Mailbox
     */
    public function openChildMailbox($child_mailbox_name)
    {
        // First check to see that we don't already have an established
        // connection to this mailbox
        if (isset($this->child_mailboxes[$child_mailbox_name])) {

            return $this->child_mailboxes[$child_mailbox_name];

        } else {

            $mailbox_imap_stream = imap_open(
                $this->imap_connection_description.$child_mailbox_name,
                $this->imap_username,
                $this->imap_password
            );

            if ( ! $mailbox_imap_stream) {

                throw new Exception(
                    sprintf(
                        'Unable to connect to child mailbox "%s"',
                        $mailbox_name
                    )
                );

            } else {

                $IMAP_Mailbox = new AIS_IMAP_Mailbox(
                    $mailbox_imap_stream,
                    $child_mailbox_name,
                    $this->parentIMAPConnection(),
                    $this->imap_connection_description.$child_mailbox_name,
                    $this
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
     * Returns an array of all emails in the current mailbox.
     * Emails are by default sorted in order of most recent to least recent, but
     * can be sorted otherwise using any of the following keys described at
     * http://us2.php.net/manual/en/function.imap-sort.php
     *
     * @param constant $sort_order         A sorting option used in the imap_sort
     *                                     method.  Defaults to SORTARRIVAL
     * @param bool     $most_recent_first  Whether to order the most recent 
     *                                     messages first in the returned array.
     *                                     Defaults to true
     * @param bool     $mark_messages_read Whether to mark messages as read when
     *                                     reading them.  Defaults to false
     *
     * @return array
     * @see http://us2.php.net/manual/en/function.imap-sort.php
     */
    public function emails($sort_order = SORTARRIVAL, $most_recent_first = true, $mark_messages_read = false)
    {
        if (empty($this->emails)) {

            $reverse = ($most_recent_first) ? 1 : 0;

            $message_ids = imap_sort(
                $this->IMAPMailboxStream(),
                SORTARRIVAL,
                $reverse
            );

            $this->setDebugInformation(
                sprintf(
                    'Found "%d" messages in inbox "%s"',
                    count($message_ids),
                    $this->mailboxName()
                )
            );

            foreach ($message_ids as $message_index_ids) {

                $email = new AIS_IMAP_Email($this, $message_index_ids);

                if ($this->isInDebugMode()) {

                    $email->setDebugger($this->debugger);
                }

                $email->parseMessage($mark_messages_read);

                $this->emails[] = $email;
            }
        }

        return $this->emails;
    }

    /**
     * Deletes all messages in the current mailbox marked for deletion by
     * either being deleted directly, or moved out of the current mailbox
     *
     * @access public
     * @return bool
     */
    public function expunge()
    {
        return imap_expunge($this->imap_stream);
    }

    /**
     * Returns the index number of the given email in the current mailbox.
     * This number is the position of the email in the mailbox, with 1 being
     * the first position
     *
     * @param AIS_IMAP_Email $email A reference to an email object in the
     *                              current mailbox
     *
     * @return int|bool
     */
    public function indexOfEmail(AIS_IMAP_Email $email)
    {
        foreach ($this->emails() as $item) {

            if ($item->uniqueMessageIdentifier() === $email->uniqueMessageIdentifier()) {

                return $item->indexId();
            }
        }

        return 1;
    }

    // ===========
    // ! Getters
    // ===========

    /**
     * Returns the name of the IMAP Mailbox repersented by the current object
     *
     * @access public
     * @return string
     */
    public function mailboxName()
    {
        return $this->mailbox_name;
    }

    /**
     * Returns the PHP IMAP stream resouce that this object represents
     *
     * @access public
     * @return resource
     */
    public function IMAPMailboxStream()
    {
        return $this->imap_stream;
    }

    /**
     * Returns a reference to the root AIS_IMAP object that points to the root
     * AIS mailbox that contains this folder
     *
     * @access public
     * @return void
     */
    public function rootIMAPConnection()
    {
        return $this->root_imap_connection;
    }

    /**
     * Returns the AIS_IMAP_Mailbox object that contains the current folder.
     * Will return null if this mailbox is a 1st level mailbox
     *
     * @access public
     * @return null|AIS_IMAP_Mailbox
     */
    public function parentMailbox()
    {
        return (isset($this->parent_mailbox)) ? $this->parent_mailbox : null;
    }
}