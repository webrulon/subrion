<?php
//##copyright##

class iaLanguage
{
	const CATEGORY_ADMIN = 'admin';
	const CATEGORY_COMMON = 'common';
	const CATEGORY_FRONTEND = 'frontend';
	const CATEGORY_PAGE = 'page';
	const CATEGORY_TOOLTIP = 'tooltip';

	protected static $_table = 'language';

	protected static $_phrases = array();

	protected static $_validCategories = array(self::CATEGORY_ADMIN, self::CATEGORY_COMMON, self::CATEGORY_FRONTEND, self::CATEGORY_PAGE, self::CATEGORY_TOOLTIP);


	public function __construct()
	{
	}

	public function __clone()
	{
	}

	public function init()
	{
	}

	public static function get($key, $default = null)
	{
		if (empty($key)) // false, empty string values
		{
			return false;
		}
		if (self::exists($key))
		{
			return self::$_phrases[$key];
		}
		else
		{
			if (INTELLI_DEBUG && empty($default))
			{
				$iaCore = iaCore::instance();
				$iaCache = $iaCore->factory('cache');
				$cache = $iaCache->get('nonexistent_phrases', 0, true);

				if (empty($cache))
				{
					$cache = array();
				}
				if (!in_array($key, $cache))
				{
					$cache[] = $key;
					$iaCache->write('nonexistent_phrases', serialize($cache));
				}

				iaDebug::debug($key, 'Phrases do not exist', 'error');
			}

			return is_null($default)
				? '{' . $key . '}'
				: $default;
		}
	}

	public static function getf($key, array $replaces)
	{
		$phrase = self::get($key);

		if (empty($phrase))
		{
			return $phrase;
		}

		$search = array();
		foreach (array_keys($replaces) as $item)
		{
			array_push($search, ':' . $item);
		}

		return str_replace($search, array_values($replaces), $phrase);
	}

	public static function set($key, $value)
	{
		self::$_phrases[$key] = $value;
	}

	public static function exists($key)
	{
		return isset(self::$_phrases[$key]);
	}

	public static function load($languageCode)
	{
		$iaCore = iaCore::instance();

		$stmt = "`code` = :language AND `category` != 'tooltip' AND `category` != :exclusion ORDER BY `extras`";
		$iaCore->iaDb->bind($stmt, array(
			'language' => $languageCode,
			'exclusion' => (iaCore::ACCESS_FRONT == $iaCore->getAccessType()) ? 'admin' : 'frontend'
		));

		self::$_phrases = $iaCore->iaDb->keyvalue(array('key', 'value'), $stmt, self::getTable());
	}

	public static function getPhrases()
	{
		return self::$_phrases;
	}

	public static function getTooltips()
	{
		$iaDb = &iaCore::instance()->iaDb;

		$stmt = '`category` = :category AND `code` = :language';
		$iaDb->bind($stmt, array('category' => self::CATEGORY_TOOLTIP, 'language' => IA_LANGUAGE),1);

		$rows = $iaDb->keyvalue(array('key', 'value'), $stmt, self::getTable());

		return is_array($rows) ? $rows : array();
	}

	public static function getTable()
	{
		return self::$_table;
	}

	public static function addPhrase($key, $value, $languageCode = '', $plugin = '', $category = self::CATEGORY_COMMON, $forceReplacement = true)
	{
		if (!in_array($category, self::$_validCategories))
		{
			return false;
		}

		$iaDb = iaCore::instance()->iaDb;
		$iaDb->setTable(self::getTable());

		$languageCode = empty($languageCode) ? IA_LANGUAGE : $languageCode;

		$stmt = '`key` = :key AND `code` = :language AND `category` = :category AND `extras` = :plugin';
		$iaDb->bind($stmt, array(
			'key' => $key,
			'language' => $languageCode,
			'category' => $category,
			'plugin' => $plugin
		));

		$phrase = $iaDb->row(array('original', 'value'), $stmt);

		if (empty($phrase))
		{
			$result = $iaDb->insert(array(
				'key' => $key,
				'original' => $value,
				'value' => $value,
				'code' => $languageCode,
				'category' => $category,
				'extras' => $plugin
			));
		}
		else
		{
			$result = ($forceReplacement || ($phrase['value'] == $phrase['original']))
				? $iaDb->update(array('value' => $value), $stmt)
				: false;
		}

		$iaDb->resetTable();

		return (bool)$result;
	}
}