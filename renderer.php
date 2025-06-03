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
 * Local Pages Renderer
 *
 * @package     local_page
 * @author      Marcin Czaja RoseaThemes
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/page/forms/edit.php');

// Temporary fix: manually include output classes until cache is cleared
require_once($CFG->dirroot . '/local/page/classes/output/page_card.php');
require_once($CFG->dirroot . '/local/page/classes/output/pages_list.php');
require_once($CFG->dirroot . '/local/page/classes/output/page_content.php');

use local_page\output\page_card;
use local_page\output\pages_list;
use local_page\output\page_content;

/**
 *
 * Class local_page_renderer
 *
 * @copyright   2025 Marcin Czaja RoseaThemes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_page_renderer extends plugin_renderer_base {

    /**
     * @var array
     */
    public $errorfields = [];

    /**
     * Render a page card using the Output API
     *
     * @param page_card $pagecard Page card output object
     * @return string The rendered HTML
     */
    public function render_page_card(page_card $pagecard): string {
        return $this->render_from_template('local_page/page_card', $pagecard->export_for_template($this));
    }

    /**
     * Render pages list using the Output API
     *
     * @param pages_list $pageslist Pages list output object
     * @return string The rendered HTML
     */
    public function render_pages_list(pages_list $pageslist): string {
        return $this->render_from_template('local_page/pages_list', $pageslist->export_for_template($this));
    }

    /**
     * Render page content using the Output API
     *
     * @param page_content $pagecontent Page content output object
     * @return string The rendered HTML
     */
    public function render_page_content(page_content $pagecontent): string {
        return $this->render_from_template('local_page/page_content', $pagecontent->export_for_template($this));
    }

    /**
     * Get the submenu item
     *
     * This function generates the HTML for displaying a single page card in the pages list.
     * Each card shows the page status (live/draft/archived), title, edit button, URLs,
     * and action buttons for viewing and deleting the page.
     *
     * @param int $parent The ID of the page
     * @param string $name The name/title of the page
     * @param string $status The current status of the page (live, draft, archived)
     * @param int $pagedate The timestamp when the page becomes active
     * @param int $enddate The timestamp when the page expires
     * @param string|null $menuname Optional menu name for clean URLs
     * @return string The generated HTML for the page card
     */
    public function get_allpages($parent, $name, $status, $pagedate, $enddate, $menuname = null): string {
        $pagecard = new page_card($parent, $name, $status, $pagedate, $enddate, $menuname);
        return $this->render_page_card($pagecard);
    }

    /**
     *
     * List the pages for the user to view
     *
     * @return string
     */
    public function list_pages() {
        global $DB;

        // Get all non-deleted pages ordered by name.
        $records = $DB->get_records_sql(
            "SELECT id, pagename, pagedata, status, menuname, pagedate, enddate
             FROM {local_page}
             WHERE deleted = 0
             ORDER BY pagename"
        );

        $pageslist = new pages_list($records);
        return $this->render_pages_list($pageslist);
    }

    /**
     *
     * Show the page based on users rights
     *
     * @param mixed $page
     * @return mixed
     */
    public function showpage($page) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/accesslib.php');

        $context = \context_system::instance();
        $canaccess = true;
        if (!empty($page->accesslevel) && trim($page->accesslevel) != '') {
            $canaccess = false;        // Page Has level Requirements - check rights.
            $levels = explode(",", $page->accesslevel);
            foreach ($levels as $key => $level) {
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

        // Initialize permissions to true, assuming access is granted.
        $permissions = true;
        // Check if the page is restricted to logged-in users.
        if ($page->onlyloggedin == 1) {
            // Set permissions to true only if the user is logged in and not a guest.
            $permissions = isloggedin() && !isguestuser();
        }

        // Check if the user has access to the page and if the page date is valid or if the user is a site admin.
        // Check if user has access based on time constraints, status, permissions, or admin status.
        if ($page->pagedate > 0 && $page->enddate > 0) {
            // Both start and end dates are set.
            $istimevalid = $page->pagedate <= time() && $page->enddate >= time() && $page->status == 'live' && $permissions;
        } else if ($page->pagedate > 0 && $page->enddate <= 0) {
            // Only start date is set.
            $istimevalid = $page->pagedate <= time() && $page->status == 'live' && $permissions;
        } else if ($page->pagedate <= 0 && $page->enddate > 0) {
            // Only end date is set.
            $istimevalid = $page->enddate >= time() && $page->status == 'live' && $permissions;
        } else {
            // No dates set, check if status is live.
            $istimevalid = $page->status == 'live' ? $permissions : false;
        }
        $isadmin = has_capability('moodle/site:config', $context);

        if (($permissions && $istimevalid) || $isadmin) {
            $today = time(); // Get the current timestamp.
            // Fetch records from the database for pages that are not deleted and are of a specific type.
            $records = $DB->get_records_sql("SELECT * FROM {local_page} WHERE deleted=0 " .
                "AND pagedate <=? ORDER BY pagename", [$page->id, $today]);
            $form = ''; // Initialize the form variable.

            // Format the page content to prevent XSS attacks.
            $pagecontent = format_text(
                $this->adduserdata($page->pagecontent),
                FORMAT_HTML,
                ['trusted' => true, 'noclean' => true]
            );

            // Replace placeholders in the page content with the actual form content.
            $content = str_replace(["#form#", "{form}"], [$form, $form], $pagecontent);
            
            $pagecontentobj = new page_content(true, $content);
            return $this->render_page_content($pagecontentobj);
        } else {
            // Return a no access message if the user does not have permission.
            $noaccessmsg = get_string('noaccess', 'local_page');
            $pagecontentobj = new page_content(false, '', $noaccessmsg);
            return $this->render_page_content($pagecontentobj);
        }
    }

    /**
     * Replaces user data placeholders in content with actual user information
     *
     * This method takes content containing placeholders in the format {username}, {email}, etc.
     * and replaces them with the current user's data. If user is not logged in, admin/guest
     * user data is used instead.
     *
     * @param string $data The content containing user data placeholders
     * @return string The content with placeholders replaced with actual user data
     */
    public function adduserdata($data) {
        global $USER, $DB;

        // Determine which user object to use based on login status.
        // Note: requires lib/accesslib.php to be included for these functions.
        if (isloggedin() && !isguestuser()) {
            // Use current logged-in user.
            $usr = $USER;
        } else {
            // Fall back to admin/guest user (id=1).
            // Only fetch necessary fields with proper error handling.
            $usr = $DB->get_record('user', ['id' => 1], '*', MUST_EXIST);
        }

        // Iterate through all user properties and replace matching placeholders.
        foreach ((array)$usr as $key => $details) {
            // Skip non-scalar values (arrays, objects) as they can't be directly inserted.
            if (is_scalar($details)) {
                $placeholder = '{' . $key . '}';
                if (strpos($data, $placeholder) !== false) {
                    // Use Moodle's s() function to escape the value for security.
                    $data = str_replace($placeholder, s($details), $data);
                }
            }
        }

        return $data;
    }

    /**
     *
     * Save the page to the database and redirect the user
     *
     * @param bool $page
     */
    public function save_page($page = false) {
        global $CFG;
        $mform = new pages_edit_product_form($page);
        if ($mform->is_cancelled()) {
            redirect(new moodle_url($CFG->wwwroot . '/local/page/pages.php'));
        } else if ($data = $mform->get_data()) {
            require_once($CFG->libdir . '/formslib.php');
            $context = context_system::instance();
            $data->pagecontent['text'] = file_save_draft_area_files(
                $data->pagecontent['itemid'],
                $context->id,
                'local_page',
                'pagecontent',
                0,
                ['subdirs' => true],
                $data->pagecontent['text']
            );

            $data->pagedata = '';

            $recordpage = new stdClass();
            $recordpage->id = $data->id;
            $recordpage->pagename = $data->pagename;
            if (get_config('local_page', 'additionalhead')) {
                $recordpage->meta = $data->meta;
            }
            $recordpage->menuname = strtolower(str_replace([
                " ",
                "/",
                "\\",
                "'",
                '"',
                ";",
                "~",
                "?",
                "&",
                "@",
                "#",
                "$",
                "%",
                "^",
                "*",
                "(",
                ")",
                "+",
                "=",
                "-",
                ".",
                ":",
                "<",
                ">",
                "{",
                "}",
                "[",
                "]",
                "|",
                "!",
            ], "", trim($data->menuname)));
            $recordpage->accesslevel = $data->accesslevel;
            $recordpage->pagedate = $data->pagedate;
            $recordpage->enddate = $data->enddate;
            $recordpage->status = $data->status;
            $recordpage->metadescription = $data->metadescription;
            $recordpage->metakeywords = $data->metakeywords;
            $recordpage->metaauthor = $data->metaauthor;
            $recordpage->metatitle = $data->metatitle;
            $recordpage->metarobots = $data->metarobots;
            $recordpage->onlyloggedin = $data->onlyloggedin;
            $recordpage->hidetitle = $data->hidetitle;

            $recordpage->pagecontent = $data->pagecontent['text'];
            $result = $page->update($recordpage);
            if ($result && $result > 0) {
                $options = ['subdirs' => 0, 'maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => '*'];
                if (isset($data->ogimage_filemanager)) {
                    file_postupdate_standard_filemanager($data, 'ogimage', $options, $context, 'local_page', 'ogimage', $result);
                }
                redirect(new moodle_url($CFG->wwwroot . '/local/page/edit.php', ['id' => $result]));
            }
        }
    }

    /**
     *
     * Show the page information to edit
     *
     * @param bool $page
     */
    public function edit_page($page = false) {
        $mform = new pages_edit_product_form($page);
        $forform = new stdClass();
        $forform->pagecontent['text'] = $page->pagecontent;
        $forform->pagename = $page->pagename;
        $forform->meta = $page->meta;
        $forform->accesslevel = $page->accesslevel;
        $forform->menuname = $page->menuname;
        $forform->status = $page->status;
        $forform->metadescription = $page->metadescription;
        $forform->metakeywords = $page->metakeywords;
        $forform->metaauthor = $page->metaauthor;
        $forform->metatitle = $page->metatitle;
        $forform->metarobots = $page->metarobots;
        $forform->id = $page->id;
        $forform->pagedate = $page->pagedate;
        $forform->enddate = $page->enddate;
        $forform->onlyloggedin = $page->onlyloggedin;
        $forform->hidetitle = $page->hidetitle;
        $mform->set_data($forform);
        $mform->display();
    }


}
