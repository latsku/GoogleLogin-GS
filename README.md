GoogleLogin-GS
==============

Google OAuth2 login module for GetSimple CMS

Install
-------

Download latest Google APIs Client Library for PHP from https://code.google.com/p/google-api-php-client/.

Extract the archive to /plugins/GoogleLogin/. You should have now Google_Client.php file in /plugins/GoogleLogin folder.

Activate and configure plugin through plugin management.


Known Issues
------------

* Only way to link GetSimple user and Google account is to change GetSimple accounts email address to Google accounts email address.

* Uses pseudoauthentication instead of OpenID Connect based authentication. Maybe minor detail, but still an issue. Look at http://en.wikipedia.org/wiki/OAuth#OpenID_vs._pseudo-authentication_using_OAuth to understand the difference.
