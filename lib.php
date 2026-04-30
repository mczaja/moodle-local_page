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

// phpcs:ignore moodle.Files.MoodleInternal.MoodleInternalNotNeeded -- function-only lib, no class definitions
defined('MOODLE_INTERNAL') || die();

/**
 * Shared options for the Open Graph image file manager (form definition, set_data, save).
 * SVG is excluded because ogimage URLs are anonymous-readable and image/svg+xml can execute script.
 *
 * @return array
 */
function local_page_ogimage_filemanager_options(): array {
    return [
        'subdirs' => 0,
        'maxbytes' => 204800,
        'maxfiles' => 1,
        'accepted_types' => ['jpg', 'jpeg', 'png', 'webp'],
    ];
}

/**
 * Whether the current user may view a local page under the same rules as the public renderer.
 *
 * Mirrors local_page_renderer::showpage() access checks (status, dates, onlyloggedin, accesslevel,
 * site configuration capability override).
 *
 * @param object $page Row from {local_page} (stdClass) or {@see \local_page\custompage} with the same fields
 * @return bool
 */
function local_page_user_can_view_page(object $page): bool {
    global $CFG;

    require_once($CFG->libdir . '/accesslib.php');

    $context = context_system::instance();

    if (has_capability('moodle/site:config', $context)) {
        return true;
    }

    $canaccess = true;
    if (!empty($page->accesslevel) && trim($page->accesslevel) !== '') {
        $canaccess = false;
        $levels = explode(',', $page->accesslevel);
        foreach ($levels as $level) {
            if ($canaccess != true) {
                if (stripos($level, '!') !== false) {
                    $level = str_replace('!', '', $level);
                    $canaccess = has_capability(trim($level), $context) ? false : true;
                } else {
                    $canaccess = has_capability(trim($level), $context) ? true : false;
                }
            }
        }
    }

    $permissions = true;
    if ((int) $page->onlyloggedin === 1) {
        $permissions = isloggedin() && !isguestuser();
    }

    $now = time();
    if ($page->pagedate > 0 && $page->enddate > 0) {
        $istimevalid = $page->pagedate <= $now && $page->enddate >= $now && $page->status === 'live' && $permissions;
    } else if ($page->pagedate > 0 && $page->enddate <= 0) {
        $istimevalid = $page->pagedate <= $now && $page->status === 'live' && $permissions;
    } else if ($page->pagedate <= 0 && $page->enddate > 0) {
        $istimevalid = $page->enddate >= $now && $page->status === 'live' && $permissions;
    } else {
        $istimevalid = $page->status === 'live' ? $permissions : false;
    }

    return $canaccess && $istimevalid;
}

/**
 * Returns local_page rows whose HTML references a stored file in the pagecontent filearea (itemid 0).
 *
 * @param int $contextid System context id
 * @param string $filepath File path with leading/trailing slashes (e.g. /sub/)
 * @param string $filename File name
 * @return stdClass[] List of page records (values only)
 */
function local_page_pages_referencing_pagecontent_file(int $contextid, string $filepath, string $filename): array {
    global $DB;

    $rel = trim($filepath, '/');
    $suffix = $rel === '' ? $filename : $rel . '/' . $filename;

    $needles = [
        '@@PLUGINFILE@@/' . $suffix,
        '/pluginfile.php/' . $contextid . '/local_page/pagecontent/0/' . $suffix,
        'pluginfile.php/' . $contextid . '/local_page/pagecontent/0/' . $suffix,
        $contextid . '/local_page/pagecontent/0/' . $suffix,
        '/local_page/pagecontent/0/' . $suffix,
        'local_page/pagecontent/0/' . $suffix,
    ];
    $encsuffix = rawurlencode($suffix);
    if ($encsuffix !== '') {
        $needles[] = 'local_page%2Fpagecontent%2F0%2F' . $encsuffix;
    }

    $fnesc = $DB->sql_like_escape($filename);
    $sql = "SELECT * FROM {local_page} WHERE deleted = 0 AND ("
        . $DB->sql_like('pagecontent', ':pc', false) . " OR " . $DB->sql_like('contenthtml', ':ch', false) . ")";
    $params = [
        'pc' => '%' . $fnesc . '%',
        'ch' => '%' . $fnesc . '%',
    ];

    $candidates = $DB->get_recordset_sql($sql, $params);
    $matches = [];
    foreach ($candidates as $page) {
        $hay = ($page->pagecontent ?? '') . "\0" . ($page->contenthtml ?? '');
        foreach ($needles as $needle) {
            if ($needle !== '' && strpos($hay, $needle) !== false) {
                $matches[$page->id] = $page;
                break;
            }
        }
    }
    $candidates->close();

    return array_values($matches);
}

/**
 * Whether the current user may fetch a pagecontent area file via pluginfile.php.
 *
 * @param int $contextid System context id
 * @param string $filepath Stored file path (with slashes)
 * @param string $filename File name
 * @return bool
 */
function local_page_user_can_serve_pagecontent_file(int $contextid, string $filepath, string $filename): bool {
    $pages = local_page_pages_referencing_pagecontent_file($contextid, $filepath, $filename);
    if ($pages === []) {
        return false;
    }
    foreach ($pages as $page) {
        if (local_page_user_can_view_page($page)) {
            return true;
        }
    }
    return false;
}

/**
 * Retrieves and serves saved files associated with a specific page.
 *
 * This function handles file requests for different file areas, such as
 * page content, Open Graph images. It checks the requested file area
 * and retrieves the corresponding file from the file storage.
 *
 * @param stdClass $course Course object, representing the course context.
 * @param stdClass $birecordorcm Course module object, used for module-specific operations.
 * @param stdClass $context Context object, providing context for file access.
 * @param string $filearea String indicating the area of the file (e.g., 'pagecontent' or 'ogimage').
 * @param array $args Array of arguments used to locate the file within the specified file area.
 * @param bool $forcedownload Flag indicating whether to force the file download.
 * @param array $options Additional options for file serving, such as caching settings.
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function local_page_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {

    // Check the contextlevel is as expected for local plugins.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'pagecontent' && $filearea !== 'ogimage') {
        return false;
    }

    // Get the file storage instance.
    $fs = get_file_storage();

    // Extract the filename from the arguments.
    $filename = array_pop($args);

    // Handle different file areas.
    $file = false;

    if ($filearea === 'pagecontent') {
        // Construct the file path from the remaining arguments.
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        // Attempt to retrieve the file from the pagecontent area.
        $file = $fs->get_file($context->id, 'local_page', 'pagecontent', 0, $filepath, $filename);

        if ($file && !$file->is_directory()) {
            if (!local_page_user_can_serve_pagecontent_file((int) $context->id, $filepath, $filename)) {
                return false;
            }
        }

        // Special handling for H5P files - integrate with Moodle's H5P system
        // This allows H5P content uploaded to pages to be properly displayed
        // through Moodle's core H5P embed functionality.
        if ($file && !$file->is_directory() && pathinfo($filename, PATHINFO_EXTENSION) === 'h5p') {
            // Close the session to prevent locking issues.
            \core\session\manager::write_close();

            // Redirect to Moodle's H5P embed system.
            $embedurl = new moodle_url('/h5p/embed.php', [
                'url' => moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_page',
                    'pagecontent',
                    0,
                    $filepath,
                    $filename
                )->out(false),
            ]);

            redirect($embedurl);
            return true;
        }
    } else if ($filearea === 'ogimage') {
        // For ogimage, we expect the itemid to be in the args.
        $itemid = array_shift($args); // Get the item ID for the Open Graph image.

        // Construct the file path (ogimages are typically stored in root path).
        $filepath = '/';

        // Retrieve the Open Graph image file from storage.
        $file = $fs->get_file($context->id, 'local_page', 'ogimage', $itemid, $filepath, $filename);
    }

    // Check if file was found and is not a directory.
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Close the session to prevent locking issues.
    \core\session\manager::write_close();

    // Serve the requested file to the user.
    send_stored_file($file, null, 0, $forcedownload, $options);
}
