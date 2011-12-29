<?php
/**
*  Ninja Zemanta System Plugin
*
*  @version     1.0
*  @package     NinjaZemanta
*  @copyright   Copyright (C) 2008 - 2009 NinjaForge. All rights reserved.
*  @license     GNU/GPL
*  @author      Richie Mortimer <richie@ninjaforge.com>
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Example system plugin
 */
class plgButtonNinjazemanta extends JPlugin
{
    /**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param 	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	function plgButtonNinjazemanta(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	function onDisplay( $name )
	{
        $document 	= &JFactory::getDocument();
        $plgbase  	= JURI::root().'plugins/editors-xtd/ninjazemanta/';
        $plugin		=& JPluginHelper::getPlugin('editors-xtd', 'ninjazemanta');
        $params		=  new JParameter($plugin->params);
        $db			=& JFactory::getDBO(); 
        $app        =& JFactory::getApplication();

        //add css
        $document->addStyleSheet($plgbase.'assets/css/zemanta-widget-joomla.css');
        
       	$api_key = $this->zem_api_key();
       	if ( !$api_key ) {
			$api_key = $this->zem_api_key_fetch();
			$this->zem_set_api_key( $api_key );
		}

        //add js
        if ($app->isAdmin()){
        $document->addScript( $plgbase.'assets/js/loader.js' );
        $document->addScript( $plgbase.'assets/js/joomla.js' );
        } else {
        $document->addScript( $plgbase.'assets/js/loader-f.js' );
        $document->addScript( $plgbase.'assets/js/joomla-f.js' );
        }
        $document->addScriptDeclaration('window.ZemantaGetAPIKey = function () {
                                         return \''.$api_key.'\';
                                            }');
        
        $apitest = $this->zem_test_api();
		$proxytest = $this->zem_test_proxy();
		$this->zem_activate();


        $button = new JObject();

		$button->set('modal', false);
		$button->set('onclick', 'insertZemanta();return false;');
		$button->set('text', JText::_('Zemanta'));
		$button->set('name', 'ninjazemanta');
		$button->set('link', '#');


		return $button;


	}
	
function zem_is_pro() {
	if (defined("ZEMANTA_API_KEY") && defined("ZEMANTA_SECRET")) return true;
	if (file_exists(dirname(__FILE__) . "/ninjazemanta/zemantapro.php"))
		require_once(dirname(__FILE__) . "/ninjazemanta/zemantapro.php");
	if (function_exists('zem_load_pro')) zem_load_pro();
	else return false;
}

function zem_check_dependencies() {
	// Return true if CURL and DOM XML modules exist and false otherwise
	return ( ( function_exists( 'curl_init' ) || ini_get('allow_url_fopen') ) &&
		( function_exists( 'preg_match' ) || function_exists( 'ereg' ) ) );
}

function zem_activate() {
	if(file_exists(dirname(__FILE__) . "/ninjazemanta/json-proxy.php"))
		chmod(dirname(__FILE__) . "/ninjazemanta/json-proxy.php", 0755);
}

function zem_reg_match( $rstr, $str ) {
	// Make a regex match independantly of library available. Might work only
	// for simple cases like ours.
	if ( function_exists( 'preg_match' ) )
		preg_match( $rstr, $str, $matches );
	elseif ( function_exists( 'ereg' ) )
		ereg( $rstr, $str, $matches );
	else
		$matches = array('', '');
	return $matches;
}

function zem_do_get_request($url) {
	$fp = @fopen($url, 'rb');
	if (!$fp) {
		return array(1, "Problem connecting to $url : @$php_errormsg\n");
	}
	$response = @stream_get_contents($fp);
	if ($response === false) {
		return array(2, "Problem reading data from $url : @$php_errormsg\n");
	}
	return array(0, $response);
}

function zem_do_post_request($url, $data, $optional_headers = null) {
	$params = array('http' => array(
				'method' => 'POST',
				'content' => $data
					));
	if ($optional_headers !== null) {
		$params['http']['header'] = $optional_headers;
	}
	$ctx = stream_context_create($params);
	$fp = @fopen($url, 'rb', false, $ctx);
	if (!$fp) {
		return("Problem connecting to $url : $php_errormsg\n");
	}
	$response = @stream_get_contents($fp);
	if ($response === false) {
		return("Problem reading data from $url : $php_errormsg\n");
	}
	return $response;
}

function zem_api_key_fetch() {
	if ($this->zem_is_pro()) {
		return "";
	}
	// Fetch fresh API key used with Zemanta calls
	$api = '';
	$url = 'http://api.zemanta.com/services/rest/0.0/';
	$postvars = 'method=zemanta.auth.create_user';

	if ( function_exists( 'curl_init' ) ) {
		$session = curl_init( $url );
		curl_setopt ( $session, CURLOPT_POST, true );
		curl_setopt ( $session, CURLOPT_POSTFIELDS, $postvars );

		// Don't return HTTP headers. Do return the contents of the call
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

		// Make the call
		$rsp = curl_exec( $session );
		curl_close( $session );
	} else if ( ini_get( 'allow_url_fopen' ) ) {
		$rsp = $this->zem_do_post_request($url, $postvars);
	}

	// Parse returned result
	$matches = $this->zem_reg_match( '/<status>(.+?)<\/status>/', $rsp );
	if ( 'ok' == $matches[1] ) {
		$matches = $this->zem_reg_match( '/<apikey>(.+?)<\/apikey>/', $rsp );
		$api = $matches[1];
	}

	return $api;
}

function zem_proxy_url() {
	return JURI::root() . 'plugins/editors-xtd/ninjazemanta/json-proxy.php';
}

function zem_test_proxy() {

	$url = $this->zem_proxy_url();
	$api_key = $this->zem_api_key();
	$args = array(
	'method'=> 'zemanta.suggest',
	'api_key'=> $api_key,
	'text'=> '',
	'format'=> 'xml'
	);

	$data = "";
	foreach($args as $key=>$value)
	{
	$data .= ($data != "")?"&":"";
	$data .= urlencode($key)."=".urlencode($value);
	}

	if ( function_exists( 'curl_init' ) ) {
		$session = curl_init( $url );
		curl_setopt ( $session, CURLOPT_POST, true );
		curl_setopt ( $session, CURLOPT_POSTFIELDS, $data );

		// Don't return HTTP headers. Do return the contents of the call
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

		// Make the call
		$rsp = curl_exec( $session );
		curl_close( $session );
	} else if ( ini_get( 'allow_url_fopen' ) ) {
		$rsp = $this->zem_do_post_request($url, $data);
	} else {
		return JText::_("Zemanta needs either the cURL PHP module or allow_url_fopen enabled to work. Please ask your server administrator to set either of these up.");
	}

	$matches = $this->zem_reg_match( '/<status>(.+?)<\/status>/', $rsp );
	if (!$matches)
		return JText::_("Invalid response: ") . '"' . htmlspecialchars($rsp) . '"';
	return $matches[1];
}

function zem_api_key() {
	
		$plugin		=& JPluginHelper::getPlugin('editors-xtd', 'ninjazemanta');
        $params		=  new JParameter($plugin->params);
		
		return $params->get( 'zemanta_api_key' );
}

function zem_set_api_key($api_key) {

        $plugin		=& JPluginHelper::getPlugin('editors-xtd', 'ninjazemanta');
        $db			=& JFactory::getDBO(); 
        
        $newparams = '"zemanta_api_key='.$api_key.'"';
			
		$query = 'UPDATE #__plugins SET params = '. $newparams
  				. ' WHERE element = '.$db->Quote($plugin->name)
  				;
		$db->setQuery( $query );
		if (!$db->query()) {
			JError::raiseError(500, $db->getErrorMsg() );
		}
        
        
}

function zem_test_api() {

	$url = 'http://api.zemanta.com/services/rest/0.0/';
	$api_key = $this->zem_api_key();
	$args = array(
	'method'=> 'zemanta.suggest',
	'api_key'=> $api_key,
	'text'=> '',
	'format'=> 'xml'
	);

	$data = "";
	foreach($args as $key=>$value)
	{
	$data .= ($data != "")?"&":"";
	$data .= urlencode($key)."=".urlencode($value);
	}

	if ( function_exists( 'curl_init' ) ) {
		$session = curl_init( $url );
		curl_setopt ( $session, CURLOPT_POST, true );
		curl_setopt ( $session, CURLOPT_POSTFIELDS, $data );

		// Don't return HTTP headers. Do return the contents of the call
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

		// Make the call
		$rsp = curl_exec( $session );
		curl_close( $session );
	} else if ( ini_get( 'allow_url_fopen' ) ) {
		$rsp = $this->zem_do_post_request($url, $data);
	} else {
		return JText::_("Zemanta needs either the cURL PHP module or allow_url_fopen enabled to work. Please ask your server administrator to set either of these up.");
	}

	$matches = $this->zem_reg_match( '/<status>(.+?)<\/status>/', $rsp );
	if (!$matches)
		return JText::_("Invalid response: ") . '"' . htmlspecialchars($rsp) . '"';
	return $matches[1];
}

}

?>
