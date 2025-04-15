# ASTP Versioning System

A WordPress plugin for implementing a versioning system for Certification Companion Guides (CCG) and Test Procedures (TP) content.

## Features

- Maintains published versions while allowing new version creation
- Tracks and categorizes changes between versions
- Supports collaborative editing
- Exports content as PDF and Word documents
- Integrates with GitHub for version storage

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Advanced Custom Fields Pro plugin
- (Optional) MultiCollab plugin for enhanced change tracking

## Installation

1. Upload the `astp-versioning` folder to the `/wp-content/plugins/` directory
2. Install required Composer dependencies by running `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure GitHub integration in the settings page

## Usage

### Creating Content

1. Create a new CCG or TP post from the WordPress admin
2. Create an initial version using the Version Control metabox
3. Make changes to the content as needed
4. Create new versions as appropriate (minor updates or major releases)
5. Publish versions when ready

### Version Types

- **Major Version**: A significant update that becomes the current published version immediately
- **Minor Version**: An in-development update that can be published later

### GitHub Integration

To use GitHub integration:

1. Create a GitHub repository for storing versions
2. Generate a personal access token with repo scope
3. Configure GitHub settings in the plugin settings page
4. Test the connection

### Frontend Display

The plugin automatically adds version information to CCG and TP content:

- Version info is displayed at the top of the content
- Version history is displayed at the bottom of the content

You can also use shortcodes to display version information anywhere:

- `[astp_version_info]` - Display version info for the current post
- `[astp_version_info id="123"]` - Display version info for a specific post
- `[astp_version_history]` - Display version history for the current post
- `[astp_version_history id="123"]` - Display version history for a specific post

## Developers

### Hooks and Filters

The plugin includes several hooks for extending its functionality:

**Filters**:
- `astp_version_info_html` - Filter the version info HTML
- `astp_version_history_html` - Filter the version history HTML
- `astp_pdf_content` - Filter the content before PDF generation
- `astp_word_content` - Filter the content before Word document generation

**Actions**:
- `astp_before_version_create` - Fires before a new version is created
- `astp_after_version_create` - Fires after a new version is created
- `astp_before_version_publish` - Fires before a version is published
- `astp_after_version_publish` - Fires after a version is published
- `astp_before_github_push` - Fires before pushing to GitHub
- `astp_after_github_push` - Fires after pushing to GitHub

### Custom Post Types

- `astp_test_method` - Test Methods
- `astp_version` - Version metadata
- `astp_changelog` - Change logs

### Taxonomies

- `change_type` - Types of changes (Addition, Removal, Amendment)
- `version_type` - Types of versions (Major, Minor)

## License

Proprietary - All rights reserved 