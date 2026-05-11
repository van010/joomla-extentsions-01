<?php
/**
 * Layout column for search result
 * @version 1.0.0
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$events = $displayData;

if (empty($events)) {
    echo Text::_('MOD_VG_SEARCH_EB_EVENT_NOT_FOUND');
    return;
}
?>

<div class="eb-container vg-search-result">
    <div class="row clearfix ebm-upcoming-events list-item-events">
        <?php foreach ($events as $event):
                $urlEventDetail = ModVgSearchEbHelper::parseUrlEventDetail($event);
                $thumb = ModVgSearchEbHelper::getImageThumb($event->image);
                $price = VgSearchEbModel::getEbConfig('currency_symbol') . $event->individual_price;
                $urlLocation = ModVgSearchEbHelper::parseUrlLocation($event);
                $eventDate = ModVgSearchEbHelper::parseDateTimeToArray($event->event_date);
                $eventEndDate = ModVgSearchEbHelper::parseDateTimeToArray($event->event_end_date);
                $noImgClass = empty($thumb) ? 'no-img' : '';
                $sponsor = VgSearchEbModel::getSponsorByEventId($event->id);
            ?>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="box-item">
                <div class="up-event-item item">
                    <div class="box-inner has-partner">
                        <div class="box-lead">
                            <div class="thumb-event">
                                <a href="<?= $urlEventDetail; ?>">
                                    <img src="<?= $thumb; ?>"
                                         class="eb-thumb-left <?= $noImgClass; ?>"
                                         alt="<?= htmlspecialchars(ucfirst($event->title), ENT_COMPAT, 'UTF-8'); ?>">
                                </a>
                            </div>
                            <?php if ($sponsor): ?>
                            <div class="list-partner">
                                <ul>
                                    <li>
                                        <div class="icon">
                                            <img src="<?= Uri::root() . $sponsor->logo; ?>" alt="">
                                        <div class="title">
                                            <a href="<?= $sponsor->website??''; ?>"
                                               title="<?= htmlspecialchars(ucfirst($sponsor->name), ENT_COMPAT, 'UTF-8'); ?>">
                                                <?= htmlspecialchars(ucfirst($sponsor->name), ENT_COMPAT, 'UTF-8'); ?>
                                            </a>
                                        </div>
                                    </li>

                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="box-ct">
                            <div class="title-event">
                                <h6 class="my-0">
                                    <a href="<?= $urlEventDetail; ?>"><?= htmlspecialchars($event->title, ENT_COMPAT, 'UTF-8'); ?></a>
                                </h6>
                            </div>

                            <div class="location-event">
                                <span class="ic-img ic-pin2"></span>
                                <a href="<?= $urlLocation; ?>"><span><?= htmlspecialchars(ucfirst($event->location_name), ENT_COMPAT, 'UTF-8'); ?></span></a>
                            </div>

                            <div class="event-meta">
                                <div class="date-event">
                                    <span><?= htmlspecialchars($eventEndDate['date'], ENT_COMPAT, 'UTF-8'); ?></span>
                                </div>
                                <?php if($event->individual_price): ?>
                                <div class="price-event">
                                    <span><?= htmlspecialchars($price, ENT_COMPAT, 'UTF-8'); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>