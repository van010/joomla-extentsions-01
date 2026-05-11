<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2026 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use OSSolution\EventBooking\Admin\Event\Event\DisplayEvent;

class EventbookingViewEventHtml extends RADViewHtml
{
	use EventbookingViewEvent;
	use EventbookingViewCaptcha;

	/**
	 * Model state
	 *
	 * @var RADModelState
	 */
	protected $state;

	/**
	 * Event Data
	 *
	 * @var stdClass
	 */
	protected $item;

	/**
	 * The location of event
	 *
	 * @var stdClass
	 */
	protected $location;

	/**
	 * Event custom fields data
	 *
	 * @var array
	 */
	protected $paramData;

	/**
	 * The group registration rates
	 *
	 * @var array
	 */
	protected $rowGroupRates;

	/**
	 * Children events of the current event
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * ID of current user
	 *
	 * @var int
	 */
	protected $userId;

	/**
	 * Plugin outputs which will be displayed in horizontal layout
	 *
	 * @var array
	 */
	protected $horizontalPlugins;

	/**
	 * The access levels of the current user
	 *
	 * @var array
	 */
	protected $viewLevels;

	/**
	 * Show register buttons?
	 *
	 * @var bool
	 */
	protected $showTaskBar;

	/**
	 * Are we printing the event?
	 *
	 * @var bool
	 */
	protected $print;

	/**
	 *  Twitter Bootstrap Helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * Parent views which can be used to get menu item parameters for this view
	 *
	 * @var array
	 */
	protected $paramsViews = [
		'categories',
		'category',
		'upcomingevents',
		'calendar',
		'fullcalendar',
		'location',
		'event',
	];

	/**
	 * The return URl which user will be redirected to after event saved
	 *
	 * @var string
	 */
	protected $return;

	/**
	 * None default languages
	 *
	 * @var array
	 */
	protected $languages;

	/**
	 * Is the site multilingual?
	 *
	 * @var bool
	 */
	protected $isMultilingual;

	/**
	 * Event URL
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * ID of the category which event belong to
	 *
	 * @var int
	 */
	protected $categoryId;

	/**
	 * The category which event belong to
	 *
	 * @var stdClass
	 */
	protected $category;

	/**
	 * Render event view
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display()
	{
		if (in_array($this->getLayout(), ['form', 'simple']))
		{
			$this->displayForm();

			return;
		}

		if (!$this->input->getInt('hmvc_call'))
		{
			$this->setLayout('default');
		}

		$user   = Factory::getApplication()->getIdentity();
		$config = EventbookingHelper::getConfig();

		/* @var EventbookingModelEvent $model */
		$model = $this->getModel();
		$item  = $model->getEventData();

		if (!empty($item->location->image))
		{
			$item->location->image = EventbookingHelperHtml::getCleanImagePath($item->location->image);
		}

		// Check to make sure the event is valid and user is allowed to access to it
		if (empty($item))
		{
			throw new Exception(Text::_('EB_EVENT_NOT_FOUND'), 404);
		}

		if (!$item->published && !$user->authorise('core.admin', 'com_eventbooking') && $item->created_by != $user->id)
		{
			throw new Exception(Text::_('EB_EVENT_NOT_FOUND'), 404);
		}

		if (!in_array($item->access, $user->getAuthorisedViewLevels()))
		{
			if (!$user->id)
			{
				$this->requestLogin();
			}
			else
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$item, ['title', 'price_text']]);

		// Update Hits
		$model->updateHits($item->id);

		if ($item->location_id)
		{
			$this->location = $item->location;
		}

		if ($item->event_type == 1 && $config->show_children_events_under_parent_event)
		{
			$this->items = EventbookingModelEvent::getAllChildrenEvents($item->id);

			// Prepare display data
			EventbookingHelper::callOverridableHelperMethod(
				'Data',
				'prepareDisplayData',
				[$this->items, 0, $config, $this->Itemid]
			);
		}

		if (isset($item->paramData))
		{
			$this->paramData = $item->paramData;
		}

		if ($this->input->get('tmpl', '') == 'component')
		{
			$this->showTaskBar = false;
		}
		else
		{
			$this->showTaskBar = true;
		}

		PluginHelper::importPlugin('eventbooking');

		$eventObj = new DisplayEvent('onEventDisplay', ['item' => $item]);

		$plugins = Factory::getApplication()->triggerEvent('onEventDisplay', $eventObj);

		$horizontalPlugins = [];
		$tabbedPlugins     = [];

		foreach ($plugins as $plugin)
		{
			if (!is_array($plugin) || empty($plugin['form']))
			{
				continue;
			}

			if (isset($plugin['position']) && $plugin['position'] === 'before_register_buttons')
			{
				$horizontalPlugins[] = $plugin;
			}
			else
			{
				$tabbedPlugins[] = $plugin;
			}
		}

		if (empty(EventbookingHelperRoute::$eventsAlias))
		{
			if ($config->insert_event_id)
			{
				EventbookingHelperRoute::$eventsAlias[$item->id] = $item->id . '-' . $item->alias;
			}
			else
			{
				EventbookingHelperRoute::$eventsAlias[$item->id] = $item->alias;
			}

			EventbookingHelperRoute::$locationsAlias[$item->location_id] = $item->location_alias;
		}

		$this->viewLevels        = $user->getAuthorisedViewLevels();
		$this->item              = $item;
		$this->state             = $model->getState();
		$this->config            = $config;
		$this->userId            = $user->id;
		$this->nullDate          = Factory::getContainer()->get('db')->getNullDate();
		$this->plugins           = $tabbedPlugins;
		$this->horizontalPlugins = $horizontalPlugins;
		$this->rowGroupRates     = EventbookingHelperDatabase::getGroupRegistrationRates($item->id);
		$this->bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
		$this->print             = $this->input->getInt('print', 0);
		$this->url               = Route::_(
			EventbookingHelperRoute::getEventRoute($item->id, $item->main_category_id, $this->Itemid),
			false,
			0,
			true
		);

		// Prepare document meta data
		$this->prepareDocument();

		EventbookingHelper::callOverridableHelperMethod(
			'Data',
			'prepareDisplayData',
			[[$this->item], $this->item->main_category_id, $this->config, $this->Itemid]
		);

		parent::display();
	}

	/**
	 * Method to prepare document before it is rendered
	 *
	 * @return void
	 */
	protected function prepareDocument()
	{
		$active = Factory::getApplication()->getMenu()->getActive();

		$params = new Registry($this->item->params ?? '{}');

		if ($active && $this->isDirectMenuLink($active))
		{
			$this->params->def('menu-meta_keywords', $this->item->meta_keywords);
			$this->params->def('menu-meta_description', $this->item->meta_description);
			$this->params->def('robots', $params->get('robots', ''));
		}
		else
		{
			// Not direct menu item, use meta_keywords and menu-meta_description from the event if set
			if ($this->item->meta_keywords)
			{
				$this->params->set('menu-meta_keywords', $this->item->meta_keywords);
			}

			if ($this->item->meta_description)
			{
				$this->params->set('menu-meta_description', $this->item->meta_description);
			}

			if ($params->get('robots'))
			{
				$this->params->set('robots', $params->get('robots'));
			}
		}

		// Process page meta data
		if (!$this->params->get('page_title'))
		{
			if ($this->item->page_title)
			{
				$pageTitle = $this->item->page_title;
			}
			else
			{
				$pageTitle = Text::_('EB_EVENT_PAGE_TITLE');
				$pageTitle = str_replace('[EVENT_TITLE]', $this->item->title, $pageTitle);
				$pageTitle = str_replace('[CATEGORY_NAME]', $this->item->category_name, $pageTitle);
			}

			$this->params->set('page_title', $pageTitle);
		}

		$this->params->def('page_heading', $this->item->title);

		$this->params->def('menu-meta_keywords', $this->item->meta_keywords);

		$this->params->def('menu-meta_description', $this->item->meta_description);

		// Load document assets
		$this->loadAssets();

		// Build document pathway
		$this->buildPathway();

		// Set page meta data
		$this->setDocumentMetadata();
	}

	/**
	 * Load assets (javascript/css) for this specific view
	 *
	 * @return void
	 */
	protected function loadAssets()
	{
		if ($this->config->multiple_booking)
		{
			if ($this->deviceType == 'mobile')
			{
				EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '100%', '450px', 'false', 'false');
			}
			else
			{
				EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '800px', 'false', 'false', 'false', 'false');
			}
		}

		if ($this->config->show_list_of_registrants)
		{
			EventbookingHelperModal::iframeModal('a.eb-colorbox-register-lists', 'eb-registrant-lists-modal');
		}

		EventbookingHelperModal::iframeModal('a.eb-colorbox-map', 'eb-map-modal');

		if ($this->config->show_invite_friend)
		{
			EventbookingHelperModal::iframeModal('a.eb-colorbox-invite', 'eb-invite-friend-modal');
		}
	}

	/**
	 * Method to build document pathway
	 *
	 * @return void
	 */
	protected function buildPathway()
	{
		if ($this->input->getInt('hmvc_call'))
		{
			return;
		}

		$app     = Factory::getApplication();
		$active  = $app->getMenu()->getActive();
		$pathway = $app->getPathway();

		if (isset($active->query['view']) && in_array($active->query['view'], ['category', 'categories']))
		{
			$categoryId = (int) $this->state->get('catid');

			if ($categoryId)
			{
				$parentId = (int) $active->query['id'];
				$paths    = EventbookingHelperData::getCategoriesBreadcrumb($categoryId, $parentId);

				for ($i = count($paths) - 1; $i >= 0; $i--)
				{
					$category = $paths[$i];
					$pathUrl  = EventbookingHelperRoute::getCategoryRoute($category->id, $this->Itemid);
					$pathway->addItem($category->name, $pathUrl);
				}

				$pathway->addItem($this->item->title);
			}
		}
		elseif (isset($active->query['view']) && in_array(
				$active->query['view'],
				['fullcalendar', 'calendar', 'upcomingevents']
			))
		{
			$pathway->addItem($this->item->title);
		}
	}

	/**
	 * Set Open Graph meta data
	 */
	protected function setDocumentMetadata()
	{
		parent::setDocumentMetadata();

		$document      = Factory::getApplication()->getDocument();
		$rootUri       = Uri::root();
		$largeImageUri = '';
		$document->setMetaData('og:title', $this->item->page_title ?: $this->item->title, 'property');

		if ($this->item->image && file_exists(JPATH_ROOT . '/' . $this->item->image))
		{
			$largeImageUri = $rootUri . $this->item->image;
		}
		elseif ($this->item->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/' . $this->item->thumb))
		{
			$largeImageUri = $rootUri . 'media/com_eventbooking/images/' . $this->item->thumb;
		}
		elseif ($this->item->thumb && file_exists(
				JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $this->item->thumb
			))
		{
			$largeImageUri = $rootUri . 'media/com_eventbooking/images/thumbs/' . $this->item->thumb;
		}

		if ($largeImageUri)
		{
			$document->setMetaData('og:image', $largeImageUri, 'property');
		}

		$document->setMetaData('og:url', $this->url, 'property');

		$description = $this->item->meta_description ?: $this->item->description;
		$description = HTMLHelper::_('string.truncate', $description, 200, true, false);
		$document->setMetaData('og:description', $description, 'property');

		$document->setMetaData('og:site_name', Factory::getApplication()->get('sitename'), 'property');
	}

	/**
	 * Display form which allows add/edit event
	 *
	 * @throws Exception
	 */
	protected function displayForm()
	{
		EventbookingHelperJquery::colorbox('eb-colorbox-addlocation');

		$app  = Factory::getApplication();
		$user = Factory::getApplication()->getIdentity();

		/* @var DatabaseDriver $db */
		$db     = Factory::getContainer()->get('db');
		$config = EventbookingHelper::getConfig();
		$item   = $this->model->getData();
		$active = $app->getMenu()->getActive();

		if ($active
			&& isset($active->query['view'], $active->query['layout'])
			&& $active->query['view'] === 'event' && in_array($active->query['layout'], ['form', 'simple']))
		{
			$params = $active->getParams();
		}
		else
		{
			$params = new Registry();
		}

		if ($this->input->getInt('validate_input_error') && method_exists($item, 'bind'))
		{
			$item->bind($this->input->post->getData(), ['id']);
		}
		elseif (!$item->id)
		{
			$item->main_category_id = $params->get('default_category_id', $this->input->getInt('main_category_id', 0));
		}

		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if ($config->submit_event_form_layout == 'simple')
		{
			$this->setLayout('simple');
		}

		if ($item->id)
		{
			$ret = EventbookingHelperAcl::checkEditEvent($item->id);
		}
		else
		{
			$ret = EventbookingHelperAcl::checkAddEvent();
		}

		if (!$ret)
		{
			if (!$user->id)
			{
				$this->requestLogin();
			}
			else
			{
				$app->enqueueMessage(Text::_('EB_NO_ADDING_EVENT_PERMISSION'), 'error');
				$app->redirect(Uri::root(), 403);
			}
		}

		$this->lists = [];

		$query = $db->getQuery(true)
			->select('id, name')
			->from('#__eb_locations')
			->where('published = 1')
			->order('name');

		if (!$user->authorise(
				'core.admin',
				'com_eventbooking'
			) && !$config->show_all_locations_in_event_submission_form)
		{
			$query->where('user_id = ' . (int) $user->id);
		}

		$db->setQuery($query);
		$locations = $db->loadAssocList();

		// Categories dropdown
		$query->clear()
			->select('id, parent AS parent_id')
			->select($db->quoteName('name' . $fieldSuffix, 'title'))
			->from('#__eb_categories')
			->where('published = 1');

		if ($config->get('category_dropdown_ordering', 'name') === 'name')
		{
			$query->order($db->quoteName('name' . $fieldSuffix));
		}
		else
		{
			$query->order('ordering');
		}

		if (!$user->authorise('core.admin', 'com_eventbooking'))
		{
			$query->whereIn('submit_event_access', $user->getAuthorisedViewLevels());
		}

		if ($categoryIds = array_filter(ArrayHelper::toInteger($params->get('category_ids', []))))
		{
			$query->whereIn('id', $categoryIds);
		}

		$db->setQuery($query);
		$categories = $db->loadObjectList();

		$this->buildFormData($item, $categories, $locations);

		$this->ensureGenresTabOnFrontendSubmitForm($item);
		$this->registerFancySelectWebAssetsForEventForm();

		$query->clear()
			->select('id, title')
			->from('#__content')
			->where('`state` = 1')
			->order('title');
		$db->setQuery($query);
		$options                   = [];
		$options[]                 = HTMLHelper::_('select.option', 0, Text::_('EB_SELECT_ARTICLE'), 'id', 'title');
		$options                   = array_merge($options, $db->loadObjectList());
		$this->lists['article_id'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'article_id',
			'',
			'id',
			'title',
			$item->article_id
		);

		if ($item->published != 2 && !array_key_exists('published', $this->lists))
		{
			$options   = [];
			$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
			$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

			$this->lists['published'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'published',
				'class="form-select"',
				'value',
				'text',
				$item->published
			);
		}

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

		$this->lists['enable_cancel_registration'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'enable_cancel_registration',
			'class="form-select"',
			'value',
			'text',
			$item->enable_cancel_registration
		);

		// Load captcha
		$this->loadCaptcha();

		$this->item           = $item;
		$this->return         = $this->input->getBase64('return');
		$this->languages      = EventbookingHelper::getLanguages();
		$this->isMultilingual = count($this->languages) && Multilanguage::isEnabled();

		$this->addToolbar();

		parent::display();
	}

	/**
	 * Ensure the Genres (eventbooking/genres) field appears on the site submit-event form when enabled.
	 *
	 * @param   object  $item  Event object for the form.
	 *
	 * @return  void
	 */
	protected function ensureGenresTabOnFrontendSubmitForm(object $item): void
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		if (!PluginHelper::isEnabled('eventbooking', 'genres'))
		{
			return;
		}

		foreach ($this->plugins as $pluginResult)
		{
			if (!empty($pluginResult['form']) && str_contains((string) $pluginResult['form'], 'eb_genres'))
			{
				return;
			}
		}

		try
		{
			$genrePlugin = $app->bootPlugin('genres', 'eventbooking');
		}
		catch (\Throwable $e)
		{
			return;
		}

		if (!$genrePlugin instanceof plgEventbookingGenres)
		{
			return;
		}

		$html = $genrePlugin->renderFrontendEventForm($item);

		if ($html === '')
		{
			return;
		}

		$app->getLanguage()->load('plg_eventbooking_genres', JPATH_PLUGINS . '/eventbooking/genres');

		$this->plugins[] = [
			'title' => Text::_('PLG_EVENTBOOKING_GENRES_TAB_TITLE'),
			'form'  => $html,
		];
	}

	/**
	 * Load Choices.js and the fancy-select web component so plugin fields (e.g. Genres) work on the FES form.
	 *
	 * @return  void
	 */
	protected function registerFancySelectWebAssetsForEventForm(): void
	{
		if (!Factory::getApplication()->isClient('site'))
		{
			return;
		}

		Factory::getApplication()->getDocument()->getWebAssetManager()
			->usePreset('choicesjs')
			->useScript('webcomponent.field-fancy-select');
	}

	/**
	 * Add toolbar buttons for submit event form
	 */
	protected function addToolbar()
	{
		ToolbarHelper::apply('apply', 'JTOOLBAR_APPLY');

		ToolbarHelper::save('save', 'JTOOLBAR_SAVE');

		if ($this->item->id)
		{
			ToolbarHelper::save2copy('save2copy');
		}

		if ($this->item->id)
		{
			ToolbarHelper::cancel('cancel', 'JTOOLBAR_CLOSE');
		}
		else
		{
			ToolbarHelper::cancel('cancel', 'JTOOLBAR_CANCEL');
		}
	}
}
