<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

require_once JPATH_ROOT . '/modules/mod_vg_search_eb/src/Helper/Response.php';
require_once JPATH_ROOT . '/modules/mod_vg_search_eb/src/Model/VgSearchEbModel.php';

class ModVgSearchEbHelper
{

	public static string $moduleName = 'mod_vg_search_eb';

	protected static function getLayout($events): string
	{
		return LayoutHelper::render('timeline', $events, JPATH_ROOT . '/modules/'.self::$moduleName.'/layouts');
	}

	public static function loadEventsAjax()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$data = [
			'filterToDate' => $input->getString('filter_to_date'),
			'filterFromDate' => $input->getString('filter_from_date'),
			'orchestraCategoryId' => $input->getInt('orchestra_category_id', 0),
			'emotionCategoryId' => $input->get('emotion_category_id', []),
			'locationId' => $input->getInt('location_id', 0)
		];

		$events = VgSearchEbModel::getEvents($data);

		Response::json([
			'html' => self::getLayout($events),
			'message' => 'success'
		]);
	}

	public static function splitDate(string $dateString): array
	{
		$parsed = date_parse($dateString);

		return [
			'day'   => $parsed['day'],
			'month' => $parsed['month'],
			'year'  => $parsed['year']
		];
	}
}
