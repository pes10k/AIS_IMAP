AIS_IMAP
Developed by AISLabs Inc., Chicago, IL
Authored by Peter Snyder <snyderp@gmail.com>
===

Description
---

AIS_IMAP is a collection of classes that provide a convenient object-oriented 
wrapper around the PHP IMAP extension's functions.  All core functionality from
the extension is exposed, such as allowing mailboxes to be iterated through
conveniently, automatic extraction and construction of email attachments, easy
ways to delete and move messages between mailboxes, and more.

Note that even though the extension being wrapped is called 'imap', both POP3
and IMAP protocols are supported by the extension and this library.

The library also integrates with a featured debugging class, to allow tracking
of how the library is interacting with mailboxes and its contents.

Although classes are explicitly included using 'include_once', the class names
follow the PCR-0 standards.  If your setup has a PCR-0 autoloader, these
includes can be excluded.

Requirements
---

The AIS_IMAP library requires the following code / libraries be installed
    - PHP 5.3.x
    - The IMAP extension, either compiled into PHP or build as a module

Usage
---

See the contained file 'example.php' for simple example of how the library
can be used.

About AISLabs
---

AISLabs a full-service technology firm that provides IT staffing, consulting,
value-added equipment sales, and cloud services. Specializations include web
design, application development, VoIP phone systems, low voltage wiring, and
digital signage.

http://www.aislabs.com/