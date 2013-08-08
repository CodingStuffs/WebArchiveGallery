WebArchiveGallery
=================

PHP-based image viewer that can read archives without extracting (needs no extra space).

Server code tested on Asus WL-500W running Optware and Lighttpd as well as Raspberry Pi with Apache 2 + mod_php.
Client rendering verified on iOS (iPhone 4S and iPad Mini), Android (Nexus 7) and Windows 8/RT/Phone.

Note: Server needs to have unrar and unzip commands in path. You will get an error if you try to open a rar without unrar or zip without unzip. If you only have one or the other, those archives will work.
