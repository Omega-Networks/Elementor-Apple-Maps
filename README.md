# Elementor Apple Maps

An advanced WordPress plugin that seamlessly integrates Apple Maps into your Elementor-powered website, providing a beautiful and feature-rich mapping experience.

## Description

Elementor Apple Maps brings the power of Apple's MapKit JS to your WordPress website through an intuitive Elementor widget. Unlike other mapping solutions, this plugin leverages Apple's beautiful map rendering, accurate data, and smooth performance to enhance your site with professional-grade maps.

### Key Features

- **Beautiful Apple Maps Integration**: Embed the same high-quality maps found in Apple products directly on your website
- **Multiple Map Types**: Choose between Standard, Satellite, Hybrid, and Muted Standard map types
- **Custom Markers**: Add and style location markers with custom colors and optional titles/subtitles
- **Customizable Interface**: Control zoom level, rotation, compass visibility, and more
- **Color Scheme Options**: Support for Light, Dark, and Adaptive color modes to match your website's design
- **Advanced Overlays**: Add polygons, polylines, and circles to highlight regions or routes
- **Custom Annotations**: Mark locations with standard pins, custom images, or HTML-based markers
- **Interactive Maps**: Implement event listeners for user actions such as clicks, selections, and region changes
- **Responsive Design**: Maps automatically adjust to look great on any device
- **Developer-Friendly**: Clean code with extensive documentation for easy customization

## Installation

### Requirements

- WordPress 5.6 or higher
- Elementor 3.0.0 or higher
- PHP 7.4 or higher
- Active Apple Developer account with MapKit JS access

### Standard Installation

1. Download the plugin from the repository
2. Upload the plugin files to the `/wp-content/plugins/elementor-apple-maps` directory
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Configure your Apple Developer credentials in Settings > Apple Maps

### Setting Up Apple Developer Credentials

Before using the plugin, you'll need to configure your Apple Developer credentials:

1. Sign up for the [Apple Developer Program](https://developer.apple.com/programs/enroll/)
2. Create a Maps ID and MapKit JS Private Key by following [Apple's official instructions](https://developer.apple.com/documentation/mapkitjs/creating_a_maps_identifier_and_a_private_key)
3. In your WordPress admin, go to Settings > Apple Maps
4. Enter your Private Key, Key ID, and Team ID
5. Click "Confirm MapKit Credentials" to verify and save

## Usage

### Basic Map

1. Edit a page with Elementor
2. Drag the "Apple Maps" widget from the widget panel to your page
3. Set the desired location using latitude and longitude coordinates
4. Configure map appearance and controls as needed
5. Save and publish your page

### Advanced Features

#### Custom Markers

1. Enable "Show Marker" in the widget settings
2. Set marker title, subtitle, and color
3. Optionally upload a custom marker icon

#### Color Schemes

1. In the Map Settings section, locate the "Color Scheme" option
2. Choose between Auto (follows system settings), Light, or Dark modes

#### Overlays (Polygons, Polylines, Circles)

1. Add coordinate points in the Overlays section
2. Configure styling options such as fill color, stroke width, and opacity
3. Save to display the shapes on your map

## Developer Documentation

For developers looking to extend or customize the plugin, please refer to our [Developer Guide](docs/developer-guide.md) which includes:

- Implementation patterns for adding new features
- Custom styling options
- Filter and action hooks reference
- Examples of common customizations

## License

This project is licensed under the Non-Commercial License - see the [LICENSE](LICENSE) file for details.

Copyright © 2025 Omega Networks Ltd, New Zealand

Permission is hereby granted to use this software for personal, educational, and non-commercial purposes. Commercial use of this software requires an explicit agreement with Omega Networks Ltd. For commercial licensing inquiries, please contact [licensing@omeganetworks.co.nz](mailto:licensing@omeganetworks.co.nz).

## Support

For support and feature requests, please use the GitHub issue tracker or contact us at [support@omeganetworks.co.nz](mailto:support@omeganetworks.co.nz).

## Changelog

### 1.0.0
- Initial release with basic Apple Maps integration
- Map type selection (Standard, Satellite, Hybrid, Muted Standard)
- Custom marker support
- Responsive design
- Color scheme options (Light, Dark, Adaptive)

## Credits

- Developed by [Omega Networks Ltd](https://omeganetworks.co.nz)
- Uses [Apple's MapKit JS](https://developer.apple.com/documentation/mapkitjs)
- Compatible with [Elementor Page Builder](https://elementor.com/)
