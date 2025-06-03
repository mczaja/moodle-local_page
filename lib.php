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
 * Local pages plugin - Core library functions
 *
 * This file contains the core functions used by the local_page plugin
 * for handling file serving, navigation menu building, and metadata generation.
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Retrieves and serves saved files associated with a specific page.
 *
 * This function handles file requests for different file areas, such as
 * page content and Open Graph images. It checks the requested file area
 * and retrieves the corresponding file from the file storage.
 *
 * @param mixed $course Course object, representing the course context.
 * @param mixed $birecordorcm Course module object, used for module-specific operations.
 * @param mixed $context Context object, providing context for file access.
 * @param mixed $filearea String indicating the area of the file (e.g., 'pagecontent' or 'ogimage').
 * @param mixed $args Array of arguments used to locate the file within the specified file area.
 * @param bool $forcedownload Flag indicating whether to force the file download.
 * @param array $options Additional options for file serving, such as caching settings.
 * @return void
 */
function local_page_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {
    $fs = get_file_storage(); // Get the file storage instance.

    $filename = array_pop($args); // Extract the filename from the arguments.
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/'; // Construct the file path from the remaining arguments.

    // Check if the requested file area is for page content.
    if ($filearea === 'pagecontent') {
        // Attempt to retrieve the file; if not found or if it's a directory, trigger a 404 error.
        if (!$file = $fs->get_file($context->id, 'local_page', 'pagecontent', 0, $filepath, $filename) || $file->is_directory()) {
            send_file_not_found(); // Send a 404 response if the file is not found.
        }
    }
    // Check if the requested file area is for Open Graph images.
    if ($filearea === 'ogimage') {
        $itemid = array_pop($args); // Extract the item ID for the Open Graph image.
        // Retrieve the Open Graph image file from storage.
        $file = $fs->get_file($context->id, 'local_page', $filearea, $itemid, '/', $filename);
    } else {
        send_file_not_found(); // Send a 404 response if the file area is not recognized.
    }

    \core\session\manager::write_close(); // Close the session to prevent locking issues.
    send_stored_file($file, null, 0, $forcedownload, $options); // Serve the requested file to the user.
}
