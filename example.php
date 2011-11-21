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
 * Below is a simple example of how to use the AIS_IMAP classes.
 * For more information, refer to the provided class documentation.
 * Note that it does not include some important items, such as error
 * handling.
 *
 * @category PHP
 * @package  AIS_IMAP
 * @author   Peter Snyder <psnyder@aislabs.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link     https://github.com/snyderp/AIS_IMAP
 *
 */

// Include both the IMAP class and the debugger classes.
// If you're not interested in the debugging information
// you can delete the second line below.  Also, if you have a
// PCR-0 autoloader setup, the below can be excluded.
require_once 'AIS/IMAP.php';
require_once 'AIS/Debugger.php';

// First, construct an array with the connection parameters to the 
// server and account you're trying to connect to.
$params = array(
    'host'  =>  'example.org',
    'port'  =>  143,
    'username'  =>  'a.person',
    'password'  =>  'secret-password',
    'type' => 'imap',
);

// The AIS_IMAP classes can be run in debugging mode, in which 
// case information about the classes' operations are logged
// and / or echoed out to the console.
$is_debug_mode = true;

// Provide the connection parameters to the class at instantiation.
$IMAP = new AIS_IMAP($params);

// If we're running in debugging mode, we need to instantiate and 
// configure a debugging object, and then set it as the debugger in
// the AIS_IMAP instance
if ($is_debug_mode) {

    // Below, we set the debugger to 
    // 1) capture information about where code being debugged is located
    //      in the code
    // 2) capture information provided by the AIS_IMAP class about what
    //      the class is doing (ie application provided strings)
    // 3) ignore information about when each event occured
    // 4) to not echo out logged events when they happen, and to instead
    //      store the, internally in the object until they're requested
    $debugger = new AIS_Debugger(
        array(
            'code_level' => true,
            'app_level' => true,
            'timing' => false,
            'print' => false,
        )
    );

    $IMAP->setDebugger($debugger);
}

// We "know" / assume that the given connection has at least two mailboxes in
// it.  Store references to both, so that we can move messages between the two.
$inbox_mailbox = $IMAP->openMailbox('INBOX');
$processed_mailbox = $IMAP->openMailbox('INBOX.Processed');

// Now, retreive a list of all the messages in the inbox.  The messages are 
// represented by instances of the AIS_IMAP_Email class.  We'll assume that there
// are at least three in the inbox (you'll need to inspect the array when using the 
// class).
$emails = $inbox_mailbox->emails();

// We'll delete the first message, store the contents of the second,
// and move the third to the processing inbox

$emails[0]->delete();

$email_body_plain_text = $emails[1]->plainBody();
$email_body_html = $emails[1]->htmlBody();

$emails[2]->moveToMailbox($processed_mailbox);

// Now, close the connection to the IMAP server, which "commits" the changes
// we made to messasges 1 and 3 above.  Note that passing true below actually
// deletes the messages marked for deletion, instead of just leaving them
// "marked" for deletion.
$IMAP->close(true);

// Last, if we're in debugging mode, print all the debug messages we've
// gathered so far
if ($is_debug_mode) {

    echo $debugger->displayMessages();
}