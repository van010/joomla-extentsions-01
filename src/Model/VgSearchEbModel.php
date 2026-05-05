<?php

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

/**
 * @package     ${NAMESPACE}
 * @author      vangogh
 * @since       1.0.0
 */
trait IdeaQueryTrait
{
	/**
	 * A raw query for filter core
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	protected static function getRawQuery(): string
	{
		$q = "
			SELECT DISTINCT e.*
			FROM jos_eb_events AS e
			LEFT JOIN jos_eb_event_categories AS ec ON e.id = ec.event_id
			LEFT JOIN jos_eb_locations AS l ON l.id = e.location_id
			WHERE 
			    -- 1. Category filter (main OR additional)
			    (
			        e.main_category_id = :category_id 
			        OR ec.category_id = :category_id
			    )
			    
			    -- 2. Additional categories (ALL must match)
			    AND (
			        :additional_categories IS NULL 
			        OR (
			            SELECT COUNT(DISTINCT ec2.category_id) 
			            FROM jos_eb_event_categories ec2 
			            WHERE ec2.event_id = e.id 
			            AND ec2.category_id IN (:additional_categories)
			        ) = (
			            SELECT COUNT(*) 
			            FROM (SELECT :additional_categories) AS temp
			        )
			    )
			    
			    -- 3. Location filter
			    AND (
			        :location_id IS NULL 
			        OR e.location_id = :location_id
			    )
			    
			    -- 4. Date filter
			    AND (
			        :event_date IS NULL 
			        OR DATE(e.event_date) = :event_date
			    )
			    
			    -- Only published/active events
			    AND e.published = 1
				AND e.hidden = 0
				AND e.event_end_date >= :currentDate
				AND l.published = 1
			ORDER BY e.event_date ASC;
			";

		return $q;
	}
}

class VgSearchEbModel
{

	use IdeaQueryTrait;

	/**
	 * Get filtered events
	 *
	 * @param   array  $data  Filter parameters
	 * @return  array
	 */
	public static function getEvents(array $data): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('DISTINCT e.*, l.name AS location_name, l.id AS location_id')
			->from($db->quoteName('#__eb_events', 'e'))
			->leftJoin($db->quoteName('#__eb_event_categories', 'ec') . ' ON e.id = ec.event_id')
			->leftJoin($db->quoteName('#__eb_locations', 'l') . ' ON e.location_id = l.id');

		// 1. Date Range
		if (!empty($data['filterFromDate']))
		{
			$query->where($db->quoteName('e.event_date') . ' >= :fromDate')
				->bind(':fromDate', $data['filterFromDate'], ParameterType::STRING);
		}
		if (!empty($data['filterToDate']))
		{
			$query->where($db->quoteName('e.event_date') . ' <= :toDate')
				->bind(':toDate', $data['filterToDate'], ParameterType::STRING);
		}

		// 2. Orchestra Category
		if (!empty($data['orchestraCategoryId']))
		{
			$catId = (int) $data['orchestraCategoryId'];
			$query->where('(' . $db->quoteName('e.main_category_id') . ' = :catId OR ' . $db->quoteName('ec.category_id') . ' = :catId)')
				->bind(':catId', $catId, ParameterType::INTEGER);
		}

		// 3. Location
		if (!empty($data['locationId']))
		{
			$query->where($db->quoteName('e.location_id') . ' = :locId')
				->bind(':locId', $data['locationId'], ParameterType::INTEGER);
		}

		// 4. Emotion Categories (ALL must match)
		$emotionIds = array_filter((array) $data['emotionCategoryId'], fn($v) => (int) $v > 0);

		if (!empty($emotionIds))
		{
			$count = count($emotionIds);
			// Safe to implode since we already filtered to positive integers
			$idsList = implode(',', array_map('intval', $emotionIds));

			$subQuery = $db->getQuery(true)
				->select('event_id')
				->from($db->quoteName('#__eb_event_categories'))
				->where($db->quoteName('category_id') . ' IN (' . $idsList . ')')
				->group('event_id')
				->having('COUNT(DISTINCT category_id) = ' . $count);

			$query->where($db->quoteName('e.id') . ' IN (' . $subQuery . ')');
		}
		$query->where($db->quoteName('e.published') . ' = 1')
			->where($db->quoteName('e.hidden') . ' = 0')
			->where('l.`published` = 1')
			->where('e.`event_end_date` >= :currentDate')
			->bind(':currentDate', ModVgSearchEbHelper::getCurrentDate())
		;

		$query->order($db->quoteName('e.event_date') . ' ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get published locations in eb for filter selection
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function getVenues(): array
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName(['l.id', 'l.name']))
            ->from($db->quoteName('#__eb_locations', 'l'))
            ->where($db->quoteName('l.published') . ' = 1')
            ->order($db->quoteName('l.name'));

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

	/**
	 * Get child categories of Orchestra in eb for filter selection
	 *
	 * @param   int    $parentId
	 * @param   array  $orchestraNames
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function getOrchestraOptions(int $parentId = 0, array $orchestraNames = []): array
    {
        if ($parentId <= 0) {
            $parentId = VgSearchEbModel::getCategoryIdByTitle('Partners');
        }

        if ($parentId <= 0) {
            return [];
        }

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('c.id'))
            ->select($db->quoteName('c.name', 'title'))
            ->from($db->quoteName('#__eb_categories', 'c'))
            ->where($db->quoteName('c.published') . ' = 1')
            ->where($db->quoteName('c.parent') . ' = :parentId')
            ->order($db->quoteName('c.name'));

        $query->bind(':parentId', $parentId, ParameterType::INTEGER);

        $orchestraNames = array_values(array_filter(array_map('trim', $orchestraNames)));

        if (!empty($orchestraNames)) {
            $quotedNames = array_map([$db, 'quote'], $orchestraNames);
            $query->where($db->quoteName('c.name') . ' IN (' . implode(',', $quotedNames) . ')');
        }

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

	/**
	 * Get additional categories or Genres of an Event in eb for filter selection
	 *
	 * @param   string  $groupName
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function getTaxonomyOptions(string $groupName): array
	{
		$parentId = VgSearchEbModel::getCategoryIdByTitle($groupName);

        if ($parentId <= 0) {
            return [];
        }

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select($db->quoteName('c.id'))
            ->select($db->quoteName('c.name', 'title'))
            ->from($db->quoteName('#__eb_categories', 'c'))
            ->where($db->quoteName('c.published') . ' = 1')
            ->where($db->quoteName('c.parent') . ' = :parentId')
            ->order($db->quoteName('c.name'));

        $query->bind(':parentId', $parentId, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObjectList() ?: [];
	}

	/**
	 * @param   string  $title
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public static function getCategoryIdByTitle(string $title): int
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('c.id'))
			->from($db->quoteName('#__eb_categories', 'c'))
			->where($db->quoteName('c.published') . ' = 1')
			->where($db->quoteName('c.name') . ' = :title');

		$query->bind(':title', $title, ParameterType::STRING);

		return (int) $db->setQuery($query)->loadResult();
	}

	/**
	 * Get active upcomingevents menu id of com_eventbooking
	 *
	 * @return int|null
	 *
	 * @since 1.0.0
	 */
	public static function getEbUpComingEventsMenu(): ?int
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$searchTerm = '%option=com_eventbooking&view=upcomingevents%';
		$query->select('id')
			->from('#__menu')
			->where('`published` = 1')
			->where('`link` LIKE :search')
			->bind(':search', $searchTerm);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * @param   string|null  $key
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function getEbConfig(?string $key): string
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('config_value')
			->from('#__eb_configs');
		if ($key) {
			$query->where('`config_key` = :key')
				->bind(':key', $key);
		}
		$db->setQuery($query);
		$config = $db->loadObject()->config_value;

		return $config;
	}

	public static function getSponsorByEventId(?int $eid): ?object
	{
		/** @var $db Joomla\Database\DatabaseDriver */
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('DISTINCT s.*')
			->from('#__eb_sponsors AS s')
			->leftJoin('#__eb_event_sponsors AS es ON es.sponsor_id = s.id')
			->where('es.`event_id` = :eid')
			->bind(':eid', $eid);
		$db->setQuery($query);

		return $db->loadObject();
	}
}

?>