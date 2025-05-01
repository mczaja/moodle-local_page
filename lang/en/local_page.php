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
 * Local Pages Module
 *
 * This module facilitates the creation and management of custom pages and forms within Moodle.
 *
 * @package    local_page
 * @copyright  2025 Marcin Czaja RoseaThemes (rosea.io)
 * @author     Marcin Czaja
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Strings for the settings page.
$string['addpage'] = "Add New Page";
$string['addpages'] = "Add Multiple Pages";
$string['backtolist'] = "Return to Pages List";
$string['cleanurl_enabled'] = 'Activate Clean URLs';
$string['cleanurl_enabled_description'] = 'Enable the use of clean URLs for links. <br />
<strong>Note:</strong> This will replace the default link structure. For example: <strong>about-us</strong> will result in http://URL/local/page/<strong>about-us</strong><br />
<strong>Mod_rewrite</strong> must be enabled on your server to use clean URLs.';
$string['confirmdeletepage'] = 'Are you sure you want to delete the page \'{$a}\'?';
$string['custompage_title'] = 'Page Management';
$string['delete'] = "Remove";
$string['edit'] = "Modify";
$string['edit_details'] = "Edit Details";
$string['edit_head'] = "Content for &lt;head&gt;";
$string['edit_htmlhead'] = "HTML &lt;head&gt;";
$string['edit_navigation'] = "Navigation Settings";
$string['edit_ogimage'] = "Open Graph Image File";
$string['edit_pagedisplay'] = "Page Display Settings";
$string['form'] = 'Form';
$string['form_field_content'] = "Form Details";
$string['form_field_date'] = "Start Publishing Date";
$string['form_field_enddate'] = "End Publishing Date";
$string['form_field_enddate_description'] = "End Publishing Date";
$string['form_field_enddate_description_help'] = "Select the date when this page will be unpublished - a past date will restrict access until that date.";
$string['form_field_enddate_help'] = "Select the date for publishing this page - access will be restricted until the specified date.";
$string['form_field_id'] = "Identifier";
$string['formbuilder'] = "Form Builder Tool";
$string['hide'] = 'Conceal';
$string['label_add'] = "Add Item";
$string['label_name'] = "Page Name";
$string['label_placeholder'] = "Placeholder Text";
$string['label_relatesto'] = "Related To";
$string['label_remove'] = "Remove Item";
$string['label_required'] = "Mandatory Field";
$string['page:addpages'] = "Add Pages";
$string['managepages'] = "Manage Pages";
$string['menu_name'] = 'Friendly URL';
$string['menu_name_description'] = 'Friendly URL Description';
$string['menu_name_description_help'] = 'Provide a user-friendly URL for the page. Use only letters, numbers, and hyphens. This will replace the default link structure. For example: <strong>about-us</strong> will result in http://URL/local/page/<strong>about-us</strong>';
$string['metaauthor'] = 'Meta Author';
$string['metaauthor_description'] = 'Meta Author Description';
$string['metaauthor_description_help'] = 'Provide a meta author for the page. This will be used to identify the author of the page.';
$string['metadescription'] = 'Meta Description';
$string['metadescription_description'] = 'Meta Description Description';
$string['metadescription_description_help'] = 'Provide a meta description for the page. This will be used to describe the page to search engines.';
$string['metakeywords'] = 'Meta Keywords';
$string['metakeywords_description'] = 'Meta Keywords Description';
$string['metakeywords_description_help'] = 'Provide a meta keywords for the page. This will be used to describe the page to search engines.';
$string['metarobots'] = 'Meta Robots';
$string['metarobots_description'] = 'Meta Robots Description';
$string['metarobots_description_help'] = 'Provide a meta robots tag for the page. This will be used to control how search engines index the page. Available options include:<br />
<ul>
    <li>"index": Allow indexing of the page.</li>
    <li>"noindex": Prevent indexing of the page.</li>
    <li>"follow": Allow following of links on the page.</li>
    <li>"nofollow": Prevent following of links on the page.</li>
    <li>"noarchive": Prevent search engines from caching the page.</li>
    <li>"nosnippet": Prevent search engines from showing a snippet of the page in search results.</li>
    <li>"noodp": Prevent the use of Open Directory Project (DMOZ) data for the page.</li>
    <li>"notranslate": Prevent search engines from offering translation of the page.</li>
    <li>"noimageindex": Prevent search engines from indexing images on the page.</li>
</ul>';
$string['metatitle'] = 'Meta Title';
$string['metatitle_description'] = 'Meta Title Description';
$string['metatitle_description_help'] = 'Provide a meta title for the page. This will be used to display the title of the page in search results.';
$string['no'] = "No";
$string['noaccess'] = 'You do not have permission to view this page.';
$string['none'] = "None";
$string['numeric'] = "Numeric Value";
$string['onlyloggedin'] = "Only Logged In";
$string['onlyloggedin_description'] = "Only show the page to logged in users";
$string['onlyloggedin_description_help'] = "<ul>
    <li>If you select 'Yes', the page will only be visible to logged in users.</li>
    <li>If you select 'No', the page will be visible to all users.</li>
    <li>Non-logged in users will see a message that the page is only visible to logged in users.</li>
    <li>Guest users will see a message that the page is only visible to logged in users.</li>
</ul>";
$string['page'] = 'Page';
$string['page:addpages'] = 'Add Pages';
$string['page_accesslevel'] = "Required Capability";
$string['page_content'] = 'Content of the Page';
$string['page_content_description'] = 'Enter the content for the page here.';
$string['page_date'] = 'Publication Date';
$string['page_loggedin'] = "Require Users to Log In";
$string['page_name'] = 'Title of the Page';
$string['page_order'] = 'Order of Pages';
$string['page_parent'] = 'Parent Page';
$string['pagecontent'] = 'Content of the Page';
$string['pagecontent_description'] = "Content for the Page";
$string['pagecontent_description_help'] = "Provide the content for the page.";
$string['pagedate'] = "Page Publication Date";
$string['pagedate_description'] = 'Select the date when this page will be published - a future date will restrict access until that date.';
$string['pagedate_description_help'] = 'Select the date for publishing this page - access will be restricted until the specified date.';
$string['pagesetup_heading'] = 'Page Setup Heading';
$string['pagesetup_title'] = 'Page Setup Title';
$string['pdfmanual'] = "PDF User Manual";
$string['placeholder_fieldname'] = "Field Name Placeholder";
$string['placeholder_text'] = "Placeholder Text Example";
$string['pleasefillin'] = 'Please complete the field: {$a}.';
$string['pleasefillinnumber'] = 'Please enter a number for: {$a}.';
$string['pleaseselect'] = 'Please select an option from the list.';
$string['pluginname'] = 'Custom Pages by RoseaThemes';
$string['pluginsettings'] = 'Plugin Settings';
$string['pluginsettings_managepages'] = 'Manage Pages Settings';
$string['privacy:metadata'] = 'The local pages plugin does not store any personal data.';
$string['restricted'] = 'Restricted by date';
$string['select_checkbox'] = "Checkbox Option";
$string['select_fullname'] = "Full Name Option";
$string['select_html'] = "HTML Option";
$string['select_no'] = "No Option";
$string['select_nothing'] = "No Selection";
$string['select_number'] = "Number Option";
$string['select_select'] = "Select Option";
$string['select_text'] = "Text Option";
$string['select_text_area'] = "Text Area Option";
$string['select_yes'] = "Yes Option";
$string['setting_additionalhead'] = "Enable Additional HTML in Head";
$string['setting_additionalhead_description'] = "Allow custom content to be added to the HTML &lt;head&gt; section.";
$string['show'] = 'Display';
$string['status'] = 'Status';
$string['status_archived'] = 'Archived';
$string['status_description'] = 'Select the status of the page.';
$string['status_draft'] = 'Draft';
$string['status_live'] = 'Live';
$string['submit'] = "Submit Form";
$string['textarea'] = 'Text Area Field';
$string['to'] = 'to';
$string['type'] = 'Field Type';
$string['view'] = "View Page";
$string['yes'] = "Yes";
