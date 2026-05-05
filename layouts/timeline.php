<?php

/**
 * Layout timeline for search result
 * @author vangogh
 * @version 1.0.0
 */

$events = $displayData;
if (empty($events)) {
    echo '<p>Events not found</p>';
    return ;
}

?>

<div class="eb-container vg-search-result">
    <div class="eb-events-timeline">
        <?php foreach ($events as $event):
            $parseEventDate = ModVgSearchEbHelper::splitDate($event->event_date);
            // $urlEventDetail = "/j5-studentpulse/index.php?option=com_eventbooking&amp;view=event&amp;id=".$event->id."&amp;catid=".$event->main_category_id."&amp;Itemid=103";
            $urlEventDetail = ModVgSearchEbHelper::parseUrlEventDetail($event);
            $urlLocation = ModVgSearchEbHelper::parseUrlLocation($event);
            $eventDate = ModVgSearchEbHelper::parseDateTimeToArray($event->event_date);
            $eventEndDate = ModVgSearchEbHelper::parseDateTimeToArray($event->event_end_date);
            $price = VgSearchEbModel::getEbConfig('currency_symbol') . $event->individual_price;
            $thumb = ModVgSearchEbHelper::getImageThumb($event->image);
            $noImgClass = empty($thumb) ? 'no-img' : '';
            ?>
        <div class="eb-category-<?= $event->id; ?> eb-event-container">
            <div class="eb-event-date-container">
                <div class="eb-event-date btn-primary">
                    <div class="eb-event-date-day">
                        <?= $parseEventDate['day']; ?>
                    </div>
                    <div class="eb-event-date-month">
                        <?= $parseEventDate['month']; ?>
                    </div>
                    <div class="eb-event-date-year">
                        <?= $parseEventDate['year']; ?>
                    </div>
                </div>
            </div>
            <div class="event-item-timeline">
                <div class="timeline-box">
                    <div class="row timeline">
                        <div class="col-12 col-md-4 col-sm-6">
                            <div class="eb-description-details clearfix">
                                <a href="<?= $urlEventDetail; ?>"><img
                                            src="<?= $thumb; ?>"
                                            class="eb-thumb-left <?= $noImgClass; ?>" alt="<?= $event->title; ?>"></a>
                            </div>
                        </div>

                        <div class="col-12 col-md-8 col-sm-6">
                            <div class="event-info-timeline">
                                <h2 class="eb-even-title-container">
                                    <a class="eb-event-title"
                                       href="<?= $urlEventDetail; ?>"><?= $event->title; ?></a>
                                </h2>


                                <div class="event-intro">
                                    <?= $event->short_description; ?></div>

                                <div class="eb-event-information">
                                    <div class="event-details">
                                        <div class="eb-event-date-info clearfix">
                                            <i class="fa fa-calendar"></i>
                                            <?php if($eventDate['date']): ?>
                                            <?= $eventDate['date']; ?> <span class="eb-time"><?= $eventDate['time']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($eventEndDate['date']): ?>
                                            - <?= $eventEndDate['date']; ?> <span class="eb-time"><?= $eventEndDate['time']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="clearfix">
                                            <span class="ic-img ic-pin2"></span>
                                            <a href="<?= $urlLocation; ?>"><span><?= ucfirst($event->location_name); ?></span></a>
                                        </div>
                                    </div>
                                    <div class="event-price">
                                        <div class="eb-event-price-container btn-primary">
                                            <span class="eb-individual-price"><?= $price; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>