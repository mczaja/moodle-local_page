# Custom Pages Documentation

## Overview
This module allows users to create and manage custom pages within Moodle. It provides a user-friendly interface for adding, editing, and deleting pages, as well as configuring various settings related to each page.

## Features
- **Add Pages**: Users can create new pages with specific content and settings.
- **Edit Pages**: Existing pages can be modified to update content or settings.
- **Delete Pages**: Users can remove pages that are no longer needed.
- **Access Control**: Define access levels for each page based on user capabilities.

## Usage
1. Navigate to **Site Administration** > **Plugins** > **Local Plugins** > **Page** > **Manage Pages** in the Moodle admin panel.
2. Click on **Add Page** to create a new page.
3. Fill in the required fields, including page name, content, and access level.
4. Save the page to make it available to users.

## Configuration
- **Access Level**: Specify the capabilities required to view the page. Use commas to separate multiple capabilities.
- **Additional HTML**: Optionally add custom HTML to the `<head>` section of the page for additional styling or scripts.

## Friendly URLs (`menuname`)

Pages can use a **Friendly URL** slug (`menuname`) so viewers can open  
`https://yourmoodlesite.example/path/to/moodle/about-us`  
instead of  
`https://yourmoodlesite.example/path/to/moodle/local/page/index.php?menuname=about-us`.

### Incoming URL routing

- **This plugin’s `.htaccess`** (under `local/page/`) asks Apache to rewrite  
  `…/local/page/<slug>` → `…/local/page/index.php?menuname=<slug>` when `mod_rewrite` and `AllowOverride` allow it. Slugs use the same character set as the form (`PARAM_ALPHANUMEXT`, including `.`). It does **not** handle **root-level** paths like `…/about-us` (those never hit this directory).
- Moodle’s optional **`$CFG->urlrewriteclass`** only rewrites **outgoing** URLs from `moodle_url::out()`; it does **not** accept incoming requests by itself.

So for **short** URLs like `/about-us` at the site root, add **web-root** rewrite rules (below). For **only** `/local/page/about-us`, configuring the server to honour this plugin’s `.htaccess` is enough.

### Web server: rewrite at the Moodle web root

Place rules where your **Moodle installation’s URL root** is served (same vhost as `$CFG->wwwroot`). Adjust the path prefix if Moodle lives in a subdirectory (e.g. `/moodle/`).

**Apache** (`mod_rewrite`), inside the `<Directory>` for your Moodle docroot or in the vhost:

```apache
RewriteEngine On
# If the request is not a real file/dir and looks like a single slug segment, send to local_page.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9_-]+)$ /local/page/index.php?menuname=$1 [L,QSA]
```

To match the edit form’s slug rules exactly (including `.` in the slug), use `[a-zA-Z0-9._-]+` instead of `[a-zA-Z0-9_-]+` in both `RewriteRule` and Nginx patterns.

If Moodle is under `/moodle/`, use `RewriteRule ^([a-zA-Z0-9_-]+)$ /moodle/local/page/index.php?menuname=$1 [L,QSA]` (or `RewriteBase /moodle/` and a relative target—match your layout).

**Nginx** (illustrative `location`):

```nginx
location ~ ^/([a-zA-Z0-9_-]+)$ {
    try_files $uri $uri/ /local/page/index.php?menuname=$1&$query_string;
}
```

Again, prefix with your Moodle base path if not at domain root.

**Conflicts**: A catch-all slug rule can shadow other single-segment routes. Restrict slugs in the rule, reserve paths, or place this rule after more specific locations.

### Optional: shorten links Moodle prints (`urlrewriteclass`)

To rewrite **generated** links from `…/local/page/index.php?menuname=slug` to `…/slug` (so emails and UI match your rewrite), add to **`config.php`** (only one rewriter class is supported site-wide):

```php
$CFG->urlrewriteclass = '\local_page\url_rewriter';
```

You still need the **web server** rules above so that `/slug` actually runs the viewer.

## Documentation
https://rosea.gitbook.io/page-by-roseathemes

## Help
For more information, refer to the support@rosea.io
