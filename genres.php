<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class plgEventbookingGenres extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Allowed genre labels stored on the event (must match form options exactly).
	 */
	public const ALLOWED_GENRES = [
		'Solo Violin',
		'Solo Piano',
		'Chamber',
		'Orchestral',
		'Opera',
		'Vocal',
		'Contemporary-Classical',
		'Cross-genre collaboration',
		'Film Music',
		'Folk',
		'Jazz',
	];

	/**
	 * Params JSON key on #__eb_events.params
	 */
	private const PARAM_KEY = 'eb_genres';

	/**
	 * @var \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * @var \Joomla\Database\DatabaseDriver
	 */
	protected $db;

	public static function getSubscribedEvents(): array
	{
		return [
			'onEditEvent'      => 'onEditEvent',
			'onAfterSaveEvent' => 'onAfterSaveEvent',
		];
	}

	public function onEditEvent(Event $eventObj): void
	{
		$args = $eventObj->getArguments();
		$row  = $args['item'];

		if (!$this->canRun($row))
		{
			return;
		}

		$this->loadLanguage();
		$this->enqueueFrontendCategoryOverrides();

		ob_start();
		$this->drawSettingForm($row);

		$result = [
			'title' => Text::_('PLG_EVENTBOOKING_GENRES_TAB_TITLE'),
			'form'  => ob_get_clean(),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * HTML for the Genres field on the frontend add/edit event form.
	 * Used by com_eventbooking when the normal onEditEvent pipeline does not attach the tab.
	 *
	 * @param   object  $row  Event row/table object passed to the submit form view.
	 *
	 * @return  string  Markup or empty string when the plugin should not run on this context.
	 */
	public function renderFrontendEventForm(object $row): string
	{
		if (!$this->canRun($row))
		{
			return '';
		}

		$this->loadLanguage();

		ob_start();
		$this->drawSettingForm($row);

		return (string) ob_get_clean();
	}

	public function onAfterSaveEvent(Event $eventObj): void
	{
		$args = $eventObj->getArguments();
		$row  = $args['row'];
		$data = $args['data'];

		if (!$this->canRun($row))
		{
			return;
		}

		$posted = $data['eb_genres'] ?? [];

		if (!is_array($posted))
		{
			$posted = ($posted !== null && $posted !== '') ? [(string) $posted] : [];
		}

		$posted    = array_map('strval', $posted);
		$allowed   = $this->getAllowedGenresFromConfig();
		$sanitized = array_values(array_intersect($allowed, $posted));

		$params = new Registry($row->params);
		$params->set(self::PARAM_KEY, json_encode($sanitized, JSON_UNESCAPED_UNICODE));

		$row->params = $params->toString();
		$row->store();
	}

	private function drawSettingForm(object $row): void
	{
		$params = new Registry($row->params);

		$selectedGenres = [];

		$raw = $params->get(self::PARAM_KEY, '');

		if ($raw !== null && $raw !== '')
		{
			$decoded = json_decode((string) $raw, true);

			if (is_array($decoded))
			{
				$allowedList    = $this->getAllowedGenresFromConfig();
				$selectedGenres = array_values(array_intersect($allowedList, $decoded));
			}
		}

		$allowedGenres = $this->getAllowedGenresFromConfig();

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	private function canRun(object $row): bool
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Load override script/data for frontend submit-event form category fields.
	 *
	 * @return void
	 */
	private function enqueueFrontendCategoryOverrides(): void
	{
		if (!$this->app->isClient('site'))
		{
			return;
		}

		$input = $this->app->getInput();

		if ($input->getCmd('option') !== 'com_eventbooking'
			|| $input->getCmd('view') !== 'event'
			|| !in_array($input->getCmd('layout', 'default'), ['form', 'simple'], true))
		{
			return;
		}

		$document = Factory::getDocument();
		$wa       = $document->getWebAssetManager();
		$wa->registerAndUseScript(
			'plg_eventbooking_genres.category_override',
			'plugins/eventbooking/genres/assets/js/plg-eb-genres.js'
		);

		$orchestraParent = trim((string) $this->params->get('orchestra_category_parent', 'Partners'));
		$emotionParent   = trim((string) $this->params->get('emotion_category_parent', 'Emotion'));

		$document->addScriptOptions(
			'plgEventbookingGenresOverride',
			[
				'mainCategory'       => [
					'selectId'      => 'main_category_id',
					'labelFor'      => 'main_category_id',
					'labelText'     => Text::_('PLG_EVENTBOOKING_GENRES_ORCHESTRAS_LABEL'),
					'placeholder'   => Text::_('PLG_EVENTBOOKING_GENRES_SELECT_ORCHESTRA'),
					'options'       => $this->getChildrenByParentName($orchestraParent),
				],
				'additionalCategory' => [
					'selectId'      => 'category_id',
					'labelFor'      => 'category_id',
					'labelText'     => Text::_('PLG_EVENTBOOKING_GENRES_EMOTION_LABEL'),
					'placeholder'   => Text::_('PLG_EVENTBOOKING_GENRES_SELECT_EMOTION'),
					'options'       => $this->getChildrenByParentName($emotionParent),
				],
			]
		);
	}

	/**
	 * Find published child categories by parent category title.
	 *
	 * @param   string  $parentTitle  Parent category name to match.
	 *
	 * @return array<int, array{value:int,text:string}>
	 */
	private function getChildrenByParentName(string $parentTitle): array
	{
		$parentTitle = trim($parentTitle);

		if ($parentTitle === '')
		{
			return [];
		}

		$db          = Factory::getContainer()->get('DatabaseDriver');
		$fieldSuffix = '';

		if (class_exists('EventbookingHelper') && method_exists('EventbookingHelper', 'getFieldSuffix'))
		{
			$fieldSuffix = (string) EventbookingHelper::getFieldSuffix();
		}

		$parentNameCol = 'name' . $fieldSuffix;
		$childNameCol  = 'name' . $fieldSuffix;

		$query = $db->getQuery(true)
			->select($db->quoteName('c.id', 'value'))
			->select($db->quoteName('c.' . $childNameCol, 'text'))
			->from($db->quoteName('#__eb_categories', 'c'))
			->innerJoin(
				$db->quoteName('#__eb_categories', 'p')
				. ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('c.parent')
			)
			->where($db->quoteName('c.published') . ' = 1')
			->where($db->quoteName('p.published') . ' = 1')
			->where($db->quoteName('p.' . $parentNameCol) . ' = :parentTitle')
			->order($db->quoteName('c.' . $childNameCol));

		$query->bind(':parentTitle', $parentTitle, ParameterType::STRING);

		$db->setQuery($query);

		return $db->loadAssocList() ?: [];
	}

	/**
	 * Allowed genre labels from this plugin params.
	 *
	 * Admin can extend with comma-separated values via plugin param `genre_values`.
	 * Falls back to ALLOWED_GENRES when empty.
	 *
	 * @return array<int, string>
	 */
	private function getAllowedGenresFromConfig(): array
	{
		static $cached = null;

		if ($cached !== null)
		{
			return $cached;
		}

		$raw = (string) $this->params->get('genre_values', '');

		if ($raw === '')
		{
			$cached = self::ALLOWED_GENRES;

			return $cached;
		}

		$parts = $this->splitGenreValuesList($raw);

		$seen   = [];
		$merged = [];

		foreach ($parts as $genre)
		{
			if ($genre === '')
			{
				continue;
			}

			if (!isset($seen[$genre]))
			{
				$seen[$genre] = true;
				$merged[]     = $genre;
			}
		}

		$cached = $merged !== [] ? $merged : self::ALLOWED_GENRES;

		return $cached;
	}

	/**
	 * @return array<int, string>
	 */
	private function splitGenreValuesList(string $raw): array
	{
		$parts = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);

		if ($parts === false)
		{
			return [];
		}

		return array_map('trim', $parts);
	}
}
