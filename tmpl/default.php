<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>

<script type="text/javascript">
	var vgSearchEbData = <?= json_encode($module); ?>;
	var modVgSearchEbId = <?= json_decode($module->id); ?>;
</script>

<form class="vg-search-eb mod-vg-search-eb-<?= $module->id; ?>">
    <?php if (!empty($itemId)) : ?>
        <input type="hidden" name="Itemid" value="<?php echo (int) $itemId; ?>">
    <?php endif; ?>
    <input type="hidden" name="category_id" id="vg_filter_category_id" value="<?php echo (int) $resolvedCategoryId; ?>">
    <input type="hidden" name="filter_to_date" id="vg_filter_to_date" value="<?php echo htmlspecialchars($selectedDate, ENT_COMPAT, 'UTF-8'); ?>">
    <div class="eb-filter-container">
        <?php if (!empty($filters['genres'])) : ?>
            <div class="mb-3">
                <label for="vg_filter_genres"
                       class="form-label"><?php echo Text::_('MOD_VG_SEARCH_EB_GENRES'); ?></label>
                <select name="genres[]" id="vg_filter_genres" class="form-select" multiple size="5">
                    <?php foreach ($filters['genres'] as $item) : ?>
                        <option value="<?php echo (int) $item->id; ?>" <?php echo in_array((int) $item->id, $selectedGenres, true) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (!empty($filters['orchestra'])) : ?>
            <div class="mb-3">
                <label for="vg_filter_orchestra"
                       class="form-label"><?php echo Text::_('MOD_VG_SEARCH_EB_ORCHESTRA'); ?></label>
                <select name="orchestra_category_id" id="vg_filter_orchestra" class="form-select">
                    <option value=""><?php echo Text::_('MOD_VG_SEARCH_EB_SELECT_ORCHESTRA'); ?></option>
                    <?php foreach ($filters['orchestra'] as $item) : ?>
                        <option value="<?php echo (int) $item->id; ?>" <?php echo (int) $item->id === (int) $selectedOrchestra ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (!empty($filters['emotion'])) : ?>
            <div class="mb-3">
                <label for="vg_filter_emotion"
                       class="form-label"><?php echo Text::_('MOD_VG_SEARCH_EB_EMOTION'); ?></label>
                <select name="emotion_category_id[]" id="vg_filter_emotion" class="form-select" multiple size="5">
                    <?php foreach ($filters['emotion'] as $item) : ?>
                        <option value="<?php echo (int) $item->id; ?>" <?php echo in_array((int) $item->id, $selectedEmotion, true) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="vg_filter_date" class="form-label"><?php echo Text::_('MOD_VG_SEARCH_EB_DATE'); ?></label>
            <?php echo HTMLHelper::_('calendar', $selectedDate, 'filter_from_date', 'vg_filter_date', '%Y-%m-%d', ['class' => 'form-control']); ?>
        </div>

        <?php if (!empty($filters['venues'])) : ?>
            <div class="mb-3">
                <label for="vg_filter_venue" class="form-label"><?php echo Text::_('MOD_VG_SEARCH_EB_VENUE'); ?></label>
                <select name="location_id" id="vg_filter_venue" class="form-select">
                    <option value=""><?php echo Text::_('MOD_VG_SEARCH_EB_ALL_VENUES'); ?></option>
                    <?php foreach ($filters['venues'] as $venue) : ?>
                        <option value="<?php echo (int) $venue->id; ?>" <?php echo (int) $venue->id === (int) $selectedVenue ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($venue->name, ENT_COMPAT, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <button type="button"
                onclick="searchEvents()"
                class="btn btn-primary"><?php echo Text::_('MOD_VG_SEARCH_EB_SEARCH'); ?></button>
        <button type="button"
                class="btn btn-secondary vg-search-eb-reset"
                onclick="resetSearch()">
            <?php echo Text::_('MOD_VG_SEARCH_EB_RESET'); ?>
        </button>
    </div>
</form>

<div class="vg-search-eb-result-<?= $module->id; ?>">
</div>

<script>
</script>
