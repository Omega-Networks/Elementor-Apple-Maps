/**
 * Simpler approach for apple-maps.js that avoids checking MapType enum
 */

var ElementorAppleMaps = (function() {
    'use strict';
    
    // Private variables
    var _maps = {};
    
    function debug(message, data) {
        console.log('ElementorAppleMaps: ' + message, data || '');
    }
    
    /**
     * Get JWT token from server
     */
    function getJwtToken() {
        return fetch(ElementorAppleMapsSettings.ajaxUrl + '?action=get_mapkit_jwt')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch token: ' + response.status);
                }
                return response.text();
            });
    }
    
    /**
     * Add event handlers to an overlay
     * @param {Object} overlay - The mapkit overlay object
     * @param {Object} originalStyle - The original style object
     * @return {Object} The overlay with event handlers attached
     */
    function addOverlayEventHandlers(overlay, originalStyle) {
        // Store original style properties
        var originalFillColor = originalStyle.fillColor;
        var originalStrokeColor = originalStyle.strokeColor;
        var originalLineWidth = originalStyle.lineWidth;
        
        // Add selection event handler
        overlay.addEventListener('select', function(event) {
            // Change styling on selection
            var highlightStyle = new mapkit.Style({
                strokeColor: '#ff0000', // Highlight with red stroke
                lineWidth: originalLineWidth + 1 // Make line slightly thicker
            });
            
            // Add fill properties if the original had them
            if (originalFillColor) {
                // Adjust opacity to make it more visible when selected
                var originalOpacity = 0.3; // Default opacity if not specified
                var color = originalFillColor;
                
                // Extract opacity if rgba format
                var rgbaMatch = originalFillColor.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/);
                if (rgbaMatch && rgbaMatch[4]) {
                    originalOpacity = parseFloat(rgbaMatch[4]);
                    
                    // Create a new rgba with higher opacity
                    var newOpacity = Math.min(originalOpacity + 0.2, 1.0);
                    color = 'rgba(' + rgbaMatch[1] + ',' + rgbaMatch[2] + ',' + rgbaMatch[3] + ',' + newOpacity + ')';
                }
                
                highlightStyle.fillColor = color;
            }
            
            this.style = highlightStyle;
        });
        
        // Add deselection event handler
        overlay.addEventListener('deselect', function(event) {
            // Restore original style
            this.style = originalStyle;
        });
        
        return overlay;
    }
    
    /**
     * Initialize map with config
     */
	function initMap(config) {
	    debug('Initializing map', config);
	    
	    // Find map element
	    var elementId = config?.elementId;
	    
	    // Check if we're in Elementor editor
	    var isElementorEditor = window.elementor && window.elementor.hasOwnProperty('previewView');
	    
	    if (!elementId) {
	        var mapElements = document.querySelectorAll('.elementor-apple-maps');
	        if (mapElements.length > 0) {
	            elementId = mapElements[0].id;
	            debug('Used fallback to find map element: ' + elementId);
	        }
	    }
	    
	    if (!elementId) {
	        console.error('ElementorAppleMaps: Could not find map element ID');
	        return;
	    }
	    
	    var mapElement = document.getElementById(elementId);
	    if (!mapElement) {
	        console.error('ElementorAppleMaps: Map element not found: ' + elementId);
	        return;
	    }
	    
	    // If we're in the editor, check if the preview element exists
	    if (isElementorEditor) {
	        var previewElement = mapElement.querySelector('.elementor-apple-maps-preview');
	        if (previewElement) {
	            // In editor with preview template active, don't initialize the actual map
	            debug('In Elementor editor with preview template - skipping actual map initialization');
	            return;
	        }
	    }
        
        // Get configuration from data attributes if not provided
        var latitude = config?.latitude || mapElement.getAttribute('data-latitude') || '0';
        var longitude = config?.longitude || mapElement.getAttribute('data-longitude') || '0';
        var zoomLevel = config?.zoomLevel || mapElement.getAttribute('data-zoom') || '12';
        var mapType = config?.mapType || mapElement.getAttribute('data-map-type') || 'standard';
        var colorScheme = config?.colorScheme || mapElement.getAttribute('data-color-scheme') || 'auto';
        var showsMapTypeControl = (config?.showsMapTypeControl || mapElement.getAttribute('data-shows-map-type-control') || 'false') === 'true';
        var isRotationEnabled = (config?.isRotationEnabled || mapElement.getAttribute('data-is-rotation-enabled') || 'false') === 'true';
        var showsCompass = config?.showsCompass || mapElement.getAttribute('data-shows-compass') || 'adaptive';
        var isZoomEnabled = (config?.isZoomEnabled || mapElement.getAttribute('data-is-zoom-enabled') || 'true') === 'true';
        var showsZoomControl = (config?.showsZoomControl || mapElement.getAttribute('data-shows-zoom-control') || 'true') === 'true';
        var isScrollEnabled = (config?.isScrollEnabled || mapElement.getAttribute('data-is-scroll-enabled') || 'true') === 'true';
        var showsScale = config?.showsScale || mapElement.getAttribute('data-shows-scale') || 'adaptive';
        
        // Show loading state
        mapElement.classList.add('elementor-apple-maps-loading');
        
        // Get JWT token and initialize map
        getJwtToken().then(token => {
            debug('Token received, initializing map');
            
            // Wait for MapKit script to load (might be already loaded)
            if (typeof mapkit === 'undefined') {
                // Load script if not already loaded
                var script = document.createElement('script');
                script.src = 'https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js';
                document.head.appendChild(script);
                
                return new Promise((resolve) => {
                    script.onload = () => {
                        debug('MapKit script loaded');
                        resolve(token);
                    };
                });
            }
            
            return token;
        }).then(token => {
            // Initialize MapKit
            mapkit.init({
                authorizationCallback: function(done) {
                    done(token);
                }
            });
            
            debug('MapKit initialized, creating map');
            
            // Basic options
            var options = {
                showsCompass: showsCompass,
                showsScale: showsScale,
                showsMapTypeControl: showsMapTypeControl,
                isRotationEnabled: isRotationEnabled,
                isZoomEnabled: isZoomEnabled,
                showsZoomControl: showsZoomControl,
                isScrollEnabled: isScrollEnabled
            };
            
            // Set color scheme - Apply after map creation
            // Convert string mapType to MapKit map type
            if (mapType === 'satellite') {
                options.mapType = 1; // Satellite
            } else if (mapType === 'hybrid') {
                options.mapType = 2; // Hybrid
            } else if (mapType === 'mutedStandard') {
                options.mapType = 3; // MutedStandard
            } else {
                options.mapType = 0; // Standard
            }
            
            // Get center coordinates
            var center = new mapkit.Coordinate(
                parseFloat(latitude),
                parseFloat(longitude)
            );
            
            // Create map
            var map = new mapkit.Map(elementId, options);
            
            // Now set the color scheme AFTER creating the map
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
            
            // Set zoom level
            var zoomValue = parseInt(zoomLevel) || 12;
            var span = new mapkit.CoordinateSpan(
                0.1 * Math.pow(0.7, zoomValue - 10),
                0.1 * Math.pow(0.7, zoomValue - 10)
            );
            map.region = new mapkit.CoordinateRegion(center, span);
            
            // Process overlays
            try {
                var overlaysData = mapElement.getAttribute('data-overlays');
                if (overlaysData) {
                    var overlays = JSON.parse(overlaysData);
                    
                    if (Array.isArray(overlays)) {
                        overlays.forEach(function(overlay) {
                            try {
                                // Create style for the overlay
                                var style = new mapkit.Style({
                                    strokeColor: overlay.stroke_color || '#000000',
                                    lineWidth: overlay.stroke_width ? overlay.stroke_width.size : 2
                                });
                                
                                // Add fill properties for polygon and circle
                                if (overlay.overlay_type === 'polygon' || overlay.overlay_type === 'circle') {
                                    style.fillColor = overlay.fill_color || 'rgba(0, 128, 255, 0.3)';
                                }
                                
                                // Parse coordinates from JSON string
                                var coords;
                                try {
                                    coords = JSON.parse(overlay.coordinates || '[]');
                                } catch (parseError) {
                                    debug('Error parsing coordinates for overlay: ' + parseError.message);
                                    return; // Skip this overlay
                                }
                                
                                // Create different overlay types based on overlay_type
                                if (overlay.overlay_type === 'polygon' && Array.isArray(coords) && coords.length > 2) {
                                    // Convert coordinates to mapkit.Coordinate objects
                                    var mapkitCoords = coords.map(function(coord) {
                                        return new mapkit.Coordinate(coord[0], coord[1]);
                                    });
                                    
                                    // Create the polygon overlay
                                    var polygonOverlay = new mapkit.PolygonOverlay(mapkitCoords, {
                                        style: style
                                    });
                                    
                                    // Add event handlers for interactive overlays
                                    polygonOverlay = addOverlayEventHandlers(polygonOverlay, style);
                                    
                                    // Add to map
                                    map.addOverlay(polygonOverlay);
                                    debug('Added polygon overlay with ' + mapkitCoords.length + ' points');
                                    
                                } else if (overlay.overlay_type === 'polyline' && Array.isArray(coords) && coords.length > 1) {
                                    // Convert coordinates to mapkit.Coordinate objects
                                    var mapkitCoords = coords.map(function(coord) {
                                        return new mapkit.Coordinate(coord[0], coord[1]);
                                    });
                                    
                                    // Create the polyline overlay
                                    var polylineOverlay = new mapkit.PolylineOverlay(mapkitCoords, {
                                        style: style
                                    });
                                    
                                    // Add event handlers for interactive overlays
                                    polylineOverlay = addOverlayEventHandlers(polylineOverlay, style);
                                    
                                    // Add to map
                                    map.addOverlay(polylineOverlay);
                                    debug('Added polyline overlay with ' + mapkitCoords.length + ' points');
                                    
                                } else if (overlay.overlay_type === 'circle' && Array.isArray(coords) && coords.length > 0) {
                                    // Create circle center coordinate
                                    var centerCoord = new mapkit.Coordinate(coords[0][0], coords[0][1]);
                                    var radius = parseFloat(overlay.radius) || 1000;
                                    
                                    // Create the circle overlay
                                    var circleOverlay = new mapkit.CircleOverlay(centerCoord, radius, {
                                        style: style
                                    });
                                    
                                    // Add event handlers for interactive overlays
                                    circleOverlay = addOverlayEventHandlers(circleOverlay, style);
                                    
                                    // Add to map
                                    map.addOverlay(circleOverlay);
                                    debug('Added circle overlay at ' + centerCoord.latitude + ',' + centerCoord.longitude + ' with radius ' + radius);
                                }
                            } catch (overlayError) {
                                debug('Error creating overlay: ' + overlayError.message);
                            }
                        });
                    }
                }
            } catch (error) {
                debug('Error processing overlays: ' + error.message);
            }
            
            // Store map for later reference
            _maps[elementId] = map;
            
            // Update map element state
            mapElement.classList.remove('elementor-apple-maps-loading');
            mapElement.classList.add('elementor-apple-maps-loaded');
            
            debug('Map created successfully');
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (_maps[elementId]) {
                    _maps[elementId].redraw();
                }
            });
            
        }).catch(error => {
            console.error('ElementorAppleMaps: Failed to initialize map', error);
            mapElement.classList.remove('elementor-apple-maps-loading');
            mapElement.innerHTML = '<div class="elementor-apple-maps-error">Failed to load Apple Maps: ' + error.message + '</div>';
        });
    }
    
    // Initialize maps on DOM content loaded
    document.addEventListener('DOMContentLoaded', function() {
        debug('DOM loaded, checking for maps');
        
        // Process queue if it exists
        if (window.elementorAppleMapsQueue && window.elementorAppleMapsQueue.length > 0) {
            debug('Processing ' + window.elementorAppleMapsQueue.length + ' queued maps');
            window.elementorAppleMapsQueue.forEach(function(config) {
                initMap(config);
            });
            window.elementorAppleMapsQueue = [];
        } else {
            // Find maps on page if no queue
            var mapElements = document.querySelectorAll('.elementor-apple-maps');
            if (mapElements.length > 0) {
                debug('Found ' + mapElements.length + ' maps on page');
                mapElements.forEach(function(element) {
                    initMap({ elementId: element.id });
                });
            }
        }
    });
    
    // Support for Elementor editor
	if (window.elementor) {
	    debug('Elementor detected, adding editor event handlers');
	    
	    // When a section, column, or widget is added or removed
	    window.elementor.channels.data.on('element:after:add element:after:remove', function(model) {
	        setTimeout(function() {
	            var mapElements = document.querySelectorAll('.elementor-apple-maps');
	            mapElements.forEach(function(element) {
	                if (!element.classList.contains('elementor-apple-maps-loaded')) {
	                    initMap({ elementId: element.id });
	                }
	            });
	        }, 500);
	    });
	    
	    // When a control value changes (for our widget)
	    window.elementor.channels.editor.on('change', function(view) {
	        var model = view.model;
	        if (model && model.get('widgetType') === 'apple_maps') {
	            debug('Apple Maps widget settings changed');
	            
	            // Find the element
	            var elementId = 'elementor-apple-maps-' + model.get('id');
	            var mapElement = document.getElementById(elementId);
	            
	            if (mapElement && _maps[elementId]) {
	                // Remove old map instance
	                delete _maps[elementId];
	                
	                // Re-initialize with new settings
	                setTimeout(function() {
	                    initMap({ elementId: elementId });
	                }, 100);
	            }
	        }
	    });
	}
    
    // Public API
    return {
        initMap: initMap,
        getMap: function(elementId) {
            return _maps[elementId] || null;
        }
    };
})();