# CHANGELOG

## [1.0.9] - 2026-04-29

### Security
- Page visibility and file access aligned so hidden or draft content is not leaked.
- User placeholders in page content only use safe profile fields.
- Open Graph images limited to common raster formats.

### Fixed
- Site “Additional HTML” and meta tags on the public page index behave correctly.
- Admin pages list, delete flow, SQL portability, and capability checks cleaned up.
- Form parameter types tightened; removed unused settings and dead code paths.

### Added
- Optional friendly outgoing URLs via `local_page\url_rewriter` and docs.

### Improved
- Edit form strings localised; clearer admin help for access levels and URLs.

## [1.0.7] - 2026-02-17
### Improved
- Enhanced Moodle code precheck compliance for better code quality and maintainability.

## [1.0.7] - 2026-02-09
### Fixed
- Minor bug fixes and improvements

## [1.0.6] - 2025-10-09
### Fixed
- Additional HTML Content

## [1.0.5] - 2025-10-07
### Fixed
- Resolved issue with the Delete Page button not functioning as expected.

## [1.0.4] - 2025-07-21
### Added
- Enhanced compatibility with Moodle 5.0.

### Improved
- Improved support for friendly URLs in custom pages.

## [1.0.3] - 2025-06-24
### Added
- Option to hide the page title.

### Fixed
- Added missing language string for capability definition.

## [1.0.2] - 2025-06-03
### Added
- Option to hide the page title.

### Fixed
- Added missing language string for capability definition.

## [1.0.1] - 2025-04-30
### Added
- Modal confirmation dialog for page deletion to prevent accidental removal.
- Enhanced user experience with clear confirmation messages and action buttons.

### Fixed
- Added missing Open Graph image file.
- Replaced hard-coded language strings with language file references.
- Added missing language string for capability definition.

## [1.0.0] - 2025-04-02
### Added
- Initial release.