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
 * This file contains the custompage class which handles the creation,
 * updating, and loading of custom pages within the Moodle platform.
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
 * This class provides functionality for managing custom pages including
 * creating, updating, and loading page data from the database.
 *
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custompage {

    /**
     * @var \stdClass The data object containing page properties.
     */
    private $_data;

    /**
     * Custompage constructor.
     *
     * @param \stdClass $data The page data object.
     */
    public function __construct($data) {
        // Initialize the data object with the provided page data.
        $this->_data = $data;
    }

    /**
     * Creates a new page in the database.
     *
     * @param \stdClass $data The page data to insert.
     * @return int The ID of the newly created page.
     */
    public function createpage($data) {
        global $DB;
        // Insert the page data into the database and return the new page ID.
        return $DB->insert_record('local_page', $data);
    }

    /**
     * Updates an existing page in the database.
     *
     * @param \stdClass $data The page data to update.
     * @return bool True if the update was successful.
     */
    public function updatepage($data) {
        global $DB;
        // Update the page data in the database and return the success status.
        return $DB->update_record('local_page', $data);
    }

    /**
     * Updates an existing page or creates a new one if it doesn't exist.
     *
     * @param \stdClass $data The page data to update or create.
     * @return int|bool The page ID on success, or false on failure.
     */
    public function update($data) {
        // Check if the page ID is set and greater than zero.
        if (isset($data->id) && $data->id > 0) {
            // Update the existing page and set the result to the page ID if successful.
            $result = $this->updatepage($data);
            if ($result) {
                $result = $data->id;
            }
        } else {
            // Create a new page and set the result to the new page ID.
            $result = $this->createpage($data);
        }
        // Return the result of the update or creation.
        return $result;
    }

    /**
     * Magic getter to retrieve properties from the page data object.
     *
     * @param string $item The property name to retrieve.
     * @return mixed The property value or null if not found.
     */
    public function __get($item) {
        // Check if the property exists in the data object and return its value.
        if (isset($this->_data->$item)) {
            return $this->_data->$item;
        }
        // Return null if the property is not found.
        return null;
    }

    /**
     * Loads a page from the database based on its ID or URL.
     *
     * @param integer $id The page ID to load.
     * @param bool $editor Whether the page is being loaded for editing.
     * @return custompage The loaded page object.
     */
    /**
     * Loads a page from the database based on its ID or URL.
     *
     * @param integer $id The page ID to load or 0 to load by URL.
     * @param bool $editor Whether the page is being loaded for editing.
     * @return custompage The loaded page object.
     */
    public static function load($id, $editor = false) {
        global $DB, $CFG;
        // Include necessary libraries for form handling and custom functionality.
        require_once($CFG->libdir . '/formslib.php');
        require_once(dirname(__FILE__) . '/../lib.php');

        // Initialize a new data object.
        $data = new \stdClass();

        // Check if the ID is greater than zero to load the page by ID.
        if (intval($id) > 0) {
            // Load page by ID from the database using get_record for better performance.
            $data = $DB->get_record('local_page', ['id' => intval($id)]);
        } else {
            // Try to load page by URL if ID is not provided.
            $requesturi = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $main = explode('?', trim($requesturi));
            $parts = explode('/', trim($main[0]));
            $lastpart = end($parts);

            // Use more specific query to improve performance and accuracy.
            $data = $DB->get_record_sql(
                "SELECT * FROM {local_page}
                 WHERE menuname LIKE ? AND deleted = 0
                 ORDER BY id DESC LIMIT 1",
                ['%' . $DB->sql_like_escape($lastpart) . '%']
            );
        }

        // Set default content if page not found or content is empty.
        if (!$data || empty($data->pagecontent)) {
            if (!$data) {
                $data = new \stdClass();
            }
            // Use global get_string function to avoid namespace issues.
            $str = \get_string('noaccess', 'local_page');
            $data->pagecontent = $editor ? '' : $str;
        }

        // Process file references in the content.
        $context = \context_system::instance();
        if (!$editor && !empty($data->pagecontent)) {
            // Rewrite plugin file URLs in the page content using global function.
            $data->pagecontent = \file_rewrite_pluginfile_urls(
                $data->pagecontent,
                'pluginfile.php',
                $context->id,
                'local_page',
                'pagecontent',
                null
            );
        }

        // Return a new custompage object with the loaded data.
        return new custompage($data);
    }
}
