<?php
/**
 * Layout column for search result
 *  supports carousel on mobile
 * @author vangogh
 * @version 1.0.0
 */

use Joomla\CMS\Uri\Uri;

$events = $displayData;

if (empty($events)) {
    echo '<p>Events not found</p>';
    return;
}
?>

<div class="eb-container vg-search-result">
    <div class="eb-events-default ebm-upcoming-events list-item-events for-detail">
        <div class="row owl-carousel owl-theme">
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
            <div class="box-item item">
                <div class="up-event-item item">
                    <div class="box-inner has-partner">
                        <div class="box-lead">
                            <div class="thumb-event">
                                <a href="<?php echo $urlEventDetail; ?>">
                                    <img src="<?= $thumb; ?>"
                                         class="eb-thumb-left <?= htmlspecialchars($noImgClass); ?>"
                                         alt="<?= htmlspecialchars($event->title, ENT_COMPAT, 'UTF-8'); ?>">
                                </a>
                                <?php if ($sponsor): ?>
                                <div class="list-partner">
                                    <ul>
                                        <li>
                                            <div class="icon">
                                                <img src="<?= Uri::root() . $sponsor->logo; ?>" alt="">
                                            </div>
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
                        </div>
                        <div class="box-ct" style="height: 177.531px;">
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
                                    <span><?= htmlspecialchars($eventEndDate['date'], ENT_COMPAT, 'UTF-8'); ?></span></div>
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
            <?php endforeach; ?>
        </div>

        <script type="text/javascript">
			jQuery(document).ready(function () {
				setTimeout(function () {
					let carouselItems = jQuery('.eb-events-default').find('.item');
					let hgRemove = jQuery('.eb-events-default .box-lead').outerHeight();

					updateItemsHeight();
					jQuery(window).resize(updateItemsHeight);

					function updateItemsHeight() {

						// calculate new one
						let maxHeight = 0;
						carouselItems.each(function () {
							maxHeight = Math.max(maxHeight, jQuery(this).outerHeight());
						});

						// set new value
						carouselItems.each(function () {
							jQuery(this).find('.box-ct').outerHeight(maxHeight - hgRemove);
						});
					}
				}, 1000);


				var owl = jQuery(".for-detail .owl-carousel");
				if (jQuery(window).width() < 992) {
					owl.owlCarousel({
						responsive: {
							0: {
								items: 1,
								stagePadding: 20,
								margin: 20,
							},

							580: {
								items: 3,
								margin: 20,
							},

							768: {
								items: 3,
								margin: 30,
							},
						},
						addClassActive: true,
						loop: false,
						nav: true,
						navText: ["<span class='fal fa-arrow-left'></span>", "<span class='fal fa-arrow-right'></span>"],
						dots: false,
						autoplay: false,
						slideTransition: 'linear',
						smartSpeed: 1000,
						autoplaySpeed: 1500,
						autoplayTimeout: 1500,
						mouseDrag: false,
						autoplayHoverPause: true,
						onInitialized: function () {
							jQuery('.owl-stage-outer').css({'margin-right': '-80px'});
						}
					});
				}
			});
        </script>
    </div>
</div>