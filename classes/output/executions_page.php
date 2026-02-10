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
use moodle_url;
use renderable;
use templatable;
use renderer_base;

/**
 * Executions page renderable class.
 *
 * @package    report_customsql
 * @copyright  2025 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class executions_page implements renderable, templatable {
    /** @var int Query ID filter. */
    private $queryid;

    /** @var string Status filter. */
    private $status;

    /** @var bool Only mine filter. */
    private $onlymine;

    /** @var bool Can view all executions. */
    private $canviewall;

    /** @var array Statistics data. */
    private $stats;

    /** @var array Available queries for filter. */
    private $queries;

    /** @var string Table HTML. */
    private $tablehtml;

    /** @var moodle_url Page URL. */
    private $pageurl;

    /** @var moodle_url Back URL. */
    private $backurl;

    /** @var string Back link text. */
    private $backlinktext;

    /**
     * Constructor.
     *
     * @param int $queryid Query ID filter
     * @param string $status Status filter
     * @param bool $onlymine Only mine filter
     * @param bool $canviewall Can view all executions
     * @param array $stats Statistics data
     * @param array $queries Available queries
     * @param string $tablehtml Table HTML
     * @param moodle_url $pageurl Page URL
     * @param moodle_url $backurl Back URL
     * @param string $backlinktext Back link text
     */
    public function __construct(
        int $queryid,
        string $status,
        bool $onlymine,
        bool $canviewall,
        array $stats,
        array $queries,
        string $tablehtml,
        moodle_url $pageurl,
        moodle_url $backurl,
        string $backlinktext
    ) {
        $this->queryid = $queryid;
        $this->status = $status;
        $this->onlymine = $onlymine;
        $this->canviewall = $canviewall;
        $this->stats = $stats;
        $this->queries = $queries;
        $this->tablehtml = $tablehtml;
        $this->pageurl = $pageurl;
        $this->backurl = $backurl;
        $this->backlinktext = $backlinktext;
    }

    /**
     * Export data for template rendering.
     *
     * @param renderer_base $output Renderer base.
     * @return array Template data.
     */
    public function export_for_template(renderer_base $output): array {
        $data = [
            'hasstats' => !empty($this->stats),
            'stats' => $this->prepare_stats_data(),
            'filters' => $this->prepare_filters_data(),
            'hastable' => !empty($this->tablehtml) && strpos($this->tablehtml, '<table') !== false,
            'tablehtml' => $this->tablehtml,
            'backurl' => $this->backurl->out(false),
            'backlinktext' => $this->backlinktext,
        ];

        return $data;
    }

    /**
     * Prepare statistics data for template.
     *
     * @return array Statistics data
     */
    private function prepare_stats_data(): array {
        if (empty($this->stats)) {
            return [];
        }

        $statsitems = [];

        if (isset($this->stats['total'])) {
            $statsitems[] = [
                'label' => get_string('totalexecutions', 'report_customsql'),
                'value' => $this->stats['total'],
            ];
        }

        if (isset($this->stats['queued'])) {
            $statsitems[] = [
                'label' => get_string('queuedexecutions', 'report_customsql'),
                'value' => $this->stats['queued'],
            ];
        }

        if (isset($this->stats['running'])) {
            $statsitems[] = [
                'label' => get_string('runningexecutions', 'report_customsql'),
                'value' => $this->stats['running'],
            ];
        }

        if (isset($this->stats['completed'])) {
            $statsitems[] = [
                'label' => get_string('completedexecutions', 'report_customsql'),
                'value' => $this->stats['completed'],
            ];
        }

        if (isset($this->stats['failed'])) {
            $statsitems[] = [
                'label' => get_string('failedexecutions', 'report_customsql'),
                'value' => $this->stats['failed'],
            ];
        }

        if (isset($this->stats['success_rate'])) {
            $statsitems[] = [
                'label' => get_string('successrate', 'report_customsql'),
                'value' => round($this->stats['success_rate'], 2) . '%',
            ];
        }

        if (isset($this->stats['avg_execution_time'])) {
            $statsitems[] = [
                'label' => get_string('avgexecutiontime', 'report_customsql'),
                'value' => format_time($this->stats['avg_execution_time']),
            ];
        }

        return [
            'title' => get_string('queuestats', 'report_customsql'),
            'items' => $statsitems,
        ];
    }

    /**
     * Prepare filters data for template.
     *
     * @return array Filters data
     */
    private function prepare_filters_data(): array {
        // Prepare queries for select.
        $queryoptions = [];
        $queryoptions[] = [
            'value' => 0,
            'label' => get_string('allqueries', 'report_customsql'),
            'selected' => $this->queryid === 0,
        ];

        foreach ($this->queries as $id => $displayname) {
            $queryoptions[] = [
                'value' => $id,
                'label' => $displayname,
                'selected' => $this->queryid == $id,
            ];
        }

        // Prepare status options.
        $statusoptions = [
            [
                'value' => 'all',
                'label' => get_string('allexecutions', 'report_customsql'),
                'selected' => $this->status === 'all',
            ],
            [
                'value' => 'pending',
                'label' => get_string('status_pending', 'report_customsql'),
                'selected' => $this->status === 'pending',
            ],
            [
                'value' => 'queued',
                'label' => get_string('queued', 'report_customsql'),
                'selected' => $this->status === 'queued',
            ],
            [
                'value' => 'running',
                'label' => get_string('running', 'report_customsql'),
                'selected' => $this->status === 'running',
            ],
            [
                'value' => 'completed',
                'label' => get_string('completed', 'report_customsql'),
                'selected' => $this->status === 'completed',
            ],
            [
                'value' => 'failed',
                'label' => get_string('failed', 'report_customsql'),
                'selected' => $this->status === 'failed',
            ],
        ];

        return [
            'title' => get_string('filterexecutions', 'report_customsql'),
            'formaction' => $this->pageurl->out_omit_querystring(),
            'querylabel' => get_string('query', 'report_customsql'),
            'queryoptions' => $queryoptions,
            'statuslabel' => get_string('status', 'report_customsql'),
            'statusoptions' => $statusoptions,
            'showonlymine' => $this->canviewall,
            'onlyminelabel' => get_string('onlymyexecutions', 'report_customsql'),
            'onlyminechecked' => $this->onlymine,
            'submitlabel' => get_string('applyfilters', 'report_customsql'),
        ];
    }
}
