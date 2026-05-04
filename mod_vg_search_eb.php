<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\SiteApplication;

require_once __DIR__ . '/src/Model/VgSearchEbModel.php';
require_once __DIR__ . '/helper.php';

HTMLHelper::_('jquery.framework');

/** @var SiteApplication $app */
$app   = Factory::getApplication();
$input = $app->getInput();

$selectedGenres      = $input->get('genres', [], 'array');
$selectedOrchestra   = $input->getInt('orchestra_category_id', 0);
$selectedEmotion     = $input->get('emotion_category_id', [], 'array');
$selectedDate        = $input->getString('filter_from_date', '');
$selectedVenue       = $input->getInt('location_id', 0);
$selectedCategoryId  = $input->getInt('category_id', 0);
$currentMenuId       = $input->getInt('Itemid', (int) ($app->getMenu()->getActive()->id ?? 0));

$selectedGenres  = array_map('intval', $selectedGenres);
$selectedEmotion = array_map('intval', $selectedEmotion);

$orchestraParentId = (int) $params->get('orchestra_parent_id', 0);
$orchestraNames    = (array) $params->get('orchestra_names', []);
$showVenues        = (int) $params->get('show_venues', 1) === 1;
$itemId            = (int) $params->get('item_id') ?: $currentMenuId ?: EventbookingHelper::getItemid();

$filters = [
    'genres'    => VgSearchEbModel::getTaxonomyOptions('Genres'),
    'orchestra' => VgSearchEbModel::getOrchestraOptions($orchestraParentId, $orchestraNames),
    'emotion'   => VgSearchEbModel::getTaxonomyOptions('Emotion'),
    'venues'    => $showVenues ? VgSearchEbModel::getVenues() : [],
];

$orchestraIds = array_map(static fn($item) => (int) $item->id, $filters['orchestra']);
$emotionIds   = array_map(static fn($item) => (int) $item->id, $filters['emotion']);

if ($selectedCategoryId > 0) {
    if (!$selectedOrchestra && in_array($selectedCategoryId, $orchestraIds, true)) {
        $selectedOrchestra = $selectedCategoryId;
    }

    if (!$selectedEmotion && in_array($selectedCategoryId, $emotionIds, true)) {
        $selectedEmotion = [$selectedCategoryId];
    }
}

$resolvedCategoryId = $selectedCategoryId ?: $selectedOrchestra ?: (!empty($selectedEmotion) ? (int) $selectedEmotion[0] : 0);

$action = Route::_('index.php');

$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_vg_search_eb.main', 'modules/mod_vg_search_eb/media/css/search-eb.css');
$wa->registerAndUseScript('mod_vg_search_eb.main', 'modules/mod_vg_search_eb/media/js/vg-search-eb.js');

require ModuleHelper::getLayoutPath('mod_vg_search_eb', $params->get('layout', 'default'));
