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

/**
 * Output class for page card template
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_page\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;
use moodle_url;

/**
 * Class representing data for page card template
 *
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_card implements renderable, templatable {

    /** @var int Page ID */
    protected $id;

    /** @var string Page name */
    protected $name;

    /** @var string Page status */
    protected $status;

    /** @var int Page start date */
    protected $pagedate;

    /** @var int Page end date */
    protected $enddate;

    /** @var string Menu name */
    protected $menuname;

    /**
     * Constructor
     *
     * @param int $id Page ID
     * @param string $name Page name
     * @param string $status Page status
     * @param int $pagedate Page start date
     * @param int $enddate Page end date
     * @param string|null $menuname Menu name
     */
    public function __construct($id, $name, $status, $pagedate, $enddate, $menuname = null) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->pagedate = $pagedate;
        $this->enddate = $enddate;
        $this->menuname = $menuname;
    }

    /**
     * Export data for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER;

        $data = new stdClass();
        $data->id = $this->id;
        $data->name = shorten_text($this->name, 100);
        $data->status = $this->status;

        // Generate card body class based on status
        $data->cardbodyclass = 'custompages-card-body rounded mb-3';
        if ($this->status === 'draft') {
            $data->cardbodyclass .= ' custompages-card-body--draft';
        } else if ($this->status === 'archived') {
            $data->cardbodyclass .= ' custompages-card-body--archived';
        }

        // Generate status badge
        $badgeclasses = [
            'live' => 'badge badge-sq badge-success',
            'draft' => 'badge badge-sq badge-warning',
            'archived' => 'badge badge-sq badge-danger',
        ];
        
        if (isset($badgeclasses[$this->status])) {
            $statusstring = get_string('status_' . $this->status, 'local_page');
            $data->statusbadge = \html_writer::tag('span', $statusstring, ['class' => $badgeclasses[$this->status]]);
        } else {
            $data->statusbadge = '';
        }

        // Check if page has restrictions
        $data->restricted = false;
        if ($this->pagedate > 0 && $this->status === 'live' && $this->pagedate > time()) {
            $data->restricted = true;
        } else if ($this->pagedate > 0 && $this->status === 'draft' && $this->pagedate <= time()) {
            $data->restricted = true;
        } else if ($this->enddate > 0 && $this->enddate < time()) {
            $data->restricted = true;
        }

        // Generate URLs
        $data->editurl = new moodle_url($CFG->wwwroot . '/local/page/edit.php', ['id' => $this->id]);
        $data->pageurl = $CFG->wwwroot . '/local/page/?id=' . $this->id;
        $data->viewurl = new moodle_url($CFG->wwwroot . '/local/page/', ['id' => $this->id]);
        $data->deleteurl = new moodle_url(
            $CFG->wwwroot . '/local/page/pages.php',
            ['pagedel' => $this->id, 'sesskey' => $USER->sesskey]
        );

        // Add friendly URL if menuname exists
        if ($this->menuname) {
            $data->menuname = $this->menuname;
            $data->friendlyurl = $CFG->wwwroot . '/local/page/' . $this->menuname;
        }

        return $data;
    }
} 