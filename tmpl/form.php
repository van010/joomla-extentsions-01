<?php
/**
 * Event edit tab: Genres (fancy multi-select).
 *
 * Layout variables:
 *
 * @var array $allowedGenres
 * @var array $selectedGenres
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$options = [];

foreach ($allowedGenres as $genre)
{
	$options[] = HTMLHelper::_('select.option', $genre, $genre);
}

$displayData = [
	'autocomplete'   => '',
	'autofocus'      => false,
	'class'          => 'form-select',
	'description'    => '',
	'disabled'       => false,
	'group'          => '',
	'hidden'         => false,
	'hint'           => Text::_('JGLOBAL_TYPE_OR_SELECT_SOME_OPTIONS'),
	'id'             => 'eb_genres',
	'label'          => '',
	'labelclass'     => '',
	'multiple'       => true,
	'name'           => 'eb_genres[]',
	'onchange'       => '',
	'onclick'        => '',
	'pattern'        => '',
	'readonly'       => false,
	'repeat'         => false,
	'required'       => false,
	'size'           => '',
	'spellcheck'     => false,
	'validate'       => '',
	'value'          => $selectedGenres,
	'options'        => $options,
	'dataAttribute'  => '',
	'dataAttributes' => [],
];

?>

<div class="control-group">
	<label class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('eb_genres', Text::_('PLG_EVENTBOOKING_GENRES_LABEL'), ''); ?>
	</label>
	<div class="controls">
		<?php echo LayoutHelper::render('joomla.form.field.list-fancy-select', $displayData); ?>
	</div>
</div>
