# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-01-24

### Added
- Advanced filter panel UI with slide-out panel design
- Filter chips component to display active filters visually
- Configurable filter panel (width, position, overlay opacity)
- Filter panel toggle button with active filter count badge (gradient design with pulse animation)
- Keyboard support (ESC key to close filter panel)
- Component publishing support for full customization
- Helper methods `availableChannels()` and `availableLevels()` in SystemLogService
- Filter chip removal functionality with matching badge design
- Mobile-responsive filter panel (full width on small screens)

### Changed
- Replaced inline filter form with modern slide-out filter panel
- Updated filter UI to use reusable Blade components
- Improved filter UX with visual chips and panel animation
- Filter panel now stays open when filters are applied (only closes on click outside or ESC key)
- Enhanced filter count badge with gradient background, pulse animation, and hover effects
- Filter chip remove buttons now match filter count badge styling
- Reset filter button now uses AJAX instead of page reload
- Improved overall UI/UX with better visual feedback

### Fixed
- Select all checkbox now works correctly after AJAX filter updates
- Filter chip remove button cross symbol now properly centered
- Reset filter button no longer redirects with unwanted filter parameters

### Removed
- "Delete All Filtered" functionality (bulk delete by filters feature)

### Configuration
- Added `ui.filter_panel` configuration section
- Added `ui.filter_chips` configuration section
- Added `filters.levels` configuration option

## [1.0.0] - 2024-12-XX

### Added
- Initial release of Laravel System Logs package
- View log entries from multiple files
- Advanced filtering and search capabilities (channel, level, environment, date, text search)
- Delete single or bulk log entries
- Bulk delete by filters with confirmation
- Recursive directory scanning with depth limits
- Flexible layout support (works with any Laravel layout)
- Security features (path validation, file size limits)
- Responsive design
- Multi-language support
- Real-time filtering via AJAX
- Permission-based access control
- Configurable route prefix and middleware
- Asset publishing for CSS and JavaScript
- View and translation customization support
