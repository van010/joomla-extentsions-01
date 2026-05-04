<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Database\ParameterType;


class ModVgSearchEbHelper
{

	public string $moduleName = 'mod_vg_search_eb';

	protected function getLayout($data)
	{
		$html = LayoutHelper::render('timeline', $data, JPATH_ROOT . '/modules/'.$this->moduleName.'/layouts');

		return $html;
	}

	public static function loadEventsAjax()
	{
		$app = Factory::getApplication();
		$input = $app->input;
	}
}
