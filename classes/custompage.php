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
 * Custom pages management for Moodle.
 *
 * Handles creation, updating, and loading of custom pages within Moodle.
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_page;

/**
 * Class custompage.
 *
 * Provides functionality for managing custom pages: create, update, load.
 */
class custompage {

    /**
     * @var \stdClass The data object containing page properties.
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param \stdClass $data The page data object.
     */
    public function __construct(\stdClass $data) {
        $this->_data = $data;
    }

    /**
     * Insert a new page into the database.
     *
     * @param \stdClass $data The page data to insert.
     * @return int The ID of the newly created page.
     */
    public function createpage(\stdClass $data): int {
        global $DB;
        return $DB->insert_record('local_page', $data);
    }

    /**
     * Update an existing page in the database.
     *
     * @param \stdClass $data The page data to update.
     * @return bool True if the update was successful.
     */
    public function updatepage(\stdClass $data): bool {
        global $DB;
        return $DB->update_record('local_page', $data);
    }

    /**
     * Update an existing page or create a new one if it doesn't exist.
     *
     * @param \stdClass $data The page data to update or create.
     * @return int|bool The page ID on success, or false on failure.
     */
    public function update(\stdClass $data) {
        if (!empty($data->id) && $data->id > 0) {
            $result = $this->updatepage($data);
            return $result ? $data->id : false;
        } else {
            return $this->createpage($data);
        }
    }

    /**
     * Magic getter to retrieve properties from the page data object.
     *
     * @param string $item The property name to retrieve.
     * @return mixed The property value or null if not found.
     */
    public function __get($item) {
        return $this->_data->$item ?? null;
    }

    /**
     * Loads a page from the database by ID or by URL (menuname).
     *
     * @param int $id The page ID to load, or 0 to load by URL.
     * @param bool $editor Whether the page is being loaded for editing.
     * @return custompage The loaded page object.
     */
    public static function load($id, $editor = false): custompage {
        global $DB, $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once(dirname(__FILE__) . '/../lib.php');

        $data = null;

        if (intval($id) > 0) {
            $data = $DB->get_record('local_page', ['id' => intval($id)]);
        } else {
            $requesturi = $_SERVER['REQUEST_URI'] ?? '';
            $main = explode('?', trim($requesturi));
            $parts = array_filter(explode('/', trim($main[0])));
            $lastpart = end($parts);

            if ($lastpart !== false && $lastpart !== '') {
                $data = $DB->get_record_sql(
                    "SELECT * FROM {local_page}
                     WHERE menuname = ? AND deleted = 0
                     ORDER BY id DESC LIMIT 1",
                    [$lastpart]
                );
            }
        }

        if (!$data || empty($data->pagecontent)) {
            if (!$data) {
                $data = new \stdClass();
            }
            $data->pagecontent = $editor ? '' : \get_string('noaccess', 'local_page');
        }

        if (!$editor && !empty($data->pagecontent)) {
            $context = \context_system::instance();
            $data->pagecontent = \file_rewrite_pluginfile_urls(
                $data->pagecontent,
                'pluginfile.php',
                $context->id,
                'local_page',
                'pagecontent',
                null
            );
        }

        return new custompage($data);
    }
}
