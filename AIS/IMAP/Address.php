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
 * Instances of this class represent email addresses referenced in the message,
 * such as the message's sender, recipient, or people (B)CC'ed.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
class AIS_IMAP_Address
{
    /**
     * Holds the mailbox of the recipient of a message.
     * This is "snyderp" in the example "snyderp@example.com"
     *
     * @var string
     * @access protected
     */
    protected $mailbox;

    /**
     * Holds the host of the email recipient, the "example.com"
     * in "snyderp@example.com"
     *
     * @var string
     * @access protected
     */
    protected $host;

    /**
     * Holds a string describing the causual name of the email recipient,
     * "Peter Snyder" in "snyderp@example.com"
     *
     * @var string
     * @access protected
     */
    protected $name;

    /**
     * Conveniece initializer that allows for populating the
     * the object with an array containing values for one or more
     * of the following fields:
     *
     *  - 'mailbox' (string): The name of the mailbox at the given server.
     *                        In <Summa Person> person@example.org, this
     *                        would be 'person'.
     *  - 'host' (string):    The domain of the server receiving the email.
     *                        In <Summa Person> person@example.org, this is
     *                        'example.org'.
     *  - 'name' (string):    The full name of the person receiving the email.
     *                        In <Summa Person> person@example.org, this is
     *                        'Summa Person'.
     *
     * @param mixed $parts (default: false) Optionally takes an array
     *      contaning zero or more fields describing the message.
     *
     * @return void
     */
    public function __construct($parts = false)
    {
        $parts = (array)$parts;

        if (is_array($parts) && ! empty($parts)) {

            $this->mailbox = empty($parts['mailbox']) ? '' : $parts['mailbox'];

            $this->host = empty($parts['host']) ? '' : $parts['host'];

            $this->name = empty($parts['name']) ? '' : $parts['name'];
        }
    }

    // =======================
    // ! Getters and Setters
    // =======================

    /**
     * Returns the full email of the message recipient such as
     * person@example.org.
     *
     * @return string
     */
    public function emailAddress()
    {
        return $this->mailbox . '@' . $this->host;
    }

    /**
     * Returns a string describing the mailbox of the email recipeint
     *
     * @access public
     * @return string
     */
    public function mailbox()
    {
        return $this->mailbox;
    }

    /**
     * Returns a string describing the host of the email recipient
     *
     * @access public
     * @return string
     */
    public function host()
    {
        return $this->host;
    }

    /**
     * Returns the arbitrary, casual name for the email recipient, like
     * Joe Schmoe
     *
     * @access public
     * @return string
     */
    public function name()
    {
        return $this->name;
    }
}