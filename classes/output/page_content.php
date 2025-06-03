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
 * Output class for page content template
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

/**
 * Class representing data for page content template
 *
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_content implements renderable, templatable {

    /** @var bool Whether user has access */
    protected $hasaccess;

    /** @var string Page content */
    protected $content;

    /** @var string No access message */
    protected $noaccessmessage;

    /**
     * Constructor
     *
     * @param bool $hasaccess Whether user has access
     * @param string $content Page content
     * @param string $noaccessmessage No access message
     */
    public function __construct($hasaccess, $content = '', $noaccessmessage = '') {
        $this->hasaccess = $hasaccess;
        $this->content = $content;
        $this->noaccessmessage = $noaccessmessage;
    }

    /**
     * Export data for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->hasaccess = $this->hasaccess;
        $data->content = $this->content;
        $data->noaccessmessage = $this->noaccessmessage;

        return $data;
    }
} 