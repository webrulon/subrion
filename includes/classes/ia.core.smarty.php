<?php
//##copyright##

require_once IA_SMARTY . 'Smarty.class.php';
require_once IA_SMARTY . 'SmartyPlugins.class.php';
require_once IA_SMARTY . 'SmartyResources.class.php';

class iaSmarty extends iaSmartyPlugins
{
	const INTELLI_RESOURCE = 'intelli';

	public $iaCore;

	public $resources = array();


	public function init()
	{
		$this->iaCore = iaCore::instance();
		parent::init();

		$obj = new smartyResources();
		$this->registerResource(self::INTELLI_RESOURCE, array(
			array($obj, 'intelli_get_template'),
			array($obj, 'intelli_get_timestamp'),
			array($obj, 'intelli_get_secure'),
			array($obj, 'intelli_get_trusted')
		));

		foreach ($this->iaCore->packagesData as $packageName => $packageData)
		{
			$this->registerResource($packageName, $this->_createPackageTemplateHandlers($packageName));
		}

		iaSystem::renderTime('<b>main</b> - afterSmartyFuncInit');

		$this->assign('tabs_content', array());
		$this->assign('tabs_before', array());
		$this->assign('tabs_after', array());

		$this->assign('fieldset_before', array());
		$this->assign('fieldset_after', array());
		$this->assign('field_before', array());
		$this->assign('field_after', array());

		$this->resources = array(
			'jquery' => 'text:Loading jQuery API..., js:jquery/jquery',
			'subrion' => 'text:Loading Subrion Awesome Stuff..., js:intelli/intelli, js:_IA_URL_tmp/cache/intelli.config, '
			. (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType()
				? 'js:_IA_TPL_bootstrap.min, js:bootstrap/js/bootstrap-switch.min, js:bootstrap/js/passfield.min, js:intelli/intelli.admin, js:admin/footer, css:_IA_URL_js/bootstrap/css/passfield'
				: 'js:intelli/intelli.minmax, js:frontend/footer, js:jquery/plugins/jquery.numeric')
			. ',js:_IA_URL_tmp/cache/intelli' . (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType() ? '.admin' : '') . '.lang.' . $this->iaCore->iaView->language,
			'extjs' => 'text:Loading ExtJS..., css:_IA_URL_js/extjs/resources/ext-theme-neptune/ext-theme-neptune-all' . ($this->iaCore->get('sap_style', false) ? '-' . $this->iaCore->get('sap_style') : '') . ', js:extjs/ext-all',
			'manage_mode' => 'js:frontend/visual-mode',
			'jstree' => 'js:jquery/plugins/jstree/jquery.jstree, css:_IA_URL_js/jquery/plugins/jstree/themes/default/style',
			'jcal' => 'js:jquery/plugins/jcal/jquery.jcal, css:_IA_URL_js/jquery/plugins/jcal/jquery.jcal',
			'bootstrap' => 'js:bootstrap/js/bootstrap.min, css:iabootstrap, css:iabootstrap-responsive, css:user-style',
			'datepicker' => 'js:bootstrap/js/bootstrap-datetimepicker.min, css:_IA_URL_js/bootstrap/css/datetimepicker',
			'tagsinput' => 'js:jquery/plugins/tagsinput/jquery.tagsinput.min, css:_IA_URL_js/jquery/plugins/tagsinput/jquery.tagsinput',
			'underscore' => 'js:utils/underscore.min',
			'flexslider' => 'js:jquery/plugins/flexslider/jquery.flexslider.min, css:_IA_URL_js/jquery/plugins/flexslider/flexslider'
		);

		$this->iaCore->startHook('phpSmartyAfterMediaInit', array('iaSmarty' => &$this));
	}

	/*
	 * Return absolute path to template resource
	 *
	 * @param string resourceName template resource name
	 * @param bool useDefault
	 *
	 * @return string absolute path to a template resource name
	 */
	public function ia_template($resourceName, $useDefault = false)
	{
		$default = $resourceName;

		if ($useDefault)
		{
			$resourceName = $this->template_dir . $resourceName;
		}
		else
		{
			$templateName = $this->iaCore->iaView->theme;

			if (defined('IA_CURRENT_PACKAGE'))
			{
				if (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType())
				{
					if (is_file(IA_PACKAGE_TEMPLATE_ADMIN . $resourceName))
					{
						$resourceName = IA_PACKAGE_TEMPLATE_ADMIN . $resourceName;
					}
				}
				elseif (is_file(IA_FRONT_TEMPLATES . $templateName . IA_DS . 'packages' . IA_DS . IA_CURRENT_PACKAGE . IA_DS . $resourceName))
				{
					$resourceName = IA_FRONT_TEMPLATES . $templateName . IA_DS . 'packages' . IA_DS . IA_CURRENT_PACKAGE . IA_DS . $resourceName;
				}
				elseif (is_file(IA_PACKAGE_TEMPLATE_COMMON . $resourceName))
				{
					$resourceName = IA_PACKAGE_TEMPLATE_COMMON . $resourceName;
				}
			}
			elseif (defined('IA_CURRENT_PLUGIN'))
			{
				$resourceName = is_file(IA_PLUGIN_TEMPLATE . $resourceName)
					? IA_PLUGIN_TEMPLATE . $resourceName
					: IA_TEMPLATES . $templateName . IA_DS . $resourceName;
			}

			$resourceName = ($resourceName == $default)
				? IA_TEMPLATES . $templateName . IA_DS . $resourceName
				: $resourceName;

			is_file($resourceName) || $resourceName = IA_TEMPLATES . 'common' . IA_DS . $default;
		}

		if (!$this->templateExists($resourceName))
		{
			if (INTELLI_DEBUG > 1 || INTELLI_QDEBUG > 1)
			{
				trigger_error('Unable to find the following resource: <b>' . $resourceName . '</b>', E_USER_ERROR);
			}
			else
			{
				echo '<div style="font-weight: bold; color: #f00; margin: 20px 0;">File Missing: ' . $resourceName . '</div>';
			}
		}

		iaSystem::renderTime('after check file ' . $resourceName);

		return $resourceName;
	}

	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
	{
		$resourceName = $this->ia_template($template, false);

		iaSystem::renderTime('check the template: ' . $resourceName);

		$result = parent::display($resourceName, $cache_id, null);

		iaSystem::renderTime('rendering the template: ' . $resourceName);

		return $result;
	}

	public function fetch_tpl($resourceName, $cacheId = null, $compileId = null, $display = false)
	{
		$resourceName = $this->ia_template($resourceName, false);

		return parent::fetch($resourceName, $cacheId, $compileId, $display);
	}

	private function _createPackageTemplateHandlers ($packageName)
	{
		$pathDeterminationCode = '
			$templateFile = sprintf("%stemplates/%s/packages/%s/%s", IA_HOME, $smarty->iaCore->get("tmpl"), ":name", $name);
			$templateFile = file_exists($templateFile)
				? $templateFile
				: sprintf("%spackages/%s/templates/common/%s", IA_HOME, ":name", $name);
		';
		$pathDeterminationCode = str_replace(':name', $packageName, $pathDeterminationCode);

		return array(
			create_function('$name, &$source, $smarty', $pathDeterminationCode . '$source = file_get_contents($templateFile); return (false !== $source);'),
			create_function('$name, &$timestamp, $smarty', $pathDeterminationCode . '$timestamp = filemtime($templateFile); return true;'),
			create_function(null, ''),
			create_function(null, '')
		);
	}
}