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

namespace report_customsql\output;

use context;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;

/**
 * Ad-hoc database queries renderer class.
 *
 * @package   report_customsql
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Output the standard action icons (edit, delete and back to list) for a report.
     *
     * @param stdClass $report the report.
     * @param stdClass $category Category object.
     * @param context $context context to use for permission checks.
     * @return string HTML for report actions.
     */
    public function render_report_actions(stdClass $report, stdClass $category, context $context): string {
        $editaction = null;
        $deleteaction = null;
        if (has_capability('report/customsql:definequeries', $context)) {
            $reporturl = report_customsql_url('view.php', ['id' => $report->id]);
            $editaction = $this->action_link(
                    report_customsql_url('edit.php', ['id' => $report->id, 'returnurl' => $reporturl->out_as_local_url(false)]),
                    $this->pix_icon('t/edit', '') . ' ' .
                    get_string('editreportx', 'report_customsql', format_string($report->displayname)));
            $deleteaction = $this->action_link(
                    report_customsql_url('delete.php', ['id' => $report->id, 'returnurl' => $reporturl->out_as_local_url(false)]),
                    $this->pix_icon('t/delete', '') . ' ' .
                    get_string('deletereportx', 'report_customsql', format_string($report->displayname)));
        }

        $backtocategoryaction = $this->action_link(
                report_customsql_url('category.php', ['id' => $category->id]),
                $this->pix_icon('t/left', '') .
                get_string('backtocategory', 'report_customsql', $category->name));

        $context = [
                'editaction' => $editaction,
                'deleteaction' => $deleteaction,
                'backtocategoryaction' => $backtocategoryaction,
        ];

        return $this->render_from_template('report_customsql/query_actions', $context);
    }
}
