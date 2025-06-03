# Moodle Output API and Templates Implementation

This document explains the modernization of the local_page plugin to use Moodle's modern Output API and Mustache templates instead of manual HTML generation.

## What Changed

### Before (Old Approach)
The original renderer used manual HTML generation with `html_writer` methods:

```php
public function get_allpages($parent, $name, $status, $pagedate, $enddate, $menuname = null): string {
    global $CFG, $USER;
    $html = '';
    
    // Manual HTML construction with html_writer
    $html .= html_writer::start_div('custompages-card rounded');
    $html .= html_writer::start_div($cardbodyclass);
    // ... hundreds of lines of HTML generation
    
    return $html;
}
```

### After (Modern Approach)
The modernized renderer uses the Output API with templates:

```php
public function get_allpages($parent, $name, $status, $pagedate, $enddate, $menuname = null): string {
    $pagecard = new page_card($parent, $name, $status, $pagedate, $enddate, $menuname);
    return $this->render_page_card($pagecard);
}

public function render_page_card(page_card $pagecard): string {
    return $this->render_from_template('local_page/page_card', $pagecard->export_for_template($this));
}
```

## New File Structure

```
local/page/
├── classes/output/           # Output API classes
│   ├── page_card.php        # Data preparation for page card
│   ├── pages_list.php       # Data preparation for pages list
│   └── page_content.php     # Data preparation for page content
├── templates/               # Mustache templates
│   ├── page_card.mustache   # Template for individual page cards
│   ├── pages_list.mustache  # Template for complete pages list
│   └── page_content.mustache # Template for page content display
└── renderer.php            # Modernized renderer using Output API
```

## Key Components

### 1. Output Classes (`classes/output/`)

These classes implement `renderable` and `templatable` interfaces:

- **`page_card.php`**: Prepares data for individual page cards
- **`pages_list.php`**: Handles the complete list of pages grouped by status
- **`page_content.php`**: Manages page content display with access control

Each class has:
- Constructor to accept raw data
- `export_for_template()` method to format data for templates

### 2. Templates (`templates/`)

Mustache templates for clean separation of logic and presentation:

- **`page_card.mustache`**: Individual page card with status, URLs, and actions
- **`pages_list.mustache`**: Complete list with grouping by status
- **`page_content.mustache`**: Page content with access control

### 3. Modernized Renderer

The renderer now:
- Uses `render_from_template()` instead of manual HTML construction
- Has render methods for each output type
- Maintains backward compatibility with existing method signatures

## Benefits

1. **Separation of Concerns**: Logic separated from presentation
2. **Maintainability**: Templates are easier to modify than PHP HTML generation
3. **Performance**: Templates are cached by Moodle
4. **Theme Integration**: Templates can be overridden by themes
5. **Accessibility**: Easier to ensure proper HTML structure
6. **Security**: Templates automatically handle escaping

## Usage Examples

### Creating a Page Card
```php
// In your controller or other code
$pagecard = new \local_page\output\page_card(123, 'My Page', 'live', 0, 0, 'my-page');
echo $OUTPUT->render($pagecard);
```

### Displaying Pages List
```php
// Get pages from database
$pages = $DB->get_records('local_page', ['deleted' => 0]);

// Create output object
$pageslist = new \local_page\output\pages_list($pages);

// Render using OUTPUT global or renderer
echo $OUTPUT->render($pageslist);
```

### Using in Templates (Theme Override)
Templates can be overridden in themes by placing them in:
```
theme/mytheme/templates/local_page/page_card.mustache
```

## Template Variables

### page_card.mustache
- `id`: Page ID
- `name`: Page name (shortened)
- `status`: Page status (live/draft/archived)
- `statusbadge`: HTML for status badge
- `cardbodyclass`: CSS classes for card body
- `restricted`: Boolean for date restrictions
- `editurl`: Edit page URL
- `pageurl`: Standard page URL
- `viewurl`: View page URL
- `deleteurl`: Delete page URL
- `menuname`: Menu name (optional)
- `friendlyurl`: Friendly URL (optional)

### pages_list.mustache
- `livepages`: Array of live page cards
- `draftpages`: Array of draft page cards
- `archivedpages`: Array of archived page cards
- `addpageurl`: URL to add new page

### page_content.mustache
- `hasaccess`: Boolean for access permission
- `content`: Page content HTML
- `noaccessmessage`: Message when no access

## Migration Notes

- Old methods still work for backward compatibility
- New approach is recommended for new development
- Templates can be gradually migrated
- No database changes required

This modernization follows Moodle's best practices and makes the code more maintainable, performant, and theme-friendly. 