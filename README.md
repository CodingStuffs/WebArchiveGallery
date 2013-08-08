WebArchiveGallery
=================

PHP-based image viewer that can read archives without extracting.
Server code tested on Asus WL-500W running Optware and Lighttpd as well as Raspberry Pi with Apache2 + mod_php.
Client rendering verified on iOS, Android and Windows Phone 8.

Note: Server needs to have unrar and unzip commands in path. You will get an error if you try to open a rar without unrar or zip without unzip. If you only have one or the other, those archives will work.
