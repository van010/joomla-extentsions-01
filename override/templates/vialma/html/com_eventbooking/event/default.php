<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2022 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

$item = $this->item ;

EventbookingHelperData::prepareDisplayData([$item], @$item->main_category_id, $this->config, $this->Itemid);

$socialUrl = Uri::getInstance()->toString(array('scheme', 'user', 'pass', 'host')) . $item->url;

/* @var EventbookingHelperBootstrap $bootstrapHelper*/
$bootstrapHelper   = $this->bootstrapHelper;
$iconPencilClass   = $bootstrapHelper->getClassMapping('icon-pencil');
$iconOkClass       = $bootstrapHelper->getClassMapping('icon-ok');
$iconRemoveClass   = $bootstrapHelper->getClassMapping('icon-remove');
$iconDownloadClass = $bootstrapHelper->getClassMapping('icon-download');
$btnClass          = $bootstrapHelper->getClassMapping('btn');
$iconPrint         = $bootstrapHelper->getClassMapping('icon-print');
$clearfixClass     = $bootstrapHelper->getClassMapping('clearfix');
$return = base64_encode(Uri::getInstance()->toString());

$isMultipleDate = false;

if ($this->config->show_children_events_under_parent_event && $item->event_type == 1)
{
	$isMultipleDate = true;
}

$offset = Factory::getApplication()->get('offset');

if ($this->showTaskBar)
{
	$layoutData = array(
		'item'              => $this->item,
		'config'            => $this->config,
		'isMultipleDate'    => $isMultipleDate,
		'canRegister'       => $item->can_register,
		'registrationOpen'  => $item->registration_open,
		'waitingList'       => $item->waiting_list,
		'return'            => $return,
		'showInviteFriend'  => true,
		'ssl'               => (int) $this->config->use_https,
		'Itemid'            => $this->Itemid,
		'btnClass'          => $btnClass,
		'iconOkClass'       => $iconOkClass,
		'iconRemoveClass'   => $iconRemoveClass,
		'iconDownloadClass' => $iconDownloadClass,
		'iconPencilClass'   => $iconPencilClass,
	);

	$registerButtons = EventbookingHelperHtml::loadCommonLayout('common/buttons.php', $layoutData);
}

if (!$this->config->get('show_group_rates', 1))
{
	$this->rowGroupRates = [];
}

$cssClasses = ['eb-container', 'eb-category-' . $item->category_id, 'eb-event'];

if ($item->featured)
{
	$cssClasses[] = 'eb-featured-event';
}

if ($item->published == 2)
{
	$cssClasses[] = 'eb-cancelled-event';
}

if ($this->input->getInt('hmvc_call'))
{
	$hTag = 'h2';
}
else
{
	$hTag = 'h1';
}

$info_field = json_decode($item->custom_fields, true);

$eventGenresDisplay = [];

if (PluginHelper::isEnabled('eventbooking', 'genres'))
{
	$genrePlugin = PluginHelper::getPlugin('eventbooking', 'genres');

	if ($genrePlugin && (int) (new Registry($genrePlugin->params))->get('show_on_frontend', 0) === 1)
	{
		$eventParams   = new Registry($item->params ?? '{}');
		$rawEbGenres   = $eventParams->get('eb_genres', '');
		$decodedGenres = null;

		if (is_array($rawEbGenres))
		{
			$decodedGenres = $rawEbGenres;
		}
		elseif (is_string($rawEbGenres) && $rawEbGenres !== '')
		{
			$decodedGenres = json_decode($rawEbGenres, true);
		}

		if (is_array($decodedGenres))
		{
			$eventGenresDisplay = array_values(array_filter(array_map(static function ($g) {
				return trim((string) $g);
			}, $decodedGenres), static function ($g) {
				return $g !== '';
			}));
		}
	}
}

?>
<div id="eb-event-page" class="<?php echo implode(' ', $cssClasses); ?>">
	<div class="row">
		<div class="col-12 col-lg-5 content-left">
			<div class="thumb-event">
				<?php if ($this->config->get('show_image_in_event_detail', 1) && $this->config->display_large_image && !empty($item->image_url))
				{
				?>
					<img src="<?php echo $item->image_url; ?>" class="eb-event-large-image img-polaroid"/>
				<?php
				}
				elseif ($this->config->get('show_image_in_event_detail', 1) && !empty($item->thumb_url))
				{
					EventbookingHelperJquery::colorbox('a.eb-modal');
				?>
					<a href="<?php echo $item->image_url; ?>" class="eb-modal"><img src="<?php echo $item->thumb_url; ?>" class="eb-thumb-left" alt="<?php echo $item->title; ?>"/></a>
				<?php } ?>
			</div>

			<div class="hide-mobile">
				<?php if ($this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), array(0, 2)))
				{
				?>
					<div class="eb-taskbar eb-register-buttons-bottom">
						<ul>
							<?php echo $registerButtons; ?>
						</ul>
					</div>
				<?php
				} ?>

				<!-- <?php if(isset($info_field['mynote2'])): ?>	
					<div class="note-field">
						<?php
							echo $info_field['mynote2']; 
						?>
					</div>
				<?php endif; ?> -->

				<div class="note-field mt-2">
					<p>Student Pulse tickets are sold out when the "Buy Now" button above is not displayed.</p>
					<p>Please check our <a href="<?php echo $info_field['mynote']; ?>" target="_blank">partner's website</a> as other tickets may still be available.</p>
				</div>
			</div>
		</div>
		<div class="col-12 col-lg-7 content-right">
			<div class="eb-box-heading <?php echo $clearfixClass; ?>">
				<div class="eb-page-heading">
					<h1><?php echo $item->title;?></h1>
				</div>

				<?php if ($this->config->get('show_print_button', '1') === '1' && !$this->print)
				{
					$uri = clone Uri::getInstance($this->url);
					$uri->setVar('tmpl', 'component');
					$uri->setVar('print', '1');
				?>
					<div id="pop-print" class="btn hidden-print">
						<a href="<?php echo $uri->toString();?> " rel="nofollow" target="_blank">
							<i class="fal fa-print"></i>
						</a>
					</div>
				<?php
				}
				?>
			</div>

			<?php if ($item->short_description != '') : ?>
	            <div class="short-desc">
	                <p class="mb-0"><?php echo $item->short_description; ?></p>
	            </div>
	        <?php endif; ?>

<!-- 			<?php if(isset($info_field['mypartner'])): ?>	
				<div class="area-partner ele-event">
					<?php echo $info_field['mypartner'];?>
				</div>
			<?php endif; ?> -->


			<?php foreach ($this->horizontalPlugins as $plugin){
					if ($plugin['name'] == "speakers") continue;
					echo $plugin['form'];
				}	
			?>


			<?php if($item->location != ''): ?>
			<div class="area-location ele-event">
				<div class="icons"><span class="ic-img ic-pin"></span></div>
				<div class="ct-desc">
					<?php echo $item->location->name?>
				</div>
			</div>
			<?php endif; ?>

			<div class="area-date ele-event">
				<div class="icons">
					<span class="ic-img ic-calendar"></span>
				</div>
				<div class="ct-desc">
					<?php 
						$date = new DateTime($item->event_date);
						echo '<span class="date">'.HTMLHelper::_('date', $item->event_date, $this->config->event_date_format, null).'</span>';
						echo $date->format("g"). '.' .$date->format("i"). ' ' .$date->format("a");
					?>
				</div>
			</div>

			<div class="time-duration ele-event">
				<div class="icons">
					<span class="ic-img ic-clock"></span>
				</div>

				<?php
				//echo $item->event_date."<br/>";
				//echo $item->event_end_date."<br/>";

				$datetime1 = new DateTime($item->event_end_date);
				$datetime2 = new DateTime($item->event_date);

				$difference = $datetime1->diff($datetime2);

				$hours = $difference->h;
				$hours = $hours + ($difference->days*24);

				//$hour   = $difference->format('%h'); 
				$minutes = $difference->format('%i');
				//echo  'Diff. in minutes is: '.($hour * 60 + $minutes);
				 //echo $difference->format('%y year %m month %d days %h hour %i minute %s second')."<br/>";

				 echo $hours.' hours ' .$minutes. ' minutes ';
				?>
			</div>

			<?php if (!empty($eventGenresDisplay)) : ?>
				<?php Factory::getApplication()->getLanguage()->load('plg_eventbooking_genres', JPATH_PLUGINS . '/eventbooking/genres'); ?>
				<div class="area-genres ele-event">
				<div class="icons" aria-hidden="true">
					<i class="fal fa-music eb-event-genres__icon"></i>
				</div>
				<div class="ct-desc">
					<!-- <strong class="eb-event-genres__label"><?php echo Text::_('PLG_EVENTBOOKING_GENRES_LABEL'); ?>:</strong> -->
					<span class="eb-event-genres__values"><?php echo htmlspecialchars(implode(', ', $eventGenresDisplay), ENT_COMPAT, 'UTF-8'); ?></span>
				</div>
			</div>
			<?php endif; ?>

			<?php if(isset($info_field['performer']) && !empty(trim($info_field['performer'])) || isset($info_field['myconductor']) && !empty(trim($info_field['myconductor']))): ?>
			<div class="area-speaker ele-event">
				<div class="icons"><span class="ic-img ic-user"></span></div>
				<div class="ct-desc">
					
					<!-- <div class="ele">
						<h6>Performers</h6>
					<?php foreach ($this->horizontalPlugins as $plugin){
							echo $plugin['form'];
						}	
					?>
					</div> -->

					<div class="ele">

						<?php if(isset($info_field['myconductor']) && !empty(trim($info_field['myconductor']))): ?>
							<h6>Conductor(s):</h6>
							<span>
								<?php echo $info_field['myconductor'];?>
							</span>
						<?php endif; ?>

						
					</div>

					<div class="ele">
						<?php if(isset($info_field['performer']) && !empty(trim($info_field['performer']))): ?>
							<h6>Performer(s):</h6>
							<span>
								<?php echo $info_field['performer'];?>
							</span>
						<?php endif; ?>
					</div>

				</div>
			</div>
			<?php endif; ?>

			<?php if(isset($item->ticketTypes)): ?>
				<div class="list-ticket">
					<div class="owl-carousel owl-theme">
						<?php
							foreach ($item->ticketTypes as $ticket) {
							?>
							<div class="item">
								<div class="time-ticket">
									<?php 
										$date = new DateTime($ticket->publish_up);
										echo '<span class="date">'.HTMLHelper::_('date', $ticket->publish_up, $this->config->event_date_format, null).'</span>';
										echo $date->format("g"). '.' .$date->format("i"). ' ' .$date->format("A");
									?>
								</div>

								<div class="price-ticket">
									<?php 
										$price = EventbookingHelper::formatCurrency($ticket->price, $this->config);
										echo $price; 
									?>
								</div>
							</div>
							<?php
							}
							?>
					</div>
				</div>
			<?php endif; ?>

			<?php if(isset($info_field['myprogramme']) && !empty(trim($info_field['myprogramme']))): ?>	
				<h4>Programme</h4>
				<div class="desc-ct">
					<?php
						echo $info_field['myprogramme']; 
					?>
				</div>
			<?php endif; ?>

			<div id="accordion-custom" class="accordion-custom">
				<?php if(isset($item->description) && !empty(trim($item->description))): ?>	
				<div class="accordion__item">
						<div class="accordion__title">
							<div class="accordion__arrow"><i class="fal fa-plus"></i></div> 
							<h2>About this event</h2>
						</div>
						<div class="accordion__content">
							<div class="box-content">
								<p class="mb-0"><?php echo $item->description?></p>
							</div>
						</div>
				</div>
				<?php endif; ?>
				<div class="accordion__item">
						<div class="accordion__title">
							<div class="accordion__arrow"><i class="fal fa-plus"></i></div> 
							<h2><?php echo 'Venue details'; ?></h2>
						</div>
						<div class="accordion__content accordion__venue">
							<div class="box-content">
								<?php if($item->location->name != ''): ?>
								<h6 class=""><?php echo $item->location->name; ?></h6>
								<?php endif;?>

								<?php if($item->location->address != ''): ?>
								<p class=""><?php echo $item->location->address; ?></p>
								<?php endif;?>

								<div class="pic-map">
									<?php if($item->location->image != ''): ?>
										<div class="pic-location">
											<img src="<?php echo $item->location->image; ?>" alt="" />
										</div>
									<?php endif;?>

									<div class="area-map">
										<?php
											if (count($this->plugins))
											{
												echo $this->loadTemplate('plugins');
											}
										?>
									</div>
								</div>
							</div>
						</div>
				</div>

				<?php if(isset($info_field['myideas']) && !empty(trim($info_field['myideas']))): ?>	
				<div class="accordion__item">
						<div class="accordion__title">
							<div class="accordion__arrow"><i class="fal fa-plus"></i></div> 
							<h2>Things to do before/after</h2>
						</div>
						<div class="accordion__content">
							<div class="box-content">
								<p class="mb-0">
									<?php 
										if(isset($info_field['mynote'])) { 
									
											//$details = json_decode($item->custom_fields, true);
											echo $info_field['myideas']; 
										}
									?>
								</p>
							</div>
						</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- <?php if(isset($info_field['mynote'])): ?>	
				<div class="content-field">
					<?php
						echo $info_field['mynote']; 
					?>
				</div>
			<?php endif; ?> -->
			<div class="content-field">
				<p class="has-bg">Tickets cannot be exchanged, transferred or refunded</p>
				<p>All Student Pulse ticket holders will be asked to present valid student identification at the venue.</p>
			</div>

			<div class="show-mobile">
				<?php if ($this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), array(0, 2)))
				{
				?>
					<div class="eb-taskbar eb-register-buttons-bottom">
						<ul>
							<?php echo $registerButtons; ?>
						</ul>
					</div>
				<?php
				} ?>

				<!-- <?php if(isset($info_field['mynote2'])): ?>	
					<div class="note-field">
						<?php
							echo $info_field['mynote2']; 
						?>
					</div>
				<?php endif; ?> -->

				<div class="note-field mt-2">
					<p>Student Pulse tickets are sold out when the "Buy Now" button above is not displayed.</p>
					<p>Please check our <a href="<?php echo $info_field['mynote']; ?>" target="_blank">partner's website</a> as other tickets may still be available.</p>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
Factory::getDocument()->addScriptDeclaration('
		function cancelRegistration(registrantId)
		{
			var form = document.adminForm ;
	
			if (confirm("' . Text::_('EB_CANCEL_REGISTRATION_CONFIRM') . '"))
			{
				form.task.value = "registrant.cancel" ;
				form.id.value = registrantId ;
				form.submit() ;
			}
		}
	');
?>
<form name="adminForm" id="adminForm" action="<?php echo Route::_('index.php?option=com_eventbooking&Itemid=' . $this->Itemid); ?>" method="post">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<script type="text/javascript">


	<?php
	if ($this->print)
	{
	?>
		window.print();
	<?php
	}
?>
</script>

<script>
(function($){
  jQuery(document).ready(function($) {
  	$(window).on('load',function(){
		setTimeout(function(){
			$('.accordion__content').hide();
		}, 500);
	});

    $(".accordion__title").on("click", function(e) {
		e.preventDefault();
		var $this = $(this);

		if (!$this.hasClass("accordion-active")) {
			$(".accordion__content").slideUp(400);
			$(".accordion__title").removeClass("accordion-active");
			$('.accordion__arrow').removeClass('accordion__rotate');
		}

		$this.toggleClass("accordion-active");
		$this.next().slideToggle();
		$('.accordion__arrow',this).toggleClass('accordion__rotate');
	});
  });
})(jQuery);
</script>
<?php
Factory::getApplication()->triggerEvent('onDisplayEvents', [[$item]]);
