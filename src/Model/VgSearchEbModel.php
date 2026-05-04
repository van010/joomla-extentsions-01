<?php

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;

/**
 * @package     ${NAMESPACE}
 * @author      vangogh
 * @since       1.0.0
 */
class VgSearchEbModel
{

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
}

?>