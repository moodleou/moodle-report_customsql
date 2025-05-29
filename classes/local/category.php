<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_customsql\local;

use report_customsql\utils;

/**
 * Category class.
 *
 * @package    report_customsql
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category {
    /** @var int Category ID. */
    private $id;

    /** @var string Category name. */
    private $name;

    /** @var array Pre-loaded queries data. */
    private $queriesdata;

    /** @var array Pre-loaded statistic data. */
    private $statistic;

    /**
     * Create a new category object.
     *
     * @param \stdClass $record The record from database.
     */
    public function __construct(\stdClass $record) {
        $this->id = $record->id;
        $this->name = $record->name;
    }

    /**
     * Load queries of category from records.
     *
     * @param \stdClass[] $queries Records to load.
     */
    public function load_queries_data(array $queries): void {
        $statistic = [];
        $queriesdata = [];
        foreach (report_customsql_runable_options() as $type => $description) {
            $filteredqueries = self::get_reports_of_a_particular_runtype($queries, $type);
            $filteredqueries = self::filter_reports_by_capability($filteredqueries);
            $statistic[$type] = count($filteredqueries);
            if ($filteredqueries) {
                $queriesdata[] = [
                    'type' => $type,
                    'queries' => $filteredqueries,
                ];
            }
        }
        $this->queriesdata = $queriesdata;
        $this->statistic = $statistic;
    }

    /**
     * Get queries for each type.
     *
     * @param \stdClass[] $queries Array of queries.
     * @param string $type Type to filter.
     * @return \stdClass[] All queries of type.
     */
    public static function get_reports_of_a_particular_runtype(array $queries, string $type) {
        return array_filter($queries, function($query) use ($type) {
            return $query->runable == $type;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Given an array of qureries, remove any that the current user cannot access.
     *
     * @param \stdClass[] $queries Array of queries.
     * @return \stdClass[] queries the current user is allowed to see.
     */
    public static function filter_reports_by_capability(array $queries) {
        return array_filter($queries, function($query) {
            return has_capability($query->capability ?? 'moodle/site:config', \context_system::instance());
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get category ID.
     *
     * @return int Category ID.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get category name.
     *
     * @return string Category name.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get pre-loaded queries' data of this category.
     *
     * @return array Queries' data.
     */
    public function get_queries_data(): array {
        return $this->queriesdata;
    }

    /**
     * Get pre-loaded statistic of this category.
     *
     * @return array Statistic data.
     */
    public function get_statistic(): array {
        return $this->statistic;
    }

    /**
     * Get url to view the category.
     *
     * @return \moodle_url Category's url.
     */
    public function get_url(): \moodle_url {
        return report_customsql_url('category.php', ['id' => $this->id]);
    }
}
