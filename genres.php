<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
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
		'Pop',
		'Rock',
		'Hip-Hop',
		'Electronic (EDM)',
		'Country',
		'Jazz',
		'Blues',
		'R&B',
		'Reggae',
		'Classical',
		'Techno',
		'Death Metal',
		'Trap',
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

		ob_start();
		$this->drawSettingForm($row);

		$result = [
			'title' => Text::_('PLG_EVENTBOOKING_GENRES_TAB_TITLE'),
			'form'  => ob_get_clean(),
		];

		$this->addResult($eventObj, $result);
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
		$sanitized = array_values(array_intersect(self::ALLOWED_GENRES, $posted));

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
				$selectedGenres = array_values(array_intersect(self::ALLOWED_GENRES, $decoded));
			}
		}

		$allowedGenres = self::ALLOWED_GENRES;

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
}
