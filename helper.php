<?php

defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

require_once JPATH_ROOT . '/modules/mod_vg_search_eb/src/Helper/Response.php';
require_once JPATH_ROOT . '/modules/mod_vg_search_eb/src/Model/VgSearchEbModel.php';

class ModVgSearchEbHelper
{

	public static string $moduleName = 'mod_vg_search_eb';
	public static string $comEbName = 'com_eventbooking';
	public static array $allowedLayouts = ['timeline', 'column', 'columns'];

	/**
	 * Get result layout from module layouts folder.
	 *
	 * @param   array   $events  The events list.
	 * @param   string  $layout  The requested layout.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	protected static function getLayout(array $events, string $layout = 'timeline'): string
	{
		if (!in_array($layout, self::$allowedLayouts, true))
		{
			$layout = 'timeline';
		}

		return LayoutHelper::render($layout, $events, JPATH_ROOT . '/modules/' . self::$moduleName . '/layouts');
	}

	/**
	 * Ajax search
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function loadEventsAjax(): void
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$searchResultLayout = $input->getCmd('search_result_layout', 'timeline');
		if (!Session::checkToken()) {
			Response::error('Bad request');
		}
		$data = [
			'filterToDate' => trim($input->getString('filter_to_date')),
			'filterFromDate' => trim($input->getString('filter_from_date')),
			'orchestraCategoryId' => $input->getInt('orchestra_category_id', 0),
			'emotionCategoryId' => $input->get('emotion_category_id', []),
			'locationId' => $input->getInt('location_id', 0)
		];

		$events = VgSearchEbModel::getEvents($data);

		Response::json([
			'html' => self::getLayout($events, $searchResultLayout),
			'message' => 'success'
		]);
	}

	/**
	 * @param   string  $dateString
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function splitDate(string $dateString): array
	{
		$parsed = date_parse($dateString);
		$dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);

		return [
			'day'   => $parsed['day'],
			'month' => $dateObj->format('M'),
			'year'  => $parsed['year']
		];
	}

	/**
	 * Parses a datetime string into a formatted date and time array.
	 *
	 * @param   string  $dateTimeStr  Input format: "Y-m-d H:i:s" (e.g., "2026-08-17 21:00:00")
	 *
	 * @return array{date: string, time: string}
	 * @since 1.0.0
	 */
	public static function parseDateTimeToArray(string $dateTimeStr): array
	{
		$dateObj = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeStr);

		if (!$dateObj)
		{
			// Fallback if format doesn't match strictly
			$dateObj = new \DateTime($dateTimeStr);
		}

		return [
			// Format: "Sun 30 Oct" (Day name + Day number + Month short name)
			'date' => $dateObj->format('D d M'),

			// Format: "9:00 pm" (12-hour hour without leading zero : minutes lowercase am/pm)
			'time' => $dateObj->format('g:i a')
		];
	}

	/**
	 * Check com-eb is installed and enabled or not ?
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public static function isEbEnabled(): bool
	{
		return ComponentHelper::isEnabled(self::$comEbName);
	}

	/**
	 * Get url of an image
	 *
	 * @param   string|null  $imagePath
	 *
	 * @return string|null
	 *
	 * @since 1.0.0
	 */
	public static function getImageThumb(?string $imagePath): ?string
	{
		$thumbPath = JPATH_ROOT . '/media/com_eventbooking/images/thumbs';
		$imageName = self::getImageName($imagePath);
		if (!$imageName) return null;

		$thumb = $thumbPath . "/{$imageName}";
		if (is_file($thumb)) {
			return Uri::root() . str_replace(JPATH_SITE . DIRECTORY_SEPARATOR, '', $thumb);
		}

		return Uri::root() . $imagePath;
	}

	/**
	 * Get image name
	 *
	 * @param   string|null  $path
	 *
	 * @return string|null
	 *
	 * @since 1.0.0
	 */
	public static function getImageName(?string $path): ?string
	{
		if (empty($path))
		{
			return null;
		}

		// 1. Remove fragment identifiers (everything after #)
		// Example: "path/image.jpg#fragment" -> "path/image.jpg"
		$path = explode('#', $path)[0];

		// 2. Remove query parameters (everything after ?)
		// Example: "path/image.jpg?width=100" -> "path/image.jpg"
		$path = explode('?', $path)[0];

		// 3. Get the basename (filename + extension)
		// Example: "images/com_eventbooking/gospel.jpg" -> "gospel.jpg"
		$filename = basename($path);

		// 4. Validate that it looks like an image file (optional but recommended)
		if (!preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $filename))
		{
			return null;
		}

		return $filename;
	}

	/**
	 * Get url to event detail
	 *
	 * @param   object  $event
	 *
	 * @return string
	 *
	 * @since version
	 */
	public static function parseUrlEventDetail(object $event): string
	{
		$params = [
			'option' => self::$comEbName,
			'view' => 'event',
			'id' => $event->id,
			'catid' => $event->main_category_id,
			'Itemid' => VgSearchEbModel::getEbUpComingEventsMenu()
		];
		return Uri::root() . 'index.php?' . http_build_query($params);
	}

	/**
	 * @param   object  $event
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function parseUrlLocation(object $event): string
	{
		$params = [
			'option' => self::$comEbName,
			'view' => 'map',
			'location_id' => $event->location_id,
			'Itemid' => VgSearchEbModel::getEbUpComingEventsMenu()
		];
		return Uri::root() . 'index.php?' . http_build_query($params);
	}

	/**
	 * Get current date as string, e.g., 2023-02-23 18:30:00
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function getCurrentDate(): string
	{
		return date("Y-m-d H:i:s");
	}
}
