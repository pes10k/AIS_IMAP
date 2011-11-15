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
 * Instances of this class represent file attachments to an email.  A single
 * message may have zero or more associated attachments.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */
class AIS_IMAP_Attachment extends AIS_Debugable
{
    // ========================
    // ! Protected Properties
    // ========================

    /**
     * Holds a reference to the email that contains
     * this attachment
     *
     * @var AIS_IMAP_Email
     * @access protected
     */
    protected $email;

    /**
     * Whether this attachment was selected as being
     * inline or as an attachment
     *
     * @var bool
     * @access protected
     */
    protected $is_inline;

    /**
     * The raw data of the attachment
     *
     * @var mixed
     * @access protected
     */
    protected $attachment;

    /**
     * Filename of the file that was attached
     *
     * @var mixed
     * @access protected
     */
    protected $file_name;

    /**
     * Unique identifier of the attachment, useful for reconstructing
     * HTML body content
     *
     * @var string
     * @access protected
     */
    protected $attachment_id;

    /**
     * A string describing the attachment type, usually file extension
     *
     * @var string
     * @access protected
     */
    protected $attachment_type;

    /**
     * Integer describing the filesize of the attachment
     *
     * @var int
     * @access protected
     */
    protected $size;

    // ==================
    // ! Public Methods
    // ==================

    /**
     * Convenience initilizer, that allows for populating the object at
     * initilization, including a refernece to the email containing this
     * attachment.
     *
     * @param AIS_IMAP_Email $email           A reference to the email message
     *                                        that this attachment is with.
     * @param stdClass       $imap_data       An object, provided by the
     *                                        imap_fetchstructure function,
     *                                        that describes a part of an email
     *                                        message.
     * @param string         $attachment_data A binary string that contains the
     *                                        data stored in this part of the
     *                                        message.
     *
     * @return void
     * @see http://www.php.net/manual/en/function.imap-fetchstructure.php
     */
    public function __construct($email, $imap_data, $attachment_data)
    {
        $this->email = $email;

        $this->is_inline = (empty($imap_data->disposition) OR
            $imap_data->disposition !== 'inline');
        $this->attachment = base64_decode($attachment_data);
        $this->size = $imap_data->bytes;
        $this->attachment_id = (empty($imap_data->id))
            ? false
            : $imap_data->id;

        $this->attachment_type = (empty($imap_data->subtype))
            ? false
            : $imap_data->subtype;

        if ( ! empty($imap_data->parameters)) {

            foreach ($imap_data->parameters as $item) {

                if ($item->attribute === 'name') {

                    $this->file_name = $item->value;
                }
            }
        }
    }

    // ===========
    // ! Getters
    // ===========

    /**
     * Returns a reference to the email object that
     * this attachment belongs to
     *
     * @access public
     * @return AIS_IMAP_Email
     */
    public function email()
    {
        return $this->email;
    }

    /**
     * Returns a boolean value, describing whether
     * the file is intended to be displayed in the file
     * or as an external file
     *
     * @access public
     * @return bool
     */
    public function isInline()
    {
        return $this->is_inline;
    }

    /**
     * Contains the raw data of the attachment
     *
     * @access public
     * @return string|binary
     */
    public function attachment()
    {
        return $this->attachment;
    }

    /**
     * Returns the name of the file as it was attached,
     * which, if the attachment is inline, likely differs
     * from how the file is refered to in the email body
     *
     * @access public
     * @return string
     */
    public function filename()
    {
        return $this->file_name;
    }

    /**
     * Returns a string used for uniquly identifiying an email
     * attachment within the body of the email (such as an image
     * attachement within in the body HTML).  This value is often
     * wrapped in angle brackets (eg <ID>), which can optionally
     * be stripped off by passing TRUE for $strip_brackets
     *
     * @param bool $strip_brackets (default. false) Often the
     *      identifier for each attachment is surrounded by
     *      angle brackets.  Passing in true here will automatically
     *      strip off these brackets if they're present.
     *
     * @return string
     */
    public function attachmentId($strip_brackets = false)
    {
        if ( ! $strip_brackets) {

            return $this->attachment_id;

        } else {

            return trim($this->attachment_id, ' <>');

        }
    }

    /**
     * The type of the attachment, usually a three character
     * file extension (ex. JPG, XML, etc.)
     *
     * @return string
     */
    public function attachmentType()
    {
        return $this->attachment_type;
    }

    /**
     * The size of the file attachment
     *
     * @return int
     */
    public function size()
    {
        return $this->size;
    }
}