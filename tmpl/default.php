<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// Determine layout class
$layoutClass = !empty($moduleLayout) ? 'vg-search-eb-' . htmlspecialchars($moduleLayout, ENT_QUOTES, 'UTF-8') : 'vg-search-eb-horizontal';
?>

<div class="vg-search-eb-container">
    <script type="text/javascript">
        var vgSearchEbData = <?= json_encode($module); ?>;
        var modVgSearchEbId = <?= (int) $module->id; ?>;
    </script>

    <form class="vg-search-eb <?= $layoutClass; ?> mod-vg-search-eb-<?php echo (int) $module->id; ?>">
        <?php if (!empty($itemId)) : ?>
            <input type="hidden" name="Itemid" value="<?php echo (int) $itemId; ?>">
        <?php endif; ?>

        <input type="hidden" name="search_result_layout" value="<?php echo htmlspecialchars($searchResultLayout, ENT_COMPAT, 'UTF-8'); ?>">
        <input type="hidden" name="category_id" id="vg_filter_category_id" value="<?php echo (int) $resolvedCategoryId; ?>">
        <input type="hidden" name="filter_to_date" id="vg_filter_to_date" value="<?php echo htmlspecialchars($selectedDate, ENT_COMPAT, 'UTF-8'); ?>">
        <?php echo HTMLHelper::_('form.token'); ?>

        <div class="eb-filter-container">
            <?php if (!empty($filters['orchestra'])) : ?>
                <div class="mb-3">
                    <label for="vg_filter_orchestra" class="form-label">
                        <?php echo Text::_('MOD_VG_SEARCH_EB_ORCHESTRA'); ?>
                    </label>
                    <select name="orchestra_category_id" id="vg_filter_orchestra" class="form-select">
                        <option value=""><?php echo htmlspecialchars($placeholderOrchestra, ENT_COMPAT, 'UTF-8'); ?></option>
                        <?php foreach ($filters['orchestra'] as $item) : ?>
                            <option value="<?php echo (int) $item->id; ?>"
                                <?php echo (int) $item->id === (int) $selectedOrchestra ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($filters['genres'])) : ?>
                <div class="mb-3">
                    <label for="vg_filter_genres" class="form-label">
                        <?php echo Text::_('MOD_VG_SEARCH_EB_GENRES'); ?>
                    </label>
                    <?php echo ModVgSearchEbHelper::fancySelectMultiple(
                        $filters['genres'],
                        'genres[]',
                        'vg_filter_genres',
                        $selectedGenres,
                        $placeholderGenres
                    ); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($filters['emotion'])) : ?>
                <div class="mb-3">
                    <label for="vg_filter_emotion" class="form-label">
                        <?php echo Text::_('MOD_VG_SEARCH_EB_EMOTION'); ?>
                    </label>
                    <?php echo ModVgSearchEbHelper::fancySelectMultiple(
                        $filters['emotion'],
                        'emotion_category_id[]',
                        'vg_filter_emotion',
                        $selectedEmotion,
                        $placeholderEmotion
                    ); ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label for="vg_filter_date" class="form-label">
                    <?php echo Text::_('MOD_VG_SEARCH_EB_DATE'); ?>
                </label>
                <?php echo HTMLHelper::_('calendar', $selectedDate, 'filter_from_date', 'vg_filter_date', '%Y-%m-%d', [
                    'class' => 'form-control',
                    'placeholder' => 'Select date'
                ]); ?>
            </div>

            <?php if (!empty($filters['venues'])) : ?>
                <div class="mb-3">
                    <label for="vg_filter_venue" class="form-label">
                        <?php echo Text::_('MOD_VG_SEARCH_EB_VENUE'); ?>
                    </label>
                    <select name="location_id" id="vg_filter_venue" class="form-select">
                        <option value=""><?php echo htmlspecialchars($placeholderVenue, ENT_COMPAT, 'UTF-8'); ?></option>
                        <?php foreach ($filters['venues'] as $venue) : ?>
                            <option value="<?php echo (int) $venue->id; ?>"
                                <?php echo (int) $venue->id === (int) $selectedVenue ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($venue->name, ENT_COMPAT, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="vg-filter-actions">
            <button type="button" data-action="search" class="btn btn-primary">
                <?php echo Text::_('MOD_VG_SEARCH_EB_SEARCH'); ?>
            </button>

            <button type="button" data-action="reset" class="btn btn-secondary">
                <?php echo Text::_('MOD_VG_SEARCH_EB_RESET'); ?>
            </button>
        </div>
    </form>

    <div class="vg-search-result-container vg-search-eb-result-<?= $module->id; ?>">
    </div>
</div>