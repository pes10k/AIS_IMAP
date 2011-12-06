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
 * Instances of this class represent email messages in an IMAP mailbox.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
class AIS_IMAP_Email extends AIS_Debugable
{
    // ===================
    // ! Class Constants
    // ===================

    // Class constants that describe the status of whether the email is
    // recent or not, and whether its been seen or not
    const RECENT_AND_UNSEEN = 0;
    const RECENT_AND_SEEN = 1;
    const NOT_RECENT = 2;

    // A end point in the internal structure that describes
    // the email
    const TYPE_PLAIN = 0;
    const TYPE_MULTIPART = 1;
    const TYPE_RFC822 = 2;
    const TYPE_APPLICATION = 3;
    const TYPE_IMAGE = 5;

    // Code friendly ways of tracking the priority of the email
    const PRIORITY_LOW = 3;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 1;

    // ========================
    // ! Protected Properties
    // ========================

    /**
     * Reference to the mailbox that contains the current email
     *
     * @var AIS_IMAP_Mailbox
     * @access protected
     */
    protected $mailbox;

    /**
     * Integer used within the php imap functions
     * to refer to a particular message within a mailbox.
     * Note that this is not the unique message identifier
     * that is contained in the header of the an email
     *
     * @var int
     * @access protected
     */
    protected $index_id;

    /**
     * Contains a string representation of the body of the
     * message that has been marked by the sender as the
     * plain, non-HTML content
     *
     * @var string
     * @access protected
     */
    protected $body_plain;

    /**
     * Contains a string representation of the body of the
     * message that has been marked by the sender as
     * HTML content
     *
     * @var string
     * @access protected
     */
    protected $body_html;

    /**
     * An array of AIS_IMAP_Attachment objects that
     * describe all of the attachements to the current email
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $attachments = array();

    /**
     * A string that globally uniquely identiifies an email, usually
     * in the form of a global UID and then the host
     *
     * @var string
     * @access protected
     */
    protected $unique_message_identifier;

    /**
     * An array of AIS_IMAP_Address objects
     * describing all recipents of this email
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $to_addresses = array();

    /**
     * A AIS_IMAP_Address object
     * describing the addresses this email was sent from
     *
     * @var AIS_IMAP_Address
     * @access protected
     */
    protected $from_address;

    /**
     * An array of AIS_IMAP_Address objects
     * describing the addresses this email was CC'ed to
     *
     * (default value: array())
     *
     * @var array
     * @access protected
     */
    protected $cc_addresses = array();

    /**
     * String describing the subject of the email
     *
     * @var string
     * @access protected
     */
    protected $subject;

    /**
     * A DateTime object representing the date the email
     * received at
     *
     * @var DateTime
     * @access protected
     */
    protected $recevied_date;

    /**
     * A DateTime object representing the date the email was
     * sent at
     *
     * @var DateTime
     * @access protected
     */
    protected $sent_date;

    /**
     * A constant describing whether the current email is recent,
     * as determined by the local IMAP implementation.  Contains
     * one of the following values constants
     *  - AIS_IMAP_Email::RECENT_AND_UNSEEN
     *  - AIS_IMAP_Email::RECENT_AND_SEEN
     *  - AIS_IMAP_Email::NOT_RECENT
     *
     * @var integer
     * @access protected
     */
    protected $is_recent;

    /**
     * A boolean describing whether the current email has been seen
     * before.  Note that this can be misleading if we've viewed the email
     * before in "peek" mode
     *
     * @var bool
     * @access protected
     */
    protected $is_unseen;

    /**
     * A boolean describing whether or not this email has been responded to
     *
     * @var bool
     * @access protected
     */
    protected $is_answered;

    /**
     * A boolean value describing whether or not this email has been flagged
     *
     * @var bool
     * @access protected
     */
    protected $is_flagged;

    /**
     * A boolean descibing whether or not the email is a draft
     *
     * @var bool
     * @access protected
     */
    protected $is_draft;

    /**
     * The email GUID that the current email is replying to, if one exists.
     * This property will be empty if the current email is not replying to
     * another email
     *
     * @var string
     * @access protected
     */
    protected $in_reply_to;

    /**
     * An array of zero or more email GUIDs of messages this email is responding
     * to. These will be in order of most recent to least recent emails, so the
     * GUID at position 0, if one exists, will be idential to the GUID of the
     * email this message is replying to
     *
     * @var array
     * @access protected
     */
    protected $references = array();

    /**
     * The numeric priority of the email, which coresponds to one of the following
     * class constants
     *
     * - PRIORITY_LOW
     * - PRIORITY_MEDIUM
     * - PRIORITY_HIGH
     *
     * @var int
     * @access protected
     */
    protected $priority;

    /**
     * A boolean description of whether the email's body has been parsed
     * and built into HTML, plain and attachment sections.
     *
     * @var bool
     * @access protected
     */
    protected $body_has_been_built = false;

    /**
     * A boolean description of whether the message was opened
     * as a peak.  If so, when the message's body is parsed,
     * the message will remain marked as "unread".
     *
     * @var bool
     * @access protected
     */
    protected $opened_as_peek = false;

    // ==================
    // ! Public Methods
    // ==================

    /**
     * Constructor function automatically associates the object with the
     * parent mailbox, along with the index_id of where the current message
     * sits in the parent mailbox
     *
     * @param AIS_IMAP_Mailbox $mailbox  A reference to the mailbox that this
     *                                   message exists in.
     * @param int              $index_id The position of this message in its
     *                                   containing mailbox.
     *
     * @return void
     */
    public function __construct($mailbox, $index_id)
    {
        $this->mailbox = $mailbox;
        $this->index_id = $index_id;
    }

    /**
     * Creates the internal structure of the email
     * and populates the various proprties of the object.
     *
     * @param bool $peek (default: false) Whether to leave the message unread.
     *
     * @return bool
     */
    public function parseMessage($peek = false)
    {
        include_once 'Address.php';

        if ( ! $this->mailbox) {

            throw new Exception('No IMAP stream to read from');

        } else if ( ! is_numeric($this->index_id)) {

            throw new Exception('No message id to pull from stream');

        } else {

            $this->setDebugInformation(
                sprintf(
                    '[%d] Beginning parsing of message in mailbox "%s"',
                    $this->index_id,
                    $this->mailbox()->mailboxName()
                ),
                array(
                    'mailbox' => $this->mailbox()->mailboxName(),
                    'id' => $this->index_id,
                )
            );

            $mailbox_stream = $this->mailbox()->IMAPMailboxStream();

            $this->opened_as_peek = $peek;

            $header_data = imap_headerinfo($mailbox_stream, $this->index_id);

            // Attempting to find the subject of the email message
            if (empty($header_data->subject)) {

                $this->subject = '';
                $this->setDebugInformation(
                    sprintf(
                        '[%d] No subject found for message.  Defaulting to ""',
                        $this->index_id
                    )
                );

            } else {

                $this->subject = $header_data->subject;
                $this->setDebugInformation(
                    sprintf(
                        '[%d] Found subject of "%d" for message.',
                        $this->index_id,
                        $header_data->subject
                    )
                );
            }

            // The PHP imap_headerinfo function does not catch the priority of
            // the email, so we need to scrape it out of the raw header text.

            $raw_header = imap_fetchheader($mailbox_stream, $this->index_id);

            // Attempting to find the priority of the message
            $priority_pattern = '/X-Priority: ([0-9])/';
            $priority_matches = array();

            if ( ! preg_match($priority_pattern, $raw_header, $priority_matches)) {

                $this->priority = self::PRIORITY_MEDIUM;
                $this->setDebugInformation(
                    sprintf(
                        '[%d] No priority set for message.  Defaulting to "%s"',
                        $this->index_id,
                        self::PRIORITY_MEDIUM
                    )
                );

            } else {

                $this->priority = $priority_matches[1];
                $this->setDebugInformation(
                    sprintf(
                        '[%d] Found priority of "%s" for message.',
                        $this->index_id,
                        $priority_matches[1]
                    ),
                    array(
                        'regex_match' => $priority_matches,
                    )
                );
            }

            $this->in_reply_to = empty($header_data->in_reply_to)
                ? null
                : $header_data->in_reply_to;

            if ( ! empty($header_data->references)) {

                $this->references = array_reverse(
                    explode(' ', $header_data->references)
                );
            }

            $header_string = imap_fetchheader($mailbox_stream, $this->index_id);

            if ( ! empty($header_string)) {

                $rfc822_headers = imap_rfc822_parse_headers($header_string);

                $no_reply_to_headers = (empty($rfc822_headers->in_reply_to) &&
                    empty($this->in_reply_to));

                $this->in_reply_to = ($no_reply_to_headers)
                    ? null
                    : $rfc822_headers->in_reply_to;

                if ( ! empty($rfc822_headers->references)) {

                    $referring_mail_ids = explode(' ', $rfc822_headers->references);

                    foreach (array_reverse($referring_mail_ids) as $message_id) {

                        $this->references[] = $message_id;
                    }
                }
            }

            // First populate the to and cc porperties of the object
            // with AIS_IMAP_Address objects

            foreach (array('to', 'cc',) as $item) {

                if ( ! empty($header_data->$item)) {

                    $local_property = $item.'_addresses';

                    foreach ($header_data->$item as $email_recipient_data) {

                        $address = new AIS_IMAP_Address($email_recipient_data);
                        array_push($this->$local_property, $address);
                    }
                }
            }

            if ( ! empty($header_data->from)) {

                $this->from_address = new AIS_IMAP_Address($header_data->from[0]);
            }

            // Next, populate the email with information about when the email was
            // sent and received
            if (is_numeric($header_data->udate)) {

                $this->received_date = new DateTime();
                $this->received_date->setTimestamp($header_data->udate);

            } else {

                $this->received_date = new DateTime($header_data->udate);
            }

            $this->sent_date = new DateTime($header_data->date);

            // Next, there are several binary values that we can determine
            // by just checking for the presence or absence of an value
            $boolean_values = array(
                'Unseen'    =>  'is_unseen',
                'Flagged'   =>  'is_flagged',
                'Answered'=>    'is_answered',
                'Deleted'   =>  'is_deleted',
                'Draft'     =>  'is_draft',
            );

            foreach ($boolean_values as $header_value => $local_property) {

                $this->$local_property = ( ! empty($header_data->$header_value));
            }

            if ($header_data->Recent === 'R') {

                $this->is_recent = AIS_IMAP_Email::RECENT_AND_SEEN;

            } elseif ($header_data->Recent === 'N') {

                $this->is_recent = AIS_IMAP_Email::RECENT_AND_UNSEEN;

            } else {

                $this->is_recent = AIS_IMAP_Email::NOT_RECENT;
            }

            $this->unique_message_identifier = $header_data->message_id;
        }
    }

    /**
     * Marks the current message for deletion.  This does not actually
     * delete the given message, but only marks it for deletion.  To
     * delete the message, call AIS_IMAP_Mailbox::expunge on the mailbox
     * that contains this message
     *
     * @return bool
     */
    public function delete()
    {
        $rs = imap_delete($this->mailbox()->IMAPMailboxStream(), $this->index_id);

        if ($rs) {

            $this->is_deleted = true;
        }

        return $rs;
    }

    /**
     * Unmarks the current email for deletion
     *
     * @return bool
     */
    public function undelete()
    {
        $rs = imap_undelete($this->mailbox()->IMAPMailboxStream(), $this->index_id);

        if ($rs) {

            $this->is_deleted = false;
        }

        return $rs;
    }

    /**
     * Moves the current message to the mailbox described by
     * $mailbox.  This actually copies the message to the given
     * IMAP mailbox, and marks the original email for deletion.
     * To complete the process, mark
     *
     * @param AIS_IMAP_Mailbox $mailbox A reference to the IMAP mailbox
     *      the current message should be moved to.
     *
     * @return bool
     */
    public function moveToMailbox(AIS_IMAP_Mailbox $mailbox)
    {
        $rs = imap_mail_move(
            $mailbox->rootIMAPConnection()->IMAPStream(),
            $this->index_id,
            $mailbox->mailboxName()
        );

        // If the move failed, return false and stop processing.  Otherwise,
        // delete the email from the old mailbox and
        // reconfigure the object to point to the new version of the email
        // in the new mailbox
        if ($rs === true) {

            $this->delete();
            $this->index_id = $mailbox->indexOfEmail($this);
            $this->mailbox = $mailbox;
            $this->undelete();
        }

        return $rs;
    }

    // ===================
    // ! Private Methods
    // ===================

    /**
     * Examines the current email message, extracts the email messasge text,
     * and locates the message's attachments.
     *
     * @param mixed $parts             An optional reference to a
     *                                 subsection of the email message
     *                                 structure.  If called with false,
     *                                 the message is called with the root
     *                                 of the message, and then recursivly
     *                                 called to find all the child sections.
     *                                 This parameter should be one of the
     *                                 objects given from the 'parts'
     *                                 property of the object returned by
     *                                 imap_fetchstructure()
     * @param mixed $index             An optional index to indicate where in
     *                                 current heirachy of the email message
     *                                 the method is recursing through.
     * @param bool  $processing_rcf822 Flag to note whether the message is
     *                                 RCF-822 encoded
     *
     * @return void
     * @see http://www.php.net/manual/en/function.imap-fetchstructure.php
     * @see http://www.faqs.org/rfcs/rfc822.html
     */
    protected function buildBodyContent($parts = false, $index = false, $processing_rcf822 = false)
    {
        if ($parts) {

            $this->setDebugInformation(
                sprintf(
                    '[%d] Beginning to parse body part %d',
                    $this->index_id,
                    $index
                )
            );

        } else {

            $this->setDebugInformation(
                sprintf(
                    '[%d] Beginning to parse the message body',
                    $this->index_id
                )
            );

            $structure = imap_fetchstructure(
                $this->mailbox()->IMAPMailboxStream(),
                $this->index_id
            );

            if (empty($structure->parts)) {

                $body_portion = imap_fetchbody(
                    $this->mailbox()->IMAPMailboxStream(),
                    $this->index_id,
                    1,
                    FT_PEEK
                );

                $this->body_plain .= $body_portion;

                $this->setDebugInformation(
                    sprintf(
                        '[%d] Message appears to only have PLAIN_TEXT content',
                        $this->index_id
                    )
                );

                return;

            } else {

                $parts = $structure->parts;
                $this->setDebugInformation(
                    sprintf(
                        '[%d] Message contains "%d" parts',
                        $this->index_id,
                        count($parts)
                    )
                );
            }
        }

        foreach ($parts as $part_index => $part) {

            $subpart_type = strtoupper($part->subtype);

            if ($processing_rcf822) {

                $current_part_string = $this->incrementEmailPartSub($index);

            } else {

                $current_part_string = ($index)
                    ? $index.'.'.($part_index + 1)
                    : ($part_index + 1);
            }

            // Iterate through all the parts of the current node of the
            // email.  If its of type plain, that means we're at a content
            // node and should append the content to either the plain or
            // html body string.
            if ($part->type === self::TYPE_PLAIN) {

                $this->setDebugInformation(
                    sprintf(
                        '[%d] [%d] Body portion is type "TYPE_PLAIN"',
                        $this->index_id,
                        $index
                    )
                );

                $body_portion = imap_fetchbody(
                    $this->mailbox()->IMAPMailboxStream(),
                    $this->index_id,
                    $current_part_string,
                    FT_PEEK
                );

                $add_section = ($subpart_type === 'PLAIN')
                    ? 'body_plain'
                    : 'body_html';

                $this->$add_section .= $body_portion;

            } elseif ($part->type === self::TYPE_IMAGE OR
                      $part->type === self::TYPE_APPLICATION) {

                if ($part->type === self::TYPE_IMAGE) {

                    $this->setDebugInformation(
                        sprintf(
                            '[%d] [%d] Body portion is type "IMAGE"',
                            $this->index_id,
                            $index
                        )
                    );

                } else {

                    $this->setDebugInformation(
                        sprintf(
                            '[%d] [%d] Body portion is type "APPLICATION"',
                            $this->index_id,
                            $index
                        )
                    );
                }

                $body_portion = imap_fetchbody(
                    $this->mailbox()->IMAPMailboxStream(),
                    $this->index_id,
                    $current_part_string,
                    FT_PEEK
                );

                $attachment = new AIS_IMAP_Attachment(
                    $this,
                    $part,
                    $body_portion
                );

                $this->attachments[] = $attachment;

                if ($this->isInDebugMode()) {

                    $attachment->setDebugger($this->debugger);
                }

            } elseif ($part->type === self::TYPE_MULTIPART) {

                $this->setDebugInformation(
                    sprintf(
                        '[%d] [%d] Body portion is type "TYPE_MULTIPART"',
                        $this->index_id,
                        $index
                    )
                );

                if (isset($part->parts)) {

                    $this->buildBodyContent($part->parts, ($index + 1));
                }

            } elseif ($part->type === self::TYPE_RFC822) {

                $this->setDebugInformation(
                    sprintf(
                        '[%d] [%d] Body portion is type "TYPE_RFC822"',
                        $this->index_id,
                        $index
                    )
                );

                if ( ! $processing_rcf822) {

                    if (isset($part->parts)) {

                        $this->buildBodyContent($parts, $current_part_string, true);
                    }

                    return;
                }
            }
        }
    }

    /**
     * Increments an index string describing a position in an email body.
     * IMAP tracks portions of the message with nested indexes like 1.3.2
     * or 5.3.6.2, which are difficult to increment.  This method increments
     * the least significant digit by one, and returns a string representing
     * that new value (ie 1.3.2 -> 1.3.3)
     *
     * @param string $index_string the current index string to increment.
     *
     * @return string
     */
    protected function incrementEmailPartSub($index_string)
    {
        $index_of_last_decimal = strrpos($index_string, '.');

        if ($index_of_last_decimal === false) {

            $index_string = $index_string.'.0';
            $index_of_last_decimal = strlen($index_string) - 2;
        }

        $last_decimal = substr($index_string, ($index_of_last_decimal + 1));

        return sprintf(
            '%s.%d',
            substr($index_string, 0, $index_of_last_decimal),
            ((int)$last_decimal + 1)
        );
    }

    /**
     * The first time this message is called, it will build the contents of the
     * email message, including the plain text body, HTML body, and attachments
     * and return true.  All subsequent calls will do nothing and return false.
     *
     * @access protected
     * @return bool
     */
    protected function buildBodyIfNeeded()
    {
        if ($this->body_has_been_built) {

            return false;

        } else {

            $body = ($this->opened_as_peek)
                ? imap_body($this->mailbox()->IMAPMailboxStream(), $this->index_id, FT_PEEK)
                : imap_body($this->mailbox()->IMAPMailboxStream(), $this->index_id);

            $this->buildBodyContent();
            $this->body_has_been_built = true;

            return true;
        }
    }

    // ===========
    // ! Getters
    // ===========

    /**
     * Returns the index of the current message in the
     * containing PHP imap stream
     *
     * @access public
     * @return integer
     */
    public function indexId()
    {
        return $this->index_id;
    }

    /**
     * Returns the AIS_IMAP_Mailbox object that represents the mailbox
     * that contains this email.
     *
     * @access public
     * @return AIS_IMAP_Mailbox|NULL
     */
    public function mailbox()
    {
        return $this->mailbox;
    }

    /**
     * Returns a string that globally uniquely identitfies
     * the email, usually in the form of <{guid}@{host}>
     *
     * @access public
     * @return string
     */
    public function uniqueMessageIdentifier()
    {
        return $this->unique_message_identifier;
    }

    /**
     * Returns an array of AIS_IMAP_Address objects
     * that describe all of the recipients of the message
     *
     * @access public
     * @return array
     */
    public function toAddresses()
    {
        return $this->to_addresses;
    }

    /**
     * Returns an AIS_IMAP_Address object
     * that describe the email address the message was sent from
     *
     * @access public
     * @return AIS_IMAP_Address
     */
    public function fromAddress()
    {
        return $this->from_address;
    }

    /**
     * Returns an array of AIS_IMAP_Address objects
     * that describe all email addresses that were cc'ed
     *
     * @access public
     * @return array
     */
    public function ccAddresses()
    {
        return $this->cc_addresses;
    }

    /**
     * Returns a DateTime object that
     * describes when the email was sent by its author
     *
     * @access public
     * @return {Time
     */
    public function sentDate()
    {
        return $this->sent_date;
    }

    /**
     * Returns a unix timestamp represention of the
     * sent date
     *
     * @access public
     * @return int
     */
    public function sentDateUnixTimestamp()
    {
        return $this->sent_date->getTimestamp();
    }

    /**
     * Returns a DateTime object that describes
     * when the email was received by this server
     *
     * @access public
     * @return DateTime
     */
    public function receivedDate()
    {
        return $this->received_date;
    }

    /**
     * Returns a unix timestamp represention of the
     * received date
     *
     * @access public
     * @return int
     */
    public function receivedDateUnixTimestamp()
    {
        return $this->received_date->getTimestamp();
    }

    /**
     * Returns a boolean value describing whether the email has not yet
     * been viewed
     *
     * @access public
     * @return boolean
     */
    public function isUnseen()
    {
        return $this->is_unseen;
    }

    /**
     * Returns a boolean value describing whether the email has been flagged
     *
     * @access public
     * @return boolean
     */
    public function isFlagged()
    {
        return $this->is_flagged;
    }

    /**
     * Returns a boolean value describing whether the email has been responded to
     *
     * @access public
     * @return boolean
     */
    public function isAnswered()
    {
        return $this->is_answered;
    }

    /**
     * Returns a boolean value describing whether the email has been
     * marked as deleted.
     *
     * @access public
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * Returns a boolean value describing whether the email has been
     * marked as a draft.
     *
     * @access public
     * @return boolean
     */
    public function isDraft()
    {
        return $this->is_draft;
    }

    /**
     * Returns a boolean value describing whether the email is recent.
     * Possible values are the folloing class constants
     *  - AIS_EmaiL::RECENT_AND_UNSEEN
     *  - AIS_EmaiL::RECENT_AND_SEEN
     *  - AIS_EmaiL::NOT_RECENT
     *
     * @access public
     * @return integer
     */
    public function isRecent()
    {
        return $this->is_recent;
    }

    /**
     * Returns the subject of the email
     *
     * @access public
     * @return string
     */
    public function subject()
    {
        return $this->subject;
    }

    /**
     * An array of AIS_IMAP_Attachment objects
     *
     * @access public
     * @return array
     */
    public function attachments()
    {
        $this->buildBodyIfNeeded();

        return $this->attachments;
    }

    /**
     * Returns an un-encoded version of the body of the current message.
     * Note that this doesn't include the attachments or included messages
     * from replying.
     *
     * @access public
     * @return string
     * @see http://php.net/manual/en/function.imap-body.php
     */
    public function rawBody()
    {
        return imap_body($this->mailbox()->IMAPMailboxStream(), $this->indexId());
    }

    /**
     * A plain text version of the text of the body
     *
     * @access public
     * @return string
     */
    public function plainBody()
    {
        $this->buildBodyIfNeeded();

        return quoted_printable_decode($this->body_plain);
    }

    /**
     * A HTML version of the email body
     *
     * @access public
     * @return string
     */
    public function htmlBody()
    {
        $this->buildBodyIfNeeded();

        return $this->body_html;
    }

    /**
     * Returns an array of zero or more email GUIDs,
     * in order of most to least recent emails
     *
     * @access public
     * @return array
     */
    public function references()
    {
        return $this->references;
    }

    /**
     * Returns the GUID of the email the current email was
     * a reply to, or an empty string if the email is not a reply
     *
     * @access public
     * @return string
     */
    public function inReplyToUniqueMessageIdentifier()
    {
        return empty($this->in_reply_to) ? '' : $this->in_reply_to;
    }

    /**
     * Returns the priority of the email, one of the following
     * constant values
     *
     * - PRIORITY_LOW
     * - PRIORITY_MEDIUM
     * - PRIORITY_HIGH
     *
     * @access public
     * @return int
     */
    public function priority()
    {
        return $this->priority;
    }
}