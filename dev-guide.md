# Elementor Apple Maps Widget - Developer Guide

This guide outlines the process for extending the Elementor Apple Maps Widget with new MapKit JS features. It provides a systematic approach to ensure proper implementation across the widget's components.

## Table of Contents

- [General Implementation Process](#general-implementation-process)
- [Step-by-Step Implementation Guide](#step-by-step-implementation-guide)
  - [1. Add Widget Controls](#1-add-widget-controls-in-apple-maps-widgetphp)
  - [2. Update the Render Method](#2-update-the-render-method-in-apple-maps-widgetphp)
  - [3. Update the Content Template](#3-update-the-content-template-for-editor-preview)
  - [4. Modify the JavaScript](#4-modify-the-javascript-in-apple-mapsjs)
  - [5. Add CSS Styles if Needed](#5-add-css-styles-if-needed-in-apple-mapscss)
- [Feature Implementation Examples](#feature-implementation-examples)
  - [Example: Adding Dark Mode Support](#example-adding-dark-mode-support)
  - [Example: Adding Polygon Overlays](#example-adding-polygon-overlays)
  - [Example: Adding Custom Annotations](#example-adding-custom-annotations)
- [Best Practices](#best-practices)
- [Resources](#resources)

## General Implementation Process

Adding a new feature to the Apple Maps widget typically requires changes to three main components:

1. **Widget Controls (PHP)** - Add UI controls in the Elementor editor
2. **Frontend Rendering (PHP)** - Output necessary data attributes
3. **JavaScript Implementation** - Handle the feature in the map initialization

## Step-by-Step Implementation Guide

### 1. Add Widget Controls in `apple-maps-widget.php`

First, identify where the control should be placed in the widget's settings panels:

```php
// In the register_controls() method, add your new control
$this->add_control(
    'feature_name', // Unique identifier for your feature
    [
        'label' => __('Feature Label', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::SELECT, // Or other control type
        'default' => 'default_value',
        'options' => [
            'option1' => __('Option 1', 'elementor-apple-maps'),
            'option2' => __('Option 2', 'elementor-apple-maps'),
        ],
        'description' => __('Description of the feature', 'elementor-apple-maps'),
    ]
);
```

Common Elementor control types:
- `Controls_Manager::SELECT` - Dropdown selection
- `Controls_Manager::SWITCHER` - Toggle switch (yes/no)
- `Controls_Manager::SLIDER` - Numeric slider with range
- `Controls_Manager::COLOR` - Color picker
- `Controls_Manager::TEXT` - Text input field
- `Controls_Manager::REPEATER` - Repeatable field groups

### 2. Update the Render Method in `apple-maps-widget.php`

Add a data attribute to pass the setting to the frontend:

```php
// In the render() method
<div id="<?php echo esc_attr($map_id); ?>" class="elementor-apple-maps elementor-apple-maps-loading"
    data-latitude="<?php echo esc_attr($settings['latitude']); ?>"
    data-longitude="<?php echo esc_attr($settings['longitude']); ?>"
    data-feature-name="<?php echo esc_attr($settings['feature_name']); ?>"
    <!-- Other attributes -->
>
```

### 3. Update the Content Template for Editor Preview

Ensure the feature appears correctly in the Elementor editor preview:

```php
// In the content_template() method
protected function content_template() {
    ?>
    <#
    var mapId = 'elementor-apple-maps-' + view.getID();
    #>
    <div class="elementor-apple-maps-container">
        <div id="{{ mapId }}" class="elementor-apple-maps">
            <div class="elementor-apple-maps-preview">
                <div>
                    <strong>Apple Maps Preview</strong><br>
                    Latitude: {{ settings.latitude }}<br>
                    Feature: {{ settings.feature_name }}
                </div>
            </div>
        </div>
    </div>
    <?php
}
```

### 4. Modify the JavaScript in `apple-maps.js`

Update the `initMap` function to handle the new feature:

```javascript
function initMap(config) {
    // Existing code...
    
    // Get configuration from data attributes if not provided
    var featureName = config?.featureName || mapElement.getAttribute('data-feature-name') || 'default_value';
    
    // Later in the code where the map is created:
    var options = {
        // Existing options...
        
        // Add your feature to the options
        customFeature: featureName === 'option1' ? value1 : value2,
    };
    
    // Create map with options
    var map = new mapkit.Map(elementId, options);
    
    // For features that need to be applied after map creation:
    if (featureName === 'option1') {
        map.someProperty = someValue;
        // or
        map.someMethod();
    }
}
```

### 5. Add CSS Styles if Needed in `apple-maps.css`

```css
/* Add feature-specific styles */
.elementor-apple-maps[data-feature-name="option1"] {
    /* Styles for option1 */
}

.elementor-apple-maps[data-feature-name="option2"] {
    /* Styles for option2 */
}
```

## Feature Implementation Examples

### Example: Adding Dark Mode Support

#### 1. Added Color Scheme Control

```php
$this->add_control(
    'color_scheme',
    [
        'label' => __('Color Scheme', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => 'auto',
        'options' => [
            'auto' => __('Auto (System)', 'elementor-apple-maps'),
            'light' => __('Light', 'elementor-apple-maps'),
            'dark' => __('Dark', 'elementor-apple-maps'),
        ],
        'description' => __('Auto follows the user\'s system settings', 'elementor-apple-maps'),
    ]
);
```

#### 2. Updated the Render Method

```php
<div id="<?php echo esc_attr($map_id); ?>" class="elementor-apple-maps elementor-apple-maps-loading"
    data-latitude="<?php echo esc_attr($settings['latitude']); ?>"
    data-longitude="<?php echo esc_attr($settings['longitude']); ?>"
    data-color-scheme="<?php echo esc_attr($settings['color_scheme']); ?>"
    <!-- other attributes -->
>
```

#### 3. Updated JavaScript Implementation

```javascript
// Inside initMap function
var colorScheme = config?.colorScheme || mapElement.getAttribute('data-color-scheme') || 'auto';

// After map creation
if (colorScheme === 'dark') {
    if (map.colorScheme !== undefined) {
        map.colorScheme = mapkit.Map.ColorSchemes.Dark;
    } else {
        debug('Dark mode not supported in this version of MapKit JS');
    }
} else if (colorScheme === 'light') {
    if (map.colorScheme !== undefined) {
        map.colorScheme = mapkit.Map.ColorSchemes.Light;
    } else {
        debug('Light mode selection not supported in this version of MapKit JS');
    }
} else {
    // Auto - this is the default
    if (map.colorScheme !== undefined) {
        map.colorScheme = mapkit.Map.ColorSchemes.Adaptive;
    }
}
```

#### 4. Added CSS Styling

```css
/* Dark mode specific styles */
.elementor-apple-maps[data-color-scheme="dark"] {
    background-color: #1a1a1a;
}

.elementor-apple-maps[data-color-scheme="auto"] {
    background-color: #f5f5f5;
}

@media (prefers-color-scheme: dark) {
    .elementor-apple-maps[data-color-scheme="auto"] {
        background-color: #1a1a1a;
    }
}
```

### Example: Adding Polygon Overlays

#### 1. Add Polygon Controls

```php
$this->start_controls_section(
    'section_overlays',
    [
        'label' => __('Overlays', 'elementor-apple-maps'),
    ]
);

$repeater = new \Elementor\Repeater();

$repeater->add_control(
    'coordinates',
    [
        'label' => __('Coordinates', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::TEXTAREA,
        'default' => '',
        'placeholder' => __('[[37.7749, -122.4194], [37.7850, -122.4360], [37.7950, -122.4260]]', 'elementor-apple-maps'),
        'description' => __('Enter coordinates as JSON array of [latitude, longitude] pairs', 'elementor-apple-maps'),
    ]
);

$repeater->add_control(
    'fill_color',
    [
        'label' => __('Fill Color', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => 'rgba(0, 128, 255, 0.3)',
    ]
);

$repeater->add_control(
    'stroke_color',
    [
        'label' => __('Stroke Color', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#0066cc',
    ]
);

$repeater->add_control(
    'stroke_width',
    [
        'label' => __('Stroke Width', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::SLIDER,
        'default' => [
            'size' => 2,
        ],
        'range' => [
            'px' => [
                'min' => 1,
                'max' => 10,
            ],
        ],
    ]
);

$this->add_control(
    'polygon_overlays',
    [
        'label' => __('Polygon Overlays', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::REPEATER,
        'fields' => $repeater->get_controls(),
        'title_field' => __('Polygon', 'elementor-apple-maps'),
    ]
);

$this->end_controls_section();
```

#### 2. Update Render Method

```php
<div id="<?php echo esc_attr($map_id); ?>" class="elementor-apple-maps elementor-apple-maps-loading"
    data-latitude="<?php echo esc_attr($settings['latitude']); ?>"
    data-longitude="<?php echo esc_attr($settings['longitude']); ?>"
    data-polygons="<?php echo esc_attr(json_encode($settings['polygon_overlays'])); ?>"
    <!-- other attributes -->
>
```

#### 3. Update JavaScript

```javascript
// Inside initMap function
var polygonsData = [];
try {
    var polygonsString = mapElement.getAttribute('data-polygons');
    if (polygonsString) {
        polygonsData = JSON.parse(polygonsString);
    }
} catch (e) {
    debug('Error parsing polygon data:', e);
}

// After map creation
if (polygonsData && polygonsData.length > 0) {
    polygonsData.forEach(function(polygonData) {
        try {
            var coordsArray = JSON.parse(polygonData.coordinates);
            var coordinates = coordsArray.map(function(coord) {
                return new mapkit.Coordinate(coord[0], coord[1]);
            });
            
            var polygonOverlay = new mapkit.PolygonOverlay(coordinates, {
                style: new mapkit.Style({
                    fillColor: polygonData.fill_color,
                    strokeColor: polygonData.stroke_color,
                    strokeWidth: polygonData.stroke_width.size
                })
            });
            
            map.addOverlay(polygonOverlay);
        } catch (e) {
            debug('Error creating polygon:', e);
        }
    });
}
```

### Example: Adding Custom Annotations

#### 1. Add Annotations Controls

```php
$this->start_controls_section(
    'section_annotations',
    [
        'label' => __('Annotations', 'elementor-apple-maps'),
    ]
);

$repeater = new \Elementor\Repeater();

$repeater->add_control(
    'latitude',
    [
        'label' => __('Latitude', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => '',
    ]
);

$repeater->add_control(
    'longitude',
    [
        'label' => __('Longitude', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => '',
    ]
);

$repeater->add_control(
    'title',
    [
        'label' => __('Title', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => '',
    ]
);

$repeater->add_control(
    'subtitle',
    [
        'label' => __('Subtitle', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => '',
    ]
);

$repeater->add_control(
    'color',
    [
        'label' => __('Color', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#c969e0',
    ]
);

$this->add_control(
    'annotations',
    [
        'label' => __('Marker Annotations', 'elementor-apple-maps'),
        'type' => \Elementor\Controls_Manager::REPEATER,
        'fields' => $repeater->get_controls(),
        'title_field' => '{{{ title }}}',
    ]
);

$this->end_controls_section();
```

#### 2. Update Render Method

```php
<div id="<?php echo esc_attr($map_id); ?>" class="elementor-apple-maps elementor-apple-maps-loading"
    data-latitude="<?php echo esc_attr($settings['latitude']); ?>"
    data-longitude="<?php echo esc_attr($settings['longitude']); ?>"
    data-annotations="<?php echo esc_attr(json_encode($settings['annotations'])); ?>"
    <!-- other attributes -->
>
```

#### 3. Update JavaScript

```javascript
// Inside initMap function
var annotationsData = [];
try {
    var annotationsString = mapElement.getAttribute('data-annotations');
    if (annotationsString) {
        annotationsData = JSON.parse(annotationsString);
    }
} catch (e) {
    debug('Error parsing annotations data:', e);
}

// After map creation
if (annotationsData && annotationsData.length > 0) {
    var annotations = annotationsData.map(function(item) {
        var coordinate = new mapkit.Coordinate(
            parseFloat(item.latitude),
            parseFloat(item.longitude)
        );
        
        return new mapkit.MarkerAnnotation(coordinate, {
            title: item.title,
            subtitle: item.subtitle,
            color: item.color
        });
    });
    
    map.addAnnotations(annotations);
}
```

## Best Practices

1. **Verify MapKit JS Support**: Always check if the feature is available in MapKit JS before implementing.

2. **Use Safe Access Patterns**: Use optional chaining and default values to handle missing configuration.

3. **Add Error Handling**: Include fallbacks and debug messages for unsupported features.

4. **Test in Multiple Environments**: Test in both Elementor editor preview and frontend to ensure consistent behavior.

5. **Document in Code**: Add comments explaining how the feature works for future reference.

6. **Check Apple's Documentation**: Reference Apple's MapKit JS documentation for proper implementation details.

## Resources

- [MapKit JS Documentation](https://developer.apple.com/documentation/mapkitjs)
- [Elementor Developer Documentation](https://developers.elementor.com/)
- [Elementor Control Reference](https://developers.elementor.com/elementor-controls/)
