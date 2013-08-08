WebArchiveGallery
=================

PHP-based image viewer that can read archives without extracting (i.e. needs no extra space on host and works fine with CIFS/NFS/etc).

Server code tested on Asus WL-500W router running Optware and Lighttpd as well as on Raspberry Pi model B with Apache 2 + mod_php.
Client rendering verified on iOS (iPhone 4S and iPad Mini), Android (Nexus 7) and Windows 8/RT/Phone.

Supported archive formats: zip, cbz, rar, cbr, 7z, cb7.

Note: Server needs to have unrar, unzip and 7z commands in path. You will get an error if you try to open an archive that does not have the required command. If you only have one or the other, those archives will work.
