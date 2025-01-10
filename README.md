# Content Analyzer WordPress Plugin

A WordPress plugin that analyzes post content for word count and keyword density.

## Features
- Analyzes published posts for content metrics
- Displays word count and keyword density
- Sortable and filterable table interface
- REST API endpoint for data access
- Responsive design

## Installation
1. Download the plugin zip file or clone the repository.
2. Log in to your WordPress admin dashboard.
3. Navigate to `Plugins > Add New > Upload Plugin`.
4. Select the `content-analyzer.zip` file and click **Install Now**.
5. Activate the plugin from the `Plugins` page.

## Usage
### Frontend Table
Add the shortcode `[content_analyzer]` to any page or post where you want the analysis table to appear.
### REST API
The plugin registers a custom REST API route:
- **Endpoint**: `/wp-json/content-analyzer/v1/analyze`
- **Methods**: `GET`
- **Parameters**:
  - `keyword` (string, required): The keyword to analyze.
  - `page` (integer, optional): The page number (default: `1`).
  - `per_page` (integer, optional): The number of posts per page (default: `10`).
  - `filter::category` (string, optional): Filter posts by category.

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher

## Development
Built using:
- WordPress REST API
- jQuery
- Custom CSS styling

## Changelog

### 1.0.0
- Initial release.
- Basic content analysis features added.