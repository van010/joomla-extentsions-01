<?php

/**
 * Layout timeline
 * Current code is sample events follow event booking style
 */

$layout = 'timeline';
$events = $displayData;
if (empty($events)) {
    echo "Events not found";
    return ;
}

?>

<div id="eb-upcoming-events-page-<?= $layout; ?>" class="eb-container">
    <div id="eb-events" class="eb-events-<?= $layout; ?>">
        <?php foreach ($events as $event):
            $parseEventDate = ModVgSearchEbHelper::splitDate($event->event_date);
            $urlEventDetail = "/j5-studentpulse/index.php?option=com_eventbooking&amp;view=event&amp;id=".$event->id."&amp;catid=".$event->main_category_id."&amp;Itemid=103";
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
                                            src="/j5-studentpulse/media/com_eventbooking/images/thumbs/gospel.jpg"
                                            class="eb-thumb-left" alt="<?= $event->title; ?>"></a>
                            </div>
                        </div>

                        <div class="col-12 col-md-8 col-sm-6">
                            <div class="event-info-timeline">
                                <h2 class="eb-even-title-container">
                                    <a class="eb-event-title"
                                       href="<?= $urlEventDetail; ?>"><?= $event->title; ?></a>
                                </h2>


                                <div class="event-intro">
                                    <?= htmlspecialchars($event->short_description); ?></div>

                                <div class="eb-event-information">
                                    <div class="event-details">
                                        <div class="eb-event-date-info clearfix">
                                            <i class="fa fa-calendar"></i>
                                            Sun 30 Oct <span class="eb-time">7:00 pm</span>
                                            - Mon 17 Aug <span class="eb-time">9:00 pm</span>
                                        </div>
                                        <div class="clearfix">
                                            <span class="ic-img ic-pin2"></span>
                                            <a href="/j5-studentpulse/index.php?option=com_eventbooking&amp;view=map&amp;location_id=2&amp;Itemid=183"><span>Barbican Hall</span></a>
                                        </div>
                                    </div>
                                    <div class="event-price">
                                        <div class="eb-event-price-container btn-primary">
                                            <span class="eb-individual-price">£9.00</span>
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