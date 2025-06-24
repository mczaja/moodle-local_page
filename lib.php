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
 * page content, Open Graph images, and H5P files. It checks the requested file area
 * and retrieves the corresponding file from the file storage.
 * 
 * H5P files (.h5p) can be stored in the 'pagecontent' file area and will be
 * served through Moodle's core H5P embed system at /h5p/embed.php
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
    global $DB;

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
        
        // Special handling for H5P files - integrate with Moodle's H5P system
        // This allows H5P content uploaded to pages to be properly displayed 
        // through Moodle's core H5P embed functionality
        if ($file && !$file->is_directory() && pathinfo($filename, PATHINFO_EXTENSION) === 'h5p') {
            // Close the session to prevent locking issues.
            \core\session\manager::write_close();
            
            // Get the file hash for H5P embed URL
            $filehash = $file->get_contenthash();
            
            // Redirect to Moodle's H5P embed system
            $embedurl = new moodle_url('/h5p/embed.php', [
                'url' => moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_page',
                    'pagecontent',
                    0,
                    $filepath,
                    $filename
                )->out(false)
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
