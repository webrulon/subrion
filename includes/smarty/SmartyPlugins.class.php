<?php
//##copyright##

class iaSmartyPlugins extends Smarty
{
	const DIRECT_CALL_MARKER = 'direct_call_marker';
	const FLAG_CSS_RENDERED = 'css_rendered';

	const LINK_STYLESHEET_PATTERN = '<link rel="stylesheet" type="text/css" href="%s.css">';
	const LINK_SCRIPT_PATTERN = '<script type="text/javascript" src="%s"></script>';

	const EXTENSION_JS = '.js';

	protected static $_positionsContent = array();


	public function init()
	{
		parent::__construct();

		iaSystem::renderTime('<b>main</b> - beforeSmartyFuncInit');

		$this->registerPlugin(self::PLUGIN_FUNCTION, 'accountActions', array(__CLASS__, 'accountActions'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'accordion', array(__CLASS__, 'accordion'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'arrayToLang', array(__CLASS__, 'arrayToLang'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'captcha', array(__CLASS__, 'captcha'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_wysiwyg', array(__CLASS__, 'ia_wysiwyg'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_add_media', array(__CLASS__, 'ia_add_media'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_blocks', array(__CLASS__, 'ia_blocks'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_block_view', array(__CLASS__, 'ia_block_view'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_print_css', array(__CLASS__, 'ia_print_css'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_print_js', array(__CLASS__, 'ia_print_js'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'ia_url', array(__CLASS__, 'ia_url'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'lang', array(__CLASS__, 'lang'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'preventCsrf', array(__CLASS__, 'preventCsrf'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'printFavorites', array(__CLASS__, 'printFavorites'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'printImage', array(__CLASS__, 'printImage'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'timer', array(__CLASS__, 'timer'));
		$this->registerPlugin(self::PLUGIN_FUNCTION, 'width', array(__CLASS__, 'width'));

		$this->registerPlugin(self::PLUGIN_BLOCK, 'access', array(__CLASS__, 'access'));
		$this->registerPlugin(self::PLUGIN_BLOCK, 'ia_add_js', array(__CLASS__, 'ia_add_js'));
		$this->registerPlugin(self::PLUGIN_BLOCK, 'ia_block', array(__CLASS__, 'ia_block'));

		iaCore::instance()->startHook('phpSmartyAfterFuncInit', array('iaSmarty' => &$this));
	}

	public static function timer($params)
	{
		if (!isset($params['name']))
		{
			return '';
		}
		iaSystem::renderTime($params['name']);

		return '';
	}

	public static function lang($params)
	{
		$key = isset($params['key']) ? $params['key'] : '';
		$default = isset($params['default']) ? $params['default'] : null;

		if (count($params) > 1 && !isset($params['default']))
		{
			unset($params['key']);
			return iaLanguage::getf($key, $params);
		}

		return iaLanguage::get($key, $default);
	}

	public static function ia_wysiwyg($params)
	{
		if (empty($params['name']))
		{
			return '';
		}

		$name = $params['name'];
		$value = isset($params['value']) ? iaSanitize::html($params['value']) : '';

		$iaView = iaCore::instance()->iaView;

		$iaView->add_js('ckeditor/ckeditor');
		$iaView->resources->js->{'code:$(function(){if(!window.CKEDITOR)'
			. "$('textarea[id=\"{$name}\"]').show();else CKEDITOR.replace('{$name}');});"} = iaView::RESOURCE_ORDER_REGULAR;

		return sprintf('<textarea name="%s" ' .
			'style="width:100%s;height:200px;display:none;" cols="80" rows="10" id="%s">' .
			'%s</textarea>', $name, '%', $name, $value);
	}

	public static function ia_block_view($params, Smarty_Internal_Template &$smarty)
	{
		$block = $params['block'];

		switch ($block['type'])
		{
			case 'menu':
				if (isset($block['contents'][0]) && $block['contents'][0])
				{
					$smarty->assign('menu', $block);

					$result = $smarty->fetch($block['tpl']);
				}

				break;

			case 'smarty':
				$smarty->assign('block', $block);

				if ($block['external'])
				{
					$filename = explode(':', $block['filename']);
					$template = iaCore::instance()->get('tmpl');

					switch (count($filename))
					{
						case 1:
							$templateFile = sprintf("%stemplates/%s/%s", IA_HOME, $template, $filename[0]);
							$templateFile = file_exists($templateFile)
								? $templateFile
								: sprintf('%s/templates/common/%s', IA_HOME, $filename[0]);
							break;
						case 2:
							$templateFile = sprintf('%stemplates/%s/packages/%s/%s', IA_HOME, $template, $filename[0], $filename[1]);
							$templateFile = file_exists($templateFile)
								? $templateFile
								: sprintf('%spackages/%s/templates/common/%s', IA_HOME, $filename[0], $filename[1]);
							break;
						default:
							$templateFile = sprintf("%stemplates/%s/plugins/%s.%s", IA_HOME, $template, $filename[1], $filename[2]);
							$templateFile = file_exists($templateFile)
								? $templateFile
								: sprintf('%splugins/%s/templates/front/%s', IA_HOME, $filename[1], $filename[2]);
					}

					$source = file_get_contents($templateFile);
				}
				else
				{
					$source = $block['contents'];
				}

				$result = $smarty->fetch('eval:' . $source);

				break;

			case 'php':
				if (!$block['external'])
				{
					if (iaSystem::phpSyntaxCheck($block['contents']))
					{
						$iaCore = iaCore::instance(); // predefine this variable to be used in the code below
						$result = eval($block['contents']);
					}
					else
					{
						iaDebug::debug(array(
							'name' => $block['name'],
							'code' => '<textarea style="width:80%;height:100px;">' . $block['contents'] . '</textarea>'
						), '<b style="color:red;">PHP syntax error in the block "' . $block['name'] . '"</b>', 'error');
					}
				}
				else
				{
					$result = include_once $block['filename'];
				}

				break;

			case 'html':
				$result = $block['contents'];

				break;

			case 'plain':
				$result = htmlspecialchars($block['contents']);
		}

		return (isset($result) && $result) ? $result : '';
	}

	public static function preventCsrf($params)
	{
		// stub dummy data
		// FIXME: should be properly implemented
		return '<input type="hidden" name="prevent_csrf" value="' . time() . '" />';
	}

	public static function ia_url($params)
	{
		if (empty($params['item']))
		{
			return '#';
		}

		$result = '';

		$defaults = array(
			'url' => '',
			'action' => 'view',
			'item' => '',
			'attr' => '',
			'text' => 'details',
			'type' => 'link',
			'data' => array()
		);
		$params = array_merge($defaults, $params);
		$params['text'] = iaLanguage::get($params['text'], $params['text']);
		$classname = isset($params['classname']) ? $params['classname'] : '';

		switch ($params['item'])
		{
			case 'members':
				$fieldName = isset($params['field']) ? $params['field'] : 'username';
				$params['url'] = IA_URL . 'members/info/' . (is_array($params['data']) && $params['data'] ? $params['data'][$fieldName] : $params['data']) . '.html';
				break;

			default:
				$iaCore = iaCore::instance();
				$iaItem = $iaCore->factory('item');
				$package = $iaItem->getPackageByItem($params['item']);
				if (empty($package))
				{
					return $result;
				}
				$iaPackage = $iaCore->factoryPackage('item', $package, iaCore::FRONT, $params['item']);
				if (empty($iaPackage))
				{
					return $result;
				}
				$params['url'] = $iaPackage->url($params['action'], $params['data']);
		}

		if (!isset($params['icon']))
		{
			$params['icon'] = 'icon-info-sign';
		}
		$params['icon'] = '<i class="' . $params['icon'] . '"></i>';

		switch ($params['type'])
		{
			case 'link':
				$result = '<a href="' . $params['url'] . '" ' . $params['attr'] . '>' . $params['text'] . '</a>';
				break;
			case 'icon':
			case 'icon_text':
				$params['text'] = ($params['type'] == 'icon') ? $params['icon'] : $params['icon'] . ' ' . $params['text'];

				$result = '<a href="' . $params['url'] . '" ' . $params['attr'] . ' class="btn btn-small ' . $classname . '">' . $params['text'] . '</a>';
				break;
			case 'url':
				$result = $params['url'];
		}

		return $result;
	}

	public static function ia_add_media(array $params, &$smarty)
	{
		if (!isset($params['files']))
		{
			return;
		}

		$order = isset($params['order']) ? $params['order'] : iaView::RESOURCE_ORDER_REGULAR;
		$resources = explode(',', $params['files']);
		foreach ($resources as $file)
		{
			$file = trim($file);
			if (empty($file))
			{
				continue;
			}
			if (isset($smarty->resources[$file]))
			{
				self::ia_add_media(array('files' => $smarty->resources[$file], 'order' => $order), $smarty);
			}
			else
			{
				list($type, $file) = @explode(':', $file);
				switch ($type)
				{
					case 'js':
						self::add_js(array('files' => $file, 'order' => $order));
						break;
					case 'css':
						self::ia_print_css(array('files' => $file, 'order' => $order));
						break;
					case 'text':
						self::add_js(array('text' => $file, 'order' => $order));
				}
			}
		}
	}

	public static function ia_print_css(array $params)
	{
		$iaView = &iaCore::instance()->iaView;

		if (isset($params['files']))
		{
			$iaView->add_css(explode(',', $params['files']), isset($params['order']) ? $params['order'] : null);
		}

		// special case: resources marked to inclusion, but the HEAD html section is already rendered.
		// currently just print out the call directly into html body
		// TODO: check if a call of this resource was already printed out
		if ($iaView->get(self::FLAG_CSS_RENDERED))
		{
			$array = $iaView->resources->css->toArray();
			end($array);
			$resource = key($array);

			echo PHP_EOL . sprintf(self::LINK_STYLESHEET_PATTERN, $resource);
			iaDebug::debug('Lateness resource inclusion: ' . $resource . '.css', 'Notice');
			return '';
		}

		if (isset($params['display']) && 'on' == $params['display'])
		{
			if ($iaView->manageMode)
			{
				self::ia_add_media(array('files' => 'manage_mode'), iaCore::instance()->iaSmarty);
			}

			foreach (self::_arrayCopyKeysSorted($iaView->resources->css->toArray()) as $resource)
			{
				$output = sprintf(self::LINK_STYLESHEET_PATTERN, $resource);
				echo PHP_EOL . "\t" . $output;
			}

			$iaView->set(self::FLAG_CSS_RENDERED, true);
		}

		return '';
	}

	public function add_js(array $params)
	{
		$iaView = &iaCore::instance()->iaView;
		$order = isset($params['order']) ? $params['order'] : iaView::RESOURCE_ORDER_REGULAR;

		if (isset($params['files']))
		{
			$iaCore = iaCore::instance();

			$files = $params['files'];
			if (is_string($files))
			{
				$files = explode(',', $files);
			}
			foreach ($files as $filename)
			{
				$filename = trim($filename);
				if (empty($filename))
				{
					continue;
				}

				$compress = true;
				$remote = false;

				if (false !== stristr($filename, 'http://') || false !== stristr($filename, 'https://'))
				{
					$remote = true;
					$compress = false;
					$url = $filename;
				}
				elseif (strstr($filename, '_IA_TPL_'))
				{
					$url = str_replace('_IA_TPL_', IA_TPL_URL . 'js' . IA_URL_DELIMITER, $filename) . self::EXTENSION_JS;
					$file = str_replace('_IA_TPL_', IA_HOME . 'templates' . IA_DS . $iaCore->get('tmpl')  . IA_DS . 'js' . IA_DS, $filename) . self::EXTENSION_JS;
					$tmp = str_replace('_IA_TPL_', 'compress/', $filename);
				}
				elseif (strstr($filename, '_IA_URL_'))
				{
					$url = str_replace('_IA_URL_', IA_CLEAR_URL, $filename) . self::EXTENSION_JS;
					$file = str_replace('_IA_URL_', IA_HOME, $filename) . self::EXTENSION_JS;
					$tmp = str_replace('_IA_URL_', 'compress/', $filename);
				}
				else
				{
					$url = IA_CLEAR_URL . 'js/' . $filename . self::EXTENSION_JS;
					$file = IA_HOME . 'js/' . $filename . self::EXTENSION_JS;
					$tmp = 'compress/' . $filename;
				}

				$modifiedTime = 0;

				if ($compress)
				{
					$excludedFiles = array('ckeditor/ckeditor', 'jquery/jquery', 'extjs/ext-all', '_IA_TPL_bootstrap.min');

					// lang cache
					if (file_exists($file))
					{
						$modifiedTime = filemtime($file);
					}
					if ($filename == '_IA_URL_tmp/cache/intelli.admin.lang.en')
					{
						$url = str_replace('_IA_URL_', IA_CLEAR_URL, $filename) . self::EXTENSION_JS;
						$file = str_replace('_IA_URL_', IA_HOME, $filename) . self::EXTENSION_JS;
						$tmp = str_replace('_IA_URL_', 'compress/', $filename);
					}

					// start compress
					if ($iaCore->get('compress_js', false) && !in_array($filename, $excludedFiles))
					{
						$time = 0;

						// modified time of the compressed file
						if (file_exists(IA_TMP . $tmp . self::EXTENSION_JS))
						{
							$time = filemtime(IA_TMP . $tmp . self::EXTENSION_JS);
						}
						// create directory for compressed files
						else
						{
							$compileDir = IA_TMP . implode( IA_DS, array_slice(explode(IA_DS, $tmp), 0, -1) );
							iaCore::util()->makeDirCascade($compileDir, 0777, true);
						}

						if (file_exists($file))
						{
							$modifiedTime = filemtime($file);
						}

						if (($modifiedTime > $time || $time == 0) && $modifiedTime != 0)
						{
							// need to compress
							iaDebug::debug(IA_TMP . $tmp . self::EXTENSION_JS . ' - ' . $modifiedTime . ' - ' . $time, 'compress', 'info');

							$iaJsmin = $iaCore->factory('jsmin');
							$text = $iaJsmin->minify(file_get_contents($file));

							file_put_contents(IA_TMP . $tmp . self::EXTENSION_JS, $text);
							$modifiedTime = time();
						}

						$url = IA_CLEAR_URL . 'tmp/' . $tmp . self::EXTENSION_JS;
					}
				}

				if (!$remote)
				{
					$url .= '?fm=' . $modifiedTime;
				}

				$iaView->resources->js->$url = $order;
			}
		}
		elseif (isset($params['code']))
		{
			$iaView->resources->js->{'code:' . $params['code']} = $order;
		}
		elseif (isset($params['text']))
		{
			$iaView->resources->js->{'text:' . $params['text']} = $order;
		}
	}

	/**
	 * Converts array items to language file string
	 *
	 * @param array $params array of values
	 */
	public static function arrayToLang($params)
	{
		$list = array();

		if ($array = explode(',', $params['values']))
		{
			foreach ($array as $value)
			{
				if ($title = iaLanguage::get('field_' . $params['name'] . '_' . trim($value)))
				{
					$list[] = $title;
				}
			}
		}

		echo implode(', ', $list);
	}

	/**
	 * Prints picture in the box uses for display listing thumbnails, listing full picture, member avatar
	 *
	 * @param array $params image params
	 *
	 * @return string
	 */
	public static function printImage($params)
	{
		isset($params['url']) || $params['url'] = null;

		$thumbUrl = IA_CLEAR_URL;

		// temporary solution
		// TODO: remove
		if ('a:' == substr($params['imgfile'], 0, 2))
		{
			$array = unserialize($params['imgfile']);

			$params['imgfile'] = $array['path'];
			$params['title'] = $array['title'];
		}
		//

		if (!empty($params['imgfile']))
		{
			$thumbUrl .= 'uploads/';
			if (isset($params['fullimage']) && $params['fullimage'])
			{
				$imgfile = explode('/', $params['imgfile']);
				$imgfile[count($imgfile) - 1] = str_replace('.', '~.', $imgfile[count($imgfile) - 1]);

				$thumbUrl .= implode('/', $imgfile);
			}
			else
			{
				$thumbUrl .= $params['imgfile'];
			}
		}
		else
		{
			$thumbUrl .= 'templates/' . iaCore::instance()->iaView->theme . '/img/no-preview.png';
		}

		if ($params['url'])
		{
			return $thumbUrl;
		}

		$width = isset($params['width']) ? ' width="' . $params['width'] . '"' : '';
		$height = isset($params['height']) ? ' height="' . $params['height'] . '"' : '';
		$title = isset($params['title']) ? iaSanitize::html($params['title']) : '';
		$class = isset($params['class']) ? ' class="' . $params['class'] . '"' : '';

		return sprintf(
			'<img src="%s" alt="%s" title="%s"%s>',
			$thumbUrl,
			$title,
			$title,
			$width . $height . $class
		);
	}

	/**
	 * Prints add/remove favorites icons
	 *
	 * @param array $params button params
	 *
	 * @return string
	 */
	public static function printFavorites($params)
	{
		if (!iaUsers::hasIdentity() || empty($params['item']) || empty($params['itemtype'])
			|| ('members' != $params['itemtype'] && isset($params['item']['member_id']) && iaUsers::getIdentity()->id === $params['item']['member_id'])
			|| ('members' == $params['itemtype'] && iaUsers::getIdentity()->id == $params['item']['id'] )
		)
		{
			return '';
		}

		$classname = isset($params['classname']) ? $params['classname'] : '';

		// $output = '<span id="favorites_' . $params['itemtype'] . '_' . $params['item']['id'] . '">';
		$output = '<a href="javascript:void(0)" onclick="intelli.actionFavorites';

		if (isset($params['item']['favorite']) && $params['item']['favorite'] == '1')
		{
			$output .= "('{$params['item']['id']}', '{$params['itemtype']}', 'delete');\"";
			$output .= ' rel="nofollow" class="btn btn-small ' . $classname . '" title="' . iaLanguage::get('remove_from_favorites') . '"><i class="icon-star"></i></a>';
		}
		else
		{
			$output .= "('{$params['item']['id']}', '{$params['itemtype']}', 'add');\"";
			$output .= ' rel="nofollow" class="btn btn-small ' . $classname . '" title="' . iaLanguage::get('add_to_favorites') . '"><i class="icon-star-empty"></i></a>';
		}
		// $output .= '</span>';

		return $output;
	}

	public static function accountActions($params)
	{
		if (!iaUsers::hasIdentity()
			|| empty($params['item'])
			|| empty($params['itemtype'])
			|| ('members' == $params['itemtype'] && iaUsers::getIdentity()->id != $params['item']['id'])
			|| ('members' != $params['itemtype'] && isset($params['item']['member_id']) && iaUsers::getIdentity()->id != $params['item']['member_id'])
			)
		{
			return '';
		}

		$iaCore = iaCore::instance();
		$iaItem = $iaCore->factory('item');

		$params['img'] = $img = IA_CLEAR_URL . 'templates/' . $iaCore->iaView->theme . '/img/';
		$classname = isset($params['classname']) ? $params['classname'] : '';

		$upgradeUrl = '';
		$editUrl = '';
		$extraActions = '';
		$output = '';

		if ('members' == $params['itemtype'])
		{
			$editUrl = IA_URL . 'profile/';
		}
		else
		{
			$item = $iaItem->getPackageByItem($params['itemtype']);
			if (empty($item))
			{
				return '';
			}
			$iaPackage = $iaCore->factoryPackage('item', $item, iaCore::FRONT, $params['itemtype']);
			if (empty($iaPackage))
			{
				return '';
			}
			if (method_exists($iaPackage, __FUNCTION__))
			{
				list($editUrl, $upgradeUrl) = $iaPackage->{__FUNCTION__}($params);
			}
			if (method_exists($iaPackage, 'extraActions'))
			{
				$extraActions = $iaPackage->extraActions($params['item']);
			}
		}
		$iaCore->startHook('phpSmartyAccountActionsBeforeShow',
			array('params' => &$params, 'type' => $params['itemtype'], 'upgrade_url' => &$upgradeUrl, 'edit_url' => &$editUrl, 'output' => &$output));

		if ($editUrl)
		{
			$output .= '<a rel="nofollow" href="' . $editUrl . '" class="btn btn-small ' . $classname . '" title="' . iaLanguage::get('edit') . '"><i class="icon-pencil"></i></a>';
		}

		return $output . $extraActions;
	}

	public static function accordion($params, Smarty_Internal_Template &$smarty)
	{
		$smarty->assign('accordion_params', $params);
		$smarty->display('accordion.tpl');
	}

	public static function ia_block(array $params, $content, Smarty_Internal_Template &$smarty)
	{
		$result = '';

		if (trim($content))
		{
			$smarty->assign('collapsible', isset($params['collapsible']) ? $params['collapsible'] : false);
			$smarty->assign('icons', isset($params['icons']) ? $params['icons'] : array());
			$smarty->assign('id', isset($params['id']) ? $params['id'] : null);
			$smarty->assign('header', isset($params['header']) ? $params['header'] : true);
			$smarty->assign('name', isset($params['name']) ? $params['name'] : '');
			$smarty->assign('classname', isset($params['classname']) ? $params['classname'] : '');
			$smarty->assign('style', isset($params['style']) ? $params['style'] : 'movable');
			$smarty->assign('title', isset($params['title']) ? $params['title'] : '');
			$smarty->assign('_block_content_', $content);

			if (!isset($params['tpl']) || empty($params['tpl']))
			{
				$params['tpl'] = 'block.tpl';
			}

			$result = $smarty->fetch($params['tpl']);
		}

		return $result;
	}

	public static function access($params, $content)
	{
		if (empty($content) || !isset($params['object']))
		{
			return '';
		}

		$user = isset($params['user']) ? (int)$params['user'] : 0;
		$group = isset($params['group']) ? (int)$params['group'] : 0;
		$objectId = isset($params['id']) ? $params['id'] : 0;
		$action = isset($params['action']) ? $params['action'] : 'read';
		$object = $params['object'];

		if (!in_array($action, array(iaCore::ACTION_ADD, iaCore::ACTION_DELETE, iaCore::ACTION_EDIT, iaCore::ACTION_READ)))
		{
			$object = $object . '-' . $objectId;
			$objectId = 0;
		}

		$iaAcl = iaCore::instance()->factory('acl');
		if ($iaAcl->checkAccess($object . ':' . $action, $user, $group, $objectId))
		{
			return $content;
		}

		return '';
	}

	public static function ia_add_js($params, $content)
	{
		if (!trim($content))
		{
			return;
		}

		$iaView = &iaCore::instance()->iaView;
		$iaView->resources->js->{'code:' . $content} = isset($params['order']) ? $params['order'] : 4;
	}

	public static function ia_print_js($params, Smarty_Internal_Template &$smarty)
	{
		$smarty->add_js($params);

		if (!isset($params['display']))
		{
			return '';
		}

		$iaView = &iaCore::instance()->iaView;
		$resources = self::_arrayCopyKeysSorted($iaView->resources->js->toArray());

		$output = '';
		foreach ($resources as $resource)
		{
			switch (true)
			{
				case (strpos($resource, 'code:') === 0):
					if ($code = trim(substr($resource, 5)))
					{
						$output .= PHP_EOL . "\t" . '<script type="text/javascript"><!-- ' . PHP_EOL . $code . PHP_EOL . ' --></script>';
					}
					continue;
				case (strpos($resource, 'text:') === 0):
					if (iaUsers::hasIdentity() && iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType())
					{
						$text = trim(substr($resource, 5));
						$output .= "<script type=\"text/javascript\">if(document.getElementById('js-ajax-loader-status'))document.getElementById('js-ajax-loader-status').innerHTML = '" . $text . "';</script>" . PHP_EOL;
					}
					continue;
				default:
					$output .= PHP_EOL . "\t" . sprintf(self::LINK_SCRIPT_PATTERN, $resource);
			}
		}

		return $output;
	}

	public static function captcha($params)
	{
		$preview = isset($params['preview']);

		$iaCore = iaCore::instance();

		if ($captchaName = $iaCore->get('captcha_name'))
		{
			$iaCaptcha = $iaCore->factoryPlugin($captchaName, iaCore::FRONT, 'captcha');

			return $preview
				? $iaCaptcha->getPreview()
				: $iaCaptcha->getImage();
		}

		if ($preview)
		{
			return iaLanguage::get('no_captcha_preview');
		}

		return '';
	}

	public static function ia_blocks(array $params, Smarty_Internal_Template &$smarty)
	{
		if (!isset($params['block']))
		{
			return '';
		}

		$directCall = isset($params[self::DIRECT_CALL_MARKER]);
		$position = $params['block'];

		// return immediately if position's content is already rendered
		if (!$directCall && isset(self::$_positionsContent[$position]))
		{
			// NULL will be an empty content marker
			return is_null(self::$_positionsContent[$position]) ? '' : self::$_positionsContent[$position];
		}

		// mark that we were here
		self::$_positionsContent[$position] = null;

		$iaView = iaCore::instance()->iaView;
		$blocks = $iaView->blocks;
		$blocks = isset($blocks[$position]) ? $blocks[$position] : null;

		if ($blocks || $iaView->manageMode)
		{
			// define if this position should be movable in visual mode
			$smarty->assign('manage', $iaView->manageMode);
			$smarty->assign('movable', isset($params['movable']));
			$smarty->assign('position', $position);
			$smarty->assign('blocks', $blocks);

			$output = $smarty->fetch('render-blocks' . iaView::TEMPLATE_FILENAME_EXT, $position . mt_rand(1000, 9999));

			if (trim($output))
			{
				self::$_positionsContent[$position] = $output;
			}
		}

		return $directCall ? null : self::$_positionsContent[$position];
	}

	public static function width(array $params, Smarty_Internal_Template &$smarty)
	{
		$position = isset($params['position']) ? $params['position'] : 'center';
		$section = isset($params['section']) ? $params['section'] : 'content';
		$movable = isset($params['movable']);

		$iaCore = iaCore::instance();

		$layoutData = $iaCore->get('tmpl_layout_data');
		$layoutData = empty($layoutData) ? array() : unserialize($layoutData);

		// pre-compilation of section's positions
		if (isset($layoutData[$section]))
		{
			foreach ($layoutData[$section] as $positionName => $options)
			{
				if (!isset(self::$_positionsContent[$positionName]))
				{
					self::ia_blocks(array('block' => $positionName, 'movable' => $movable, self::DIRECT_CALL_MARKER => true), $smarty);
				}
			}
		}

		$positions = array_keys(array_filter(self::$_positionsContent));
		$positions[] = 'center';

		if (!in_array($position, $positions))
		{
			$width = 0;
		}
		else
		{
			$width = 3; // default width

			// start to calculate a width specific to the Bootstrap CSS framework
			if (isset($layoutData[$section][$position]))
			{
				$sectionPositions = $layoutData[$section];

				if ($sectionPositions[$position]['fixed'])
				{
					$width = $sectionPositions[$position]['width'];
				}
				else
				{
					$unitsToDistribute = 0;
					$positionWidth = array();
					$flexiblePositions = array();

					// composing initial data
					foreach ($sectionPositions as $positionName => $options)
					{
						in_array($positionName, $positions)
							? $positionWidth[$positionName] = $options['width']
							: $unitsToDistribute += $options['width'];
						$options['fixed'] || $flexiblePositions[] = $positionName;
					}

					// if we need to distribute a width of hidden positions
					if ($flexiblePositions)
					{
						reset($positionWidth);
						while ($unitsToDistribute)
						{
							$key = key($positionWidth);
							if (is_null($key))
							{
								reset($positionWidth);
								$key = key($positionWidth);
							}
							// simply increment a width of flexible positions
							if (in_array($key, $flexiblePositions))
							{
								$positionWidth[$key]++;
								$unitsToDistribute--;
							}
							next($positionWidth);
						}
					}

					// width calculation
					$width = 12;
					foreach ($sectionPositions as $positionName => $options)
					{
						if ($positionName != $position && in_array($positionName, $positions))
						{
							$width -= $positionWidth[$positionName];
						}
					}
				}
			}
		}

		$tag = isset($params['tag']) ? $params['tag'] : 'span';

		return $tag . $width;
	}


	private static function _arrayCopyKeysSorted(array $array)
	{
		$a = array();
		foreach ($array as $key => $value)
		{
			isset($a[$value]) || $a[$value] = array();
			$a[$value][] = $key;
		}
		ksort($a, SORT_NUMERIC);
		$result = array();
		foreach ($a as $values)
		{
			foreach ($values as $value)
			{
				$result[] = $value;
			}
		}

		return $result;
	}
}