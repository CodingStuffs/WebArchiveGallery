WebArchiveGallery
=================

PHP-based image viewer that can read archives without extracting to disk and display them on any mobile device. That means you can put your comics in one place and immediately read them on every device you own.

WAG only requires an existing PHP server to run this script on and some way of reading your archives (locally or through a CIFS or NFS share or etc).

Server code tested on Asus WL-500W router running Optware and Lighttpd as well as on Raspberry Pi model B with Apache 2 + mod_php.
Client rendering verified on iOS (iPhone 4S and iPad Mini), Android (Nexus 7) and Windows 8/RT/Phone.

Supported archive formats: zip, cbz, rar, cbr, 7z, cb7.

Note: Server needs to have unrar, unzip and 7z commands in path. You will get an error if you try to open an archive that does not have the required command. If you only have one or the other, those archives will work (for example, it's fine to only have unzip on server if you don't plan to view rar or 7z files).
