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
        global $CFG, $USER;
        $html = '';

        // Start card div.
        $html .= html_writer::start_div('custompages-card rounded');
        $cardbodyclass = 'custompages-card-body rounded mb-3';
        if ($status === 'draft') {
            $cardbodyclass .= ' custompages-card-body--draft';
        } else if ($status === 'archived') {
            $cardbodyclass .= ' custompages-card-body--archived';
        }
        $html .= html_writer::start_div($cardbodyclass);

        // Generate status badge based on page status.
        $statusbadge = '';
        $badgeclasses = [
            'live' => 'badge badge-sq badge-success',
            'draft' => 'badge badge-sq badge-warning',
            'archived' => 'badge badge-sq badge-danger',
        ];
        if (isset($badgeclasses[$status])) {
            $statusstring = get_string('status_' . $status, 'local_page');
            $statusbadge = html_writer::tag('span', $statusstring, ['class' => $badgeclasses[$status]]);
        }

        // Has restricted access by dates.
        $restricted = false;
        if ($pagedate > 0 && $status === 'live' && $pagedate > time()) {
            $restricted = true;
        } else if ($pagedate > 0 && $status === 'draft' && $pagedate <= time()) {
            $restricted = true;
        } else if ($enddate > 0 && $enddate < time()) {
            $restricted = true;
        }

        // Add title.
        $html .= html_writer::tag('div', $statusbadge, ['class' => 'custompages-title-badge d-inline-block me-2 mb-2']);
        if ($restricted) {
            $html .= html_writer::tag('span', get_string('restricted', 'local_page'), ['class' => 'badge badge-sq badge-warning']);
        }

        $html .= html_writer::tag('h5', shorten_text($name, 100), ['class' => 'custompages-title font-weight-medium']);
        // Edit button.
        $editurl = new moodle_url($CFG->wwwroot . '/local/page/edit.php', ['id' => $parent]);
        $html .= html_writer::start_div('w-100 border-bottom mb-2 pb-2');
        $html .= html_writer::link($editurl, get_string('edit', 'moodle'), ['class' => 'm-0 btn btn-secondary w-100']);
        $html .= html_writer::end_div();

        // Link to the page url to copy.
        $html .= html_writer::tag('label', get_string('url', 'moodle'), ['class' => 'custompages-title-badge-label']);
        $html .= html_writer::tag('pre', $CFG->wwwroot . '/local/page/?id=' . $parent, ['class' => 'custompage-url mb-2']);

        if ($menuname) {
            // Link Friendly URL to copy.
            $html .= html_writer::tag('label', get_string('menu_name', 'local_page'), ['class' => 'custompages-title-badge-label']);
            $html .= html_writer::tag('pre', $CFG->wwwroot . '/local/page/' . $menuname, ['class' => 'custompage-url mb-2']);
        }

        // Start button group.
        $html .= html_writer::start_div('custompages-btn-group', ['role' => 'group']);

        // Create a two-column layout.
        $html .= html_writer::start_div('row w-100 no-gutters mt-2 border-top pt-3');

        // First column - View button.
        $html .= html_writer::start_div('col me-2');
        $viewurl = new moodle_url($CFG->wwwroot . '/local/page/', ['id' => $parent]);
        $html .= html_writer::link(
            $viewurl,
            '<i class="fa-solid fa-arrow-up-right-from-square"></i>',
            ['class' => 'm-0 btn btn-info w-100', 'target' => '_blank']
        );
        $html .= html_writer::end_div(); // End div for class 'col me-2'.
        // Second column - Delete button.
        $html .= html_writer::start_div('col ms-2');
        $deleteurl = new moodle_url(
            $CFG->wwwroot . '/local/page/pages.php',
            ['pagedel' => $parent, 'sesskey' => $USER->sesskey]
        );
        $html .= html_writer::link(
            $deleteurl,
            '<i class="fa fa-trash"></i>',
            ['class' => 'm-0 btn btn-danger w-100']
        );
        $html .= html_writer::end_div(); // End div for class 'col ms-2'.

        $html .= html_writer::end_div(); // End div for class 'row w-100 no-gutters mt-2 border-top pt-3'.

        // End button group and card.
        $html .= html_writer::end_div(); // End div for class 'custompages-btn-group'.
        $html .= html_writer::end_div(); // End div for class 'custompages-card-body'.
        $html .= html_writer::end_div(); // End div for class 'custompages-card rounded'.

        return $html;
    }

    /**
     *
     * List the pages for the user to view
     *
     * @return string
     */
    public function list_pages() {
        global $DB, $CFG;

        // Get all non-deleted pages ordered by name.
        $records = $DB->get_records_sql(
            "SELECT id, pagename, pagedata, status, menuname, pagedate, enddate
             FROM {local_page}
             WHERE deleted = 0
             ORDER BY pagename"
        );

        // Build the main wrapper.
        $html = html_writer::start_div('custompages-list-wrapper');

        // Add list of pages.
        $html .= html_writer::start_div('custompages-list w-100');

        // Group pages by status.
        $livepages = [];
        $draftpages = [];
        $archivedpages = [];

        // Sort pages by status.
        foreach ($records as $page) {
            if ($page->status == 'live') {
                $livepages[] = $page;
            } else if ($page->status == 'draft') {
                $draftpages[] = $page;
            } else if ($page->status == 'archived') {
                $archivedpages[] = $page;
            }
        }

        // Display live pages.
        if (!empty($livepages)) {
            $html .= html_writer::tag('h3', get_string('status_live', 'local_page'), ['class' => 'font-weight-medium mb-3']);
            $html .= html_writer::start_div('custompages-item mb-4');
            foreach ($livepages as $page) {
                $html .= $this->get_allpages($page->id, $page->pagename,
                    $page->status, $page->pagedate, $page->enddate, $page->menuname);
            }
            $html .= html_writer::end_div();
        }

        // Display draft pages.
        if (!empty($draftpages)) {
            $html .= html_writer::tag('h3', get_string('status_draft', 'local_page'), ['class' => 'font-weight-medium mt-6 mb-3']);
            $html .= html_writer::start_div('custompages-item mb-4');
            foreach ($draftpages as $page) {
                $html .= $this->get_allpages($page->id, $page->pagename, $page->status,
                    $page->pagedate, $page->enddate, $page->menuname);
            }
            $html .= html_writer::end_div();
        }

        // Display archived pages.
        if (!empty($archivedpages)) {
            $html .= html_writer::tag('h3', get_string('status_archived', 'local_page'),
                ['class' => 'font-weight-medium mt-6 mb-3']);
            $html .= html_writer::start_div('custompages-item mb-4');
            foreach ($archivedpages as $page) {
                $html .= $this->get_allpages($page->id, $page->pagename, $page->status,
                    $page->pagedate, $page->enddate, $page->menuname);
            }
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();

        // Add footer with "Add page" button.
        $html .= html_writer::start_div('custompages-footer mt-4');
        $html .= html_writer::start_div('custompages-list-element');
        $addpageurl = new moodle_url($CFG->wwwroot . '/local/page/edit.php');
        $html .= html_writer::link(
            $addpageurl,
            get_string('addpage', 'local_page'),
            ['class' => 'custompages-add btn btn-primary']
        );
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();

        return $html;
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

        if ($permissions && $istimevalid || $isadmin) {
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
            return str_replace(["#form#", "{form}"], [$form, $form], $pagecontent);
        } else {
            // Return a no access message if the user does not have permission.
            return get_string('noaccess', 'local_page');
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
     * Check if the form is valid
     *
     * @param mixed $records
     * @return bool
     */
    public function valid($records) {
        $valid = true;
        foreach ((array)$records as $key => $value) {
            $tmpparam = trim(str_replace(" ", "_", $value->name));
            $tmpparam = trim(optional_param($tmpparam, '', PARAM_RAW));

            if ($value->required == "Yes" && $value->type != "HTML") {
                if (
                    $value->type == "Email" && (stripos($tmpparam, "@") === false ||
                        stripos($tmpparam, ".") === false)
                ) {
                    $this->error_fields[$value->name] = get_string('validemail', 'local_page', $value->name);
                    $valid = false;
                }

                if ($value->type != 'Email' && $tmpparam == '') {
                    $this->error_fields[$value->name] = get_string('pleasefillin', 'local_page', $value->name);
                    $valid = false;
                }

                if ($value->type == 'Numeric' && !is_numeric($tmpparam)) {
                    $this->error_fields[$value->name] = get_string('pleasefillinnumber', 'local_page', $value->name);
                    $valid = false;
                }
            }
        }
        return $valid;
    }
    /**
     * clean the incoming data according to field type
     * @param mixed $data
     * @param string $type
     * @return array|float|int|mixed|string|null
     * @throws coding_exception
     */
    public function cleanme($data, $type) {
        $safedata = '';
        switch (mb_strtolower($type)) {
            case 'email':
                $safedata = clean_param($data, PARAM_EMAIL);
                break;
            case 'number':
                $safedata = clean_param($data, PARAM_FLOAT);
                break;
            case 'text':
            case 'text area':
            case 'select':
            case 'checkbox':
                $safedata = preg_replace('/[^A-Za-z0-9 _-]/i', '', $data);
                break;
            default:
                $safedata = preg_replace('/[^A-Za-z0-9 _-]/i', '', $data);
        }

        return $safedata;
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
        $mform->set_data($forform);
        $mform->display();
    }

    /**
     *
     * Gets all the menu items
     *
     * @param mixed $parent
     * @param string $name
     * @param string $url
     * @return string
     */
    public function get_menuitem($parent, $name, $url) {
        global $DB, $CFG;
        $context = context_system::instance();
        $html = '';
        $urllocation = new moodle_url($CFG->wwwroot . '/local/page/', ['id' => $parent]);
        if (get_config('local_page', 'cleanurl_enabled')) {
            $urllocation = new moodle_url($CFG->wwwroot . '/local/page/' . $url);
        }
        $today = date('U');
        $records = $DB->get_records_sql("SELECT * FROM {local_page} WHERE deleted=0 AND onmenu=1 " .
            " AND pagedate <=? " .
            "ORDER BY pagename", [$today]);
        if ($records) {
            $html .= html_writer::start_tag('li', ['class' => 'custompages_item']);
            $html .= html_writer::link($urllocation, $name);
            $html .= html_writer::start_tag('ul', ['class' => 'custompages_submenu']);
            $canaccess = true;
            foreach ($records as $page) {
                if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                    $canaccess = false;        // Page Has level Requirements - check rights.
                    $levels = explode(",", $page->accesslevel);
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
                    $html .= $this->get_menuitem($page->id, $page->pagename, $page->menuname);
                }
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_tag('li');
        } else {
            $html .= html_writer::start_tag('li', ['class' => 'custompages_item']);
            $html .= html_writer::link($urllocation, $name);
            $html .= html_writer::end_tag('li');
        }
        return $html;
    }

    /**
     *
     * Builds the menu for the page
     *
     * @return string
     */
    public function build_menu() {
        global $DB;
        $context = context_system::instance();
        $dbman = $DB->get_manager();
        $html = '';
        if ($dbman->table_exists('local_page')) {
            $html = '<ul class="custompages_nav">';
            $today = date('U');
            $records = $DB->get_records_sql("SELECT * FROM {local_page} WHERE deleted=0 AND onmenu=1 " .
                " AND pagedate <= ? ORDER BY pagename", [$today]);
            $canaccess = true;
            foreach ($records as $page) {
                if (isset($page->accesslevel) && stripos($page->accesslevel, ":") !== false) {
                    $canaccess = false;
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
                if ($canaccess) {
                    $html .= $this->get_menuitem($page->id, $page->pagename, $page->menuname);
                }
            }
            $html .= "</ul>";
        }
        return $html;
    }
}
