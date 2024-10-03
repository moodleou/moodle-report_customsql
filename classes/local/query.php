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

use moodle_url;

/**
 * Query class.
 *
 * @package    report_customsql
 * @copyright  2021 The Open Univesity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query {
    /** @var \stdClass The query's record from database. */
    private $record;

    /**
     * Create a new category object.
     *
     * @param \stdClass $record The record from database.
     */
    public function __construct(\stdClass $record) {
        $this->record = $record;
    }

    /**
     * Get query Id.
     *
     * @return int Query's Id.
     */
    public function get_id(): int {
        return $this->record->id;
    }

    /**
     * Get query's display name.
     *
     * @return string Display name.
     */
    public function get_displayname(): string {
        return $this->record->displayname;
    }

    /**
     * Get url to view query.
     *
     * @return moodle_url View url.
     */
    public function get_url(): moodle_url {
        return report_customsql_url('view.php', ['id' => $this->record->id]);
    }

    /** Get url to edit query.
     *
     * @param moodle_url|null $returnurl Return url.
     * @return moodle_url Edit url.
     */
    public function get_edit_url(moodle_url | null $returnurl = null): moodle_url {
        $param = ['id' => $this->record->id];
        if ($returnurl) {
            $param['returnurl'] = $returnurl->out_as_local_url(false);
        }

        return report_customsql_url('edit.php', $param);
    }

    /**
     * Get url to delete query.
     *
     * @param moodle_url|null $returnurl Return url.
     * @return moodle_url Delete url.
     */
    public function get_delete_url(moodle_url | null $returnurl = null): moodle_url {
        $param = ['id' => $this->record->id];
        if ($returnurl) {
            $param['returnurl'] = $returnurl->out_as_local_url(false);
        }

        return report_customsql_url('delete.php', $param);
    }

    /**
     * Get the time note.
     *
     * @return string Time not.
     */
    public function get_time_note() {
        return report_customsql_time_note($this->record, 'span');
    }

    /**
     * Get the text to display the capability.
     *
     * @return string Capability text.
     */
    public function get_capability_string() {
        $capabilities = report_customsql_capability_options();
        return $capabilities[$this->record->capability];
    }

    /**
     * Check if user can edit the query.
     *
     * @param \context $context The context to check.
     * @return bool true if the user has this capability. Otherwise false.
     */
    public function can_edit(\context $context): bool {
        return has_capability('report/customsql:definequeries', $context);
    }

    /**
     * Check the capability to view the query.
     *
     * @param \context $context The context to check.
     * @return bool Has capability to view or not?
     */
    public function can_view(\context $context): bool {
        return empty($report->capability) || has_capability($report->capability, $context);
    }
}
