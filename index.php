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
 * Main view page for displaying custom pages in the local_page plugin.
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include config.php.
// phpcs:disable moodle.Files.RequireLogin.Missing
// Let codechecker ignore the next line because otherwise it would complain about a missing login check
// after requiring config.php which is really not needed.
require(__DIR__ . '/../../config.php');

// Globals.
global $CFG, $PAGE, $USER, $DB, $SITE;
require_once($CFG->dirroot . '/local/page/lib.php'); // Include the library file for local_page plugin functions.

// Retrieve the ID or menuname of the page to be displayed from the URL parameters.
$pageid = optional_param('id', 0, PARAM_INT);
$menuname = optional_param('menuname', '', PARAM_TEXT);

// Load the custom page object using the page ID or menuname.
if (!empty($menuname)) {
    // Load by menuname
    $custompage = \local_page\custompage::load_by_menuname($menuname);
} else {
    // Load by ID
    $custompage = \local_page\custompage::load($pageid);
}

// Set up the page context and URL for the current page.
$context = context_system::instance(); // Get the system context.
$PAGE->set_context($context); // Set the context for the page.

// Set the URL based on whether we're using menuname or ID
if (!empty($menuname)) {
    $PAGE->set_url(new moodle_url('/' . $menuname)); // Define the URL for the page using menuname.
} else {
    $PAGE->set_url(new moodle_url('/local/page/index.php', ['id' => $pageid])); // Define the URL for the page using ID.
}

// Check if the custom page has specific access level requirements.
if (!empty($custompage->accesslevel)) {
    require_login(); // Ensure the user is logged in if access level is required.

    // Note: Additional capability checks can be added here based on $custompage->accesslevel.
}

// Initialize an empty string to hold the meta tags for SEO.
$headseo = '';

// Define an array of meta tags with their corresponding content from the custom page.
$metatags = [
    'description' => $custompage->metadescription,
    'keywords' => $custompage->metakeywords,
    'author' => $custompage->metaauthor,
    'og:title' => $custompage->metatitle,
    'robots' => $custompage->metarobots,
];

// Loop through each meta tag and its content.
foreach ($metatags as $name => $content) {
    // Check if the content is not empty.
    if (!empty($content)) {
        // Append the meta tag to the $headseo string, using format_string to properly escape the content for HTML.
        $headseo .= '<meta name="' . $name . '" content="' . format_string($content) . '" />' . "\n";
    }
}

// Add Open Graph image if available for this page.
$fs = get_file_storage();
$context = context_system::instance();
$files = $fs->get_area_files($context->id, 'local_page', 'ogimage', $custompage->id, 'sortorder', false);

if ($files) {
    $file = reset($files); // Get the first file.
    if (!$file->is_directory()) {
        $imageurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false // Do not force download.
        );
        $headseo .= '<meta property="og:image" content="' . $imageurl->out(false) . '" />' . "\n";
    }
}

// Build the canonical URL for the page.
if (!empty($menuname) && !empty($custompage->menuname)) {
    // Use the root-level URL format
    $canonicalurl = new moodle_url('/' . $custompage->menuname);
} else {
    // Use standard URL format with ID parameter.
    $canonicalurl = new moodle_url('/local/page/index.php', ['id' => $custompage->id]);
}

// Add standard Open Graph metadata for social media sharing.
$headseo .= '<meta property="og:site_name" content="' . format_string($SITE->fullname) . '" />' . "\n";
$headseo .= '<meta property="og:type" content="website" />' . "\n";
$headseo .= '<meta property="og:title" content="' . format_string($PAGE->title) . '" />' . "\n";
$headseo .= '<meta property="og:url" content="' . $canonicalurl->out(false) . '" />' . "\n";

// Additional HTML head content.
$additionalhead = $custompage->meta;

// Set the additional HTML head content in the global configuration.
$CFG->additionalhtmlhead = $headseo . $additionalhead;

// Set the page layout to use.
$PAGE->set_pagelayout('base'); // Set the page layout.

// Set the page title and heading using the custom page's name.
$PAGE->set_title($custompage->pagename);

// Get page status for admin and user.
$statusbadge = $custompage->status;

if ($custompage->hidetitle == 'no') {
    $PAGE->set_heading($custompage->pagename);
}

// Set a custom body ID here.
$PAGE->set_pagetype('local-page-id-' . $pageid); // Optional.

// Add a link to the custom pages list in the navbar if the user has the necessary capability.
if (has_capability('local/page:addpages', $context) || is_siteadmin()) {
    $PAGE->add_body_class('local-page-status-' . $statusbadge);
}

// Add a CSS class to the body tag to uniquely identify this page.
if ($pageid) {
    if ($pagedata = $DB->get_record('local_page', ['id' => $pageid])) {
        // Construct the CSS class name using the format: {pagetype}-local-pages-{pagename}-{pageid}.
        $classname = "local-page-id-{$pageid}";

        // Add the constructed class name to the body tag.
        $PAGE->add_body_class($classname);
    }
}

// Obtain the renderer for the local_page plugin to output the page content.
$renderer = $PAGE->get_renderer('local_page');

// Output the page header, content, and footer.
echo $OUTPUT->header(); // Display the page header.
echo $OUTPUT->blocks('side-pre');
echo $renderer->showpage($custompage); // Render and display the custom page content.

// Check if the user has the capability to add pages or is a site admin.
if (has_capability('local/page:addpages', $context) || is_siteadmin()) {
    // Create a link to the edit page with an icon and the text 'edit'.
    $footerbtn = html_writer::div(
        html_writer::link(
            new moodle_url('/local/page/edit.php', ['id' => $pageid]),
            '<i class="fa fa-pencil me-2"></i>' . get_string('edit', 'moodle'),
            ['class' => 'btn btn-primary']
        ),
        'local-page-admin-controls mt-3'
    );
    // Output the footer button.
    echo $footerbtn;
}

echo $OUTPUT->footer(); // Display the page footer.
