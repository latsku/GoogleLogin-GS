<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); } ?>
<?php
/*
Plugin Name: Google Login
Description: Allows login with Google account
Version: 0.1
Author: Lari Lehtomäki
Author URI: http://latsku.fi
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");
define('__ROOT__', dirname(dirname(__FILE__)));



# register plugin
register_plugin(
        $thisfile,
	$thisfile,
        '0.1',
        'Lari Lehtomäki',
        'http://latsku.fi/',
        'Allows login with Google account',
        'plugin',
	GoogleLogin_settings
);

require_once __ROOT__ . '/plugins/GoogleLogin/Google_Client.php';
require_once __ROOT__ . '/plugins/GoogleLogin/contrib/Google_Oauth2Service.php';

add_action('plugins-sidebar','createSideMenu', array($thisfile, "Google Login"));
add_action('index-login','DoGoogle');
add_action('login-reqs','LoginButton');

register_style('LoginButtons', $SITEURL.'plugins/GoogleLogin/css/auth-buttons.css', 0.1, 'screen');
queue_style('LoginButtons',GSBACK);

function LoginButton() {
  $client = new Google_Client();
  $oauth2 = new Google_Oauth2Service($client);
  $google_authUrl = $client->createAuthUrl();

  print "<a class='btn-auth btn-google' href='$google_authUrl'>Log in with <b>Google</b></a>";

}

function DoGoogle() {
  $MSG = null;
  $error = null;
  session_start();
  $client = new Google_Client();
  $oauth2 = new Google_Oauth2Service($client);

  if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
    return;
  }

  if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
  }

  if (isset($_REQUEST['logout'])) {
    unset($_SESSION['token']);
    $client->revokeToken();
  }

  if ($client->getAccessToken()) {
    $user = $oauth2->userinfo->get();

    // These fields are currently filtered through the PHP sanitize filters.
    // See http://www.php.net/manual/en/filter.filters.sanitize.php
    $email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
    $img = filter_var($user['picture'], FILTER_VALIDATE_URL);
    $personMarkup = "$email<div><img src='$img?sz=50'></div>";

    // The access token may have been updated lazily.
    $_SESSION['token'] = $client->getAccessToken();

    $authenticated = false;
    if ($handle = opendir(GSUSERSPATH)) {
      while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && strstr($entry, ".xml")) {
          $data = getXML(GSUSERSPATH . '/' . $entry );

	  if ( $email == $data->EMAIL ) {
	    $USR = strtolower($data->USR);
	    $authenticated = true;
	    break;
	  }
        }
      }
      $logFailed = new GS_Logging_Class('failedlogins.log');
      if (!$authenticated) {
        $logFailed->add('Reason','Cannot match Google account and local account.');
      }
      closedir($handle);
    }
    if( $authenticated ) {
      $logFailed->add('Username',$USR);
      exec_action('successful-login-start');
      create_cookie();
      setcookie('GS_ADMIN_USERNAME', $USR, time() + 3600,'/');
      exec_action('successful-login-end');
      redirect($cookie_redirect);
    } else {
      $error = i18n_r('LOGIN_FAILED');
    }
    $logFailed->save();
  }
}

function GoogleLogin_settings() { ?>
  <h2>Google Login</h2>
  <p>
    All values can be found or created on <a href="https://code.google.com/apis/console/#access">Google APIs console</a>.
  </p>

  <form method="post" action="<?php echo $_SERVER ['REQUEST_URI']?>">
    <p><label for="application_name">Application Name</label>
    <input type="text" id="application_name" name="application_name" value="<?php print $application_name; ?>" class="text" /></p>

    <p><label for="oauth2_client_id">Oauth2 Client Id</label>
    <input type="text" id="oauth2_client_id" name="oauth2_client_id" value="<?php print $oauth2_client_id; ?>" class="text" /></p>

    <p><label for="oauth2_client_secret">Oauth2 Client Secret</label>
    <input type="text" id="oauth2_client_secret" name="oauth2_client_secret" value="<?php print $oauth2_client_secret; ?>" class="text" /></p>

    <p><label for="oauth2_redirect_uri">Oauth2 Redirect URI</label>
    <input type="url" id="oauth2_redirect_uri" name="oauth2_redirect_uri" value="<?php print $oauth2_redirect_uri; ?>" class="text" /></p>

    <p><label for="site_name">Site Name</label>
    <input type="text" id="site_name" name="site_name" value="<?php print $site_name; ?>" class="text" /></p>

    <p><label for="oauth2_approval_prompt">Approval Prompt</label>
    <select name="oauth2_approval_prompt">
      <option selected value="auto">auto</option>
      <option value="force">force</option>
    </select></p>

    <p><input type="submit" id="submit" class="submit" value="<?php i18n('BTN_SAVESETTINGS'); ?>" name="submit" /></p>
  </form>
<?php
} ?>