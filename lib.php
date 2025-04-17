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

defined('MOODLE_INTERNAL') || die();

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
/**
 * Builds the navigation menu for custom pages.
 *
 * Retrieves all active custom pages that should appear in the menu
 * and adds them to the navigation structure.
 *
 * @param navigation_node $nav The navigation node to add menu items to
 * @param global_navigation $gnav The global navigation object
 * @throws coding_exception If there's an issue with string formatting
 * @throws dml_exception If there's a database error
 * @throws moodle_exception For other Moodle-related exceptions
 */
function local_page_build_menu(navigation_node $nav, global_navigation $gnav) {
    global $DB;

    // Get current timestamp to filter pages by date.
    $now = time();

    // Build SQL conditions for active menu pages.
    $conditions = [
        'deleted = 0',
        'onmenu = 1',
        'pagedate <= :now',
    ];

    $sql = "SELECT *
            FROM {local_page}
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY pagename";

    // Retrieve all published, non-deleted pages that should appear in the menu.
    $records = $DB->get_records_sql($sql, ['now' => $now]);

    // Process the retrieved records to build the menu.
    local_page_process_records($records, $nav, $gnav);
}

/**
 * Processes page records to build navigation menu items.
 *
 * For each page record, checks access permissions and creates
 * appropriate navigation links based on the page configuration.
 *
 * @param array $records Database records of pages to process
 * @param navigation_node $nav The navigation node to add items to
 * @param global_navigation $gnav The global navigation object
 * @param bool|object $parent Parent object or false if no parent
 * @throws coding_exception If there's an issue with string formatting
 * @throws dml_exception If there's a database error
 * @throws moodle_exception For other Moodle-related exceptions
 */
function local_page_process_records($records, $nav, global_navigation $gnav, $parent = false) {
    global $CFG;
    if ($records) {
        foreach ($records as $page) {
            // Default access is granted.
            $canaccess = true;
            // Check access level restrictions if defined.
            if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                $canaccess = false;
                $levels = explode(",", $page->accesslevel);
                $context = context_system::instance();
                // Process each capability in the access level list.
                foreach ($levels as $level) {
                    if ($canaccess != true) {
                        if (stripos($level, "!") !== false) {
                            // Negated capability check (user must NOT have this capability).
                            $level = str_replace("!", "", $level);
                            $canaccess = has_capability(trim($level), $context) ? false : true;
                        } else {
                            // Standard capability check (user must have this capability).
                            $canaccess = has_capability(trim($level), $context) ? true : false;
                        }
                    }
                }
            }
            // If user has access, create the navigation URL.
            if ($canaccess) {
                // Default URL with page ID parameter.
                $urllocation = new moodle_url('/local/page/', ['id' => $page->id]);
                // Use clean URL if enabled and page has a menu name.
                if (get_config('local_page', 'cleanurl_enabled') && trim($page->menuname) != '') {
                    $urllocation = new moodle_url('/local/page/' . $page->menuname);
                }
            }
        }
    }
}

/**
 * Adds custom metadata to the HTML head for local pages.
 *
 * This function is called by the before_standard_head_html_generation hook
 * and adds Open Graph metadata and other custom meta tags to local pages.
 *
 * @param \core\hook\output\before_standard_head_html_generation $hook The hook object
 * @return void
 */
function local_page_output_before_standard_head_html_generation(
    \core\hook\output\before_standard_head_html_generation $hook
): void {
    global $CFG, $DB, $PAGE, $SITE;

    // Only apply to local pages - check the page type.
    if ($PAGE->pagetype !== 'local-pages-index') {
        return;
    }

    // Get the page ID from URL parameters and load the custom page object.
    $pageid = optional_param('id', 0, PARAM_INT);
    $custompage = \local_page\custompage::load($pageid);

    // Initialize output with custom meta tags if enabled in settings.
    $output = get_config('local_page', 'additionalhead') ? $custompage->meta : '';

    // Add Open Graph image if available for this page.
    $query = "SELECT * FROM {files}
              WHERE component = 'local_page'
              AND filearea = 'ogimage'
              AND itemid = ?
              AND filesize > 0";

    // If an OG image exists for this page, add it to the metadata.
    if ($filerecord = $DB->get_record_sql($query, [$custompage->id])) {
        $src = $CFG->wwwroot . '/pluginfile.php/1/local_page/ogimage/' .
            $custompage->id . '/' . $filerecord->filename;
        $output .= "\n" . '    <meta property="og:image" content="' . $src . '" />';
    }

    // Build the canonical URL for the page.
    $url = new moodle_url($PAGE->url);
    $url->remove_all_params();

    // Use clean URL format if enabled and no specific page ID is requested.
    if (get_config('local_page', 'cleanurl_enabled') && $pageid === 0) {
        $url = str_replace('index.php', '', $url->out());
        $url .= $custompage->menuname;
    } else {
        // Otherwise use standard URL format with ID parameter.
        $url = $url->out() . '?id=' . $custompage->id;
    }

    // Add standard Open Graph metadata for social media sharing.
    $output .= "\n" . '    <meta property="og:site_name" content="' . format_string($SITE->fullname) . '" />';
    $output .= "\n" . '    <meta property="og:type" content="website" />';
    $output .= "\n" . '    <meta property="og:title" content="' . format_string($PAGE->title) . '" />';
    $output .= "\n" . '    <meta property="og:url" content="' . $url . '" />';

    // Add the generated HTML to the hook.
    $hook->add_html($output);
}
