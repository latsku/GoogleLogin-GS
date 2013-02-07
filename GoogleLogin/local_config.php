<?php
global $apiConfig;
$google_login_conf = google_login_loadconf();

$apiConfig = array(

    // The application_name is included in the User-Agent HTTP header.
    'application_name' => $google_login_conf['application_name'],
	
    // OAuth2 Settings, you can get these keys at https://code.google.com/apis/console
    'oauth2_client_id' => $google_login_conf['oauth2_client_id'],
    'oauth2_client_secret' => $google_login_conf['oauth2_client_secret'],
    'oauth2_redirect_uri' => $google_login_conf['oauth2_redirect_uri'],

    // The developer key, you get this at https://code.google.com/apis/console
    'developer_key' => $google_login_conf['developer_key'],

    // Site name to show in the Google's OAuth 1 authentication screen.
    'site_name' => $google_login_conf['site_name'],

    'oauth2_approval_prompt' => $google_login_conf['oauth2_approval_prompt']

);
