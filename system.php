<?php
//##copyright##

define('IA_VERSION', '3.1.1');

if (isset($ia_version))
{
	return IA_VERSION;
}

if (version_compare('5.2', PHP_VERSION, '>'))
{
	exit('Subrion ' . IA_VERSION . ' requires PHP 5.2 or higher to run properly.');
}

// enable errors display
ini_set('display_errors', true);
error_reporting(E_STRICT | E_ALL);

// define system constants
define('IA_DS', '/');
define('IA_URL_DELIMITER', '/');
define('IA_HOME', str_replace('\\', IA_DS, dirname(__FILE__)) . IA_DS);
define('IA_INCLUDES', IA_HOME . 'includes' . IA_DS);
define('IA_CLASSES', IA_INCLUDES . 'classes' . IA_DS);
define('IA_PLUGINS', IA_HOME . 'plugins' . IA_DS);
define('IA_PACKAGES', IA_HOME . 'packages' . IA_DS);
define('IA_UPLOADS', IA_HOME . 'uploads' . IA_DS);
define('IA_SMARTY', IA_INCLUDES . 'smarty' . IA_DS);
define('IA_TMP', IA_HOME . 'tmp' . IA_DS);
define('IA_CACHEDIR', IA_TMP . 'cache' . IA_DS);
define('IA_FRONT', IA_HOME . 'front' . IA_DS);
define('IA_ADMIN', IA_HOME . 'admin' . IA_DS);
define('FOLDER', trim(str_replace((array('/index.php', '/system.php')), '', $_SERVER['PHP_SELF']), IA_URL_DELIMITER));
define('FOLDER_URL', FOLDER != '' ? trim(str_replace(IA_DS, IA_URL_DELIMITER, FOLDER), IA_URL_DELIMITER) . IA_URL_DELIMITER : '');

// process stripslashes if magic_quotes is enabled on the server
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
{
	$in = array(&$_GET, &$_POST, &$_COOKIE, &$_SERVER);
	while (list($k, $v) = each($in))
	{
		foreach ($v as $key => $val)
		{
			if (!is_array($val))
			{
				$in[$k][$key] = stripslashes($val);
				continue;
			}
			$in[] = & $in[$k][$key];
		}
	}
	unset($in);
}

if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.') && !filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_IP))
{
	$domain = $_SERVER['HTTP_HOST'];
	$chunks = array_reverse(explode('.', $domain));
	if (count($chunks) > 2)
	{
		if (!in_array($chunks[1], array('co', 'com', 'net', 'org', 'gov', 'ltd', 'ac', 'edu')))
		{
			$domain = implode('.', array($chunks[1], $chunks[0]));

			if ($chunks[2] != 'www')
			{
				$domain = implode('.', array($chunks[2], $chunks[1], $chunks[0]));
			}
		}
	}
	$domain = '.' . $domain;

	session_set_cookie_params(0, '/', $domain);
}

session_name('INTELLI_' . substr(md5(IA_HOME), 0, 10));
session_start();

unset($_SESSION['debug'], $_SESSION['error'], $_SESSION['info']);

$performInstallation = false;

if (file_exists(IA_INCLUDES . 'config.inc.php'))
{
	include IA_INCLUDES . 'config.inc.php';
	defined('INTELLI_DEBUG') || $performInstallation = true;
}
else
{
	$performInstallation = true;
}

// redirect to installation
if ($performInstallation)
{
	if (file_exists(IA_HOME . 'install/system.php'))
	{
		header('Location: ' . str_replace('system.php', 'install/', $_SERVER['SCRIPT_NAME']));
		return;
	}

	exit('Install directory was not found!');
}

require_once IA_CLASSES . 'ia.system.php';

if (function_exists('spl_autoload_register'))
{
	spl_autoload_register(array('iaSystem', 'autoload'));
}

iaSystem::renderTime('start');

if (INTELLI_DEBUG)
{
	register_shutdown_function(array('iaSystem', 'shutdown'));
	ob_start(array('iaSystem', 'output'));
}
else
{
	error_reporting(0);
}

set_error_handler(array('iaSystem', 'error'));

require_once IA_INCLUDES . 'function.php';
require_once IA_CLASSES . 'ia.interfaces.php';

iaSystem::renderTime('Core Loaded');

iaCore::instance()->init();