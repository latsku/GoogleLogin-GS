<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); } ?>
<?php
/*
Plugin Name: Google Login
Description: Allows login with Google account
Version: 0.5
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
        '0.5',
        'Lari Lehtomäki',
        'http://latsku.fi/',
        'Allows login with Google account',
        'plugin',
	GoogleLogin_settings
);

# global vars
$google_login_conf = google_login_loadconf();

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

function GoogleLogin_settings() { 
  global $google_login_conf;
  if (isset($_POST) && sizeof($_POST)>0) {
    /* Save Settings */
    if (isset($_POST['application_name'])) {
      $google_login_conf['application_name'] = $_POST['application_name'];
    }
    if (isset($_POST['oauth2_client_id'])) {
      $google_login_conf['oauth2_client_id'] = $_POST['oauth2_client_id'];
    }
    if (isset($_POST['oauth2_client_secret'])) {
      $google_login_conf['oauth2_client_secret'] = $_POST['oauth2_client_secret'];
    }
    if (isset($_POST['oauth2_redirect_uri'])) {
      $google_login_conf['oauth2_redirect_uri'] = $_POST['oauth2_redirect_uri'];
    }
    if (isset($_POST['developer_key'])) {
      $google_login_conf['developer_key'] = $_POST['developer_key'];
    }
    if (isset($_POST['site_name'])) {
      $google_login_conf['site_name'] = $_POST['site_name'];
    }
    if (isset($_POST['oauth2_approval_prompt'])) {
      $google_login_conf['oauth2_approval_prompt'] = $_POST['oauth2_approval_prompt'];
    }
    google_login_saveconf();
    echo '<div style="display: block;" class="updated">Updated</div>';
  }
?>
  <h2>Google Login</h2>
  <p>
    All values can be found or created on <a href="https://code.google.com/apis/console/#access">Google APIs console</a>.
  </p>

  <form method="post" action="<?php echo $_SERVER ['REQUEST_URI']?>">
    <p><label for="application_name">Application Name</label>
    <input type="text" id="application_name" name="application_name" value="<?php print $google_login_conf['application_name']; ?>" class="text" /></p>

    <p><label for="oauth2_client_id">Oauth2 Client Id</label>
    <input type="text" id="oauth2_client_id" name="oauth2_client_id" value="<?php print $google_login_conf['oauth2_client_id']; ?>" class="text" /></p>

    <p><label for="oauth2_client_secret">Oauth2 Client Secret</label>
    <input type="text" id="oauth2_client_secret" name="oauth2_client_secret" value="<?php print $google_login_conf['oauth2_client_secret']; ?>" class="text" /></p>

    <p><label for="oauth2_redirect_uri">Oauth2 Redirect URI</label>
    <input type="url" id="oauth2_redirect_uri" name="oauth2_redirect_uri" value="<?php print $google_login_conf['oauth2_redirect_uri']; ?>" class="text" /></p>

    <p><label for="developer_key">Developer key (optional)</label>
    <input type="text" id="developer_key" name="developer_key" value="<?php print $google_login_conf['developer_key']; ?>" class="text" /></p>

    <p><label for="site_name">Site Name</label>
    <input type="text" id="site_name" name="site_name" value="<?php print $google_login_conf['site_name']; ?>" class="text" /></p>

    <p><label for="oauth2_approval_prompt">Approval Prompt</label>
    <select name="oauth2_approval_prompt"><?php
	if ( $google_login_conf['oauth2_approval_prompt'] == "auto" ) { ?>	
      <option selected value="auto">auto</option>
      <option value="force">force</option>
	  <?php } else { ?>
      <option value="auto">auto</option>
      <option selected value="force">force</option>
	  <?php } ?>
    </select></p>

    <p><input type="submit" id="submit" class="submit" value="<?php i18n('BTN_SAVESETTINGS'); ?>" name="submit" /></p>
  </form>
<?php
} 

/* get config settings from file */
function google_login_loadconf() {
  $vals=array();
  $configfile=GSDATAOTHERPATH . 'google_login.xml';
  if (!file_exists($configfile)) {
    //default settings
    $xml_root = new SimpleXMLElement('<settings><application_name></application_name><oauth2_client_id></oauth2_client_id><oauth2_client_secret></oauth2_client_secret><oauth2_redirect_uri></oauth2_redirect_uri><developer_key></developer_key><site_name></site_name><oauth2_approval_prompt>force</oauth2_approval_prompt></settings>');
    if ($xml_root->asXML($configfile) === FALSE) {
		exit("SAVEERROR " . $configfile . " MSG_CHECKPRIV");
    }
    if (defined('GSCHMOD')) {
	  chmod($configfile, GSCHMOD);
    } else {
      chmod($configfile, 0755);
    }
  }

  $xml_root = simplexml_load_file($configfile);
  
  if ($xml_root !== FALSE) {
    $node = $xml_root->children();
  
    $vals['application_name'] = (string)$node->application_name;
    $vals['oauth2_client_id'] = (int)$node->oauth2_client_id;
    $vals['oauth2_client_secret'] = (string)$node->oauth2_client_secret;
    $vals['oauth2_redirect_uri'] = (string)$node->oauth2_redirect_uri;
    $vals['developer_key'] = (string)$node->developer_key;
    $vals['site_name'] = (string)$node->site_name;
    $vals['oauth2_approval_prompt'] = (string)$node->oauth2_approval_prompt;
  }
  return($vals);
}

/* save config settings to file */
function google_login_saveconf() {
  global $google_login_conf;
  $configfile=GSDATAOTHERPATH . 'google_login.xml';

  $xml_root = new SimpleXMLElement('<settings></settings>');
  $xml_root->addchild('application_name', $google_login_conf['application_name']);
  $xml_root->addchild('oauth2_client_id', $google_login_conf['oauth2_client_id']);
  $xml_root->addchild('oauth2_client_secret', $google_login_conf['oauth2_client_secret']);
  $xml_root->addchild('oauth2_redirect_uri', $google_login_conf['oauth2_redirect_uri']);
  $xml_root->addchild('developer_key', $google_login_conf['developer_key']);
  $xml_root->addchild('site_name', $google_login_conf['site_name']);
  $xml_root->addchild('oauth2_approval_prompt', $google_login_conf['oauth2_approval_prompt']);
  
  if ($xml_root->asXML($configfile) === FALSE) {
	exit("SAVEERROR " . $configfile . " MSG_CHECKPRIV");
  }
}

?>