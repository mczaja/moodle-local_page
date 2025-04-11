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
 * Local pages plugin
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();
 
/**
 * This function handles the retrieval and delivery of saved files for the local page plugin.
 * It checks the requested file area and serves the appropriate file to the user, 
 * ensuring proper session management and handling of download requests.
 *
 * @param mixed $course Course object
 * @param mixed $birecordorcm Course module object
 * @param mixed $context Context object
 * @param mixed $filearea File area string
 * @param mixed $args Arguments
 * @param bool $forcedownload Force download flag
 * @param array $options Additional options
 * @return void
 */
function local_page_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Get the file storage API
    $fs = get_file_storage();

    // Extract the filename from the end of the args array
    $filename = array_pop($args);
    // Construct the filepath from remaining args or use root path
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    if ($filearea === 'pagecontent') {
        // Handle files in the pagecontent area
        if (!$file = $fs->get_file($context->id, 'local_page', 'pagecontent', 0, $filepath, $filename) || $file->is_directory()) {
            // If file doesn't exist or is a directory, return 404
            send_file_not_found();
        }
    } else if ($filearea === 'ogimage') {
        // Handle files in the ogimage area (Open Graph images for social media)
        $itemid = array_pop($args);
        // Get the file with the specific item ID
        $file = $fs->get_file($context->id, 'local_page', $filearea, $itemid, '/', $filename);
        // Todo: Maybe put in fall back image.
    }

    // Close the session before sending the file to prevent session lock issues
    \core\session\manager::write_close();
    // Send the file to the browser
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Build the menu for the page
 *
 * @param navigation_node $nav Navigation node
 * @param mixed $parent Parent ID
 * @param global_navigation $gnav Global navigation object
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_page_build_menu(navigation_node $nav, global_navigation $gnav) {
    global $DB;
    $today = date('U');
    $records = $DB->get_records_sql("SELECT * FROM {local_page} WHERE deleted=0 AND onmenu=1 " .
        " AND pagedate <=? " .
        "ORDER BY pagename", [$today]);
    local_page_process_records($records, $nav, false, $gnav);
}

/**
 * Process records for pages
 *
 * @param mixed $records Database records
 * @param mixed $nav Navigation node
 * @param global_navigation $gnav Global navigation object
 * @param bool|object $parent Parent object or false
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_page_process_records($records, $nav, global_navigation $gnav, $parent = false) {
    global $CFG;
    if ($records) {
        foreach ($records as $page) {
            $canaccess = true;
            if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                $canaccess = false;
                $levels = explode(",", $page->accesslevel);
                $context = context_system::instance();
                foreach ($levels as $level) {
                    if ($canaccess != true) {
                        if (stripos($level, "!") !== false) {
                            $level = str_replace("!", "", $level);
                            $canaccess = has_capability(trim($level), $context) ? false : true;
                        } else {
                            $canaccess = has_capability(trim($level), $context) ? true : false;
                        }
                    }
                }
            }
            if ($canaccess) {
                $urllocation = new moodle_url('/local/page/', ['id' => $page->id]);
                if (get_config('local_page', 'cleanurl_enabled') && trim($page->menuname) != '') {
                    $urllocation = new moodle_url('/local/page/' . $page->menuname);
                }
            }
        }
    }
}

/**
 * Callback for the core\hook\output\before_standard_head_html_generation hook.
 *
 * @param \core\hook\output\before_standard_head_html_generation $hook
 * @return void
 */
function local_page_output_before_standard_head_html_generation(
    \core\hook\output\before_standard_head_html_generation $hook
): void {
    global $CFG, $DB, $PAGE, $SITE;

    // Only apply to local pages.
    if ($PAGE->pagetype !== 'local-pages-index') {
        return;
    }

    // Get the page ID and load the custom page.
    $pageid = optional_param('id', 0, PARAM_INT);
    $custompage = \local_page\custompage::load($pageid);

    // Initialize output with custom meta tags if enabled.
    $output = get_config('local_page', 'additionalhead') ? $custompage->meta : '';

    // Add Open Graph image if available.
    $query = "SELECT * FROM {files}
              WHERE component = 'local_page'
              AND filearea = 'ogimage'
              AND itemid = ?
              AND filesize > 0";

    if ($filerecord = $DB->get_record_sql($query, [$custompage->id])) {
        $src = $CFG->wwwroot . '/pluginfile.php/1/local_page/ogimage/' .
            $custompage->id . '/' . $filerecord->filename;
        $output .= "\n" . '    <meta property="og:image" content="' . $src . '" />';
    }

    // Build the canonical URL for the page.
    $url = new moodle_url($PAGE->url);
    $url->remove_all_params();

    if (get_config('local_page', 'cleanurl_enabled') && $pageid === 0) {
        $url = str_replace('index.php', '', $url->out());
        $url .= $custompage->menuname;
    } else {
        $url = $url->out() . '?id=' . $custompage->id;
    }

    // Add standard Open Graph metadata.
    $output .= "\n" . '    <meta property="og:site_name" content="' . format_string($SITE->fullname) . '" />';
    $output .= "\n" . '    <meta property="og:type" content="website" />';
    $output .= "\n" . '    <meta property="og:title" content="' . format_string($PAGE->title) . '" />';
    $output .= "\n" . '    <meta property="og:url" content="' . $url . '" />';

    // Add the generated HTML to the hook.
    $hook->add_html($output);
}
