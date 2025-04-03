<?php
/**
 * Elementor Apple Maps Widget.
 * 
 * An improved implementation that incorporates best practices from the Block-based plugin.
 * With added debug logging capabilities to diagnose loading issues.
 */
class Elementor_Apple_Maps extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'apple_maps';
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return __('Apple Maps', 'elementor-apple-maps');
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-google-maps';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return ['general'];
    }

    /**
     * Get widget keywords.
     */
    public function get_keywords() {
        return ['map', 'apple', 'location', 'mapkit'];
    }

    /**
     * Register widget scripts.
     */
    public function get_script_depends() {
        return ['elementor-apple-maps-js'];
    }

    /**
     * Register widget styles.
     */
    public function get_style_depends() {
        return ['elementor-apple-maps-css'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        // Location Section
        $this->start_controls_section(
            'section_location',
            [
                'label' => __('Location', 'elementor-apple-maps'),
            ]
        );

        $this->add_control(
            'address',
            [
                'label' => __('Address', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Enter address', 'elementor-apple-maps'),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'latitude',
            [
                'label' => __('Latitude', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '37.7749',
                'placeholder' => '37.7749',
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'longitude',
            [
                'label' => __('Longitude', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '-122.4194',
                'placeholder' => '-122.4194',
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'search_button',
            [
                'label' => __('Find Coordinates', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::BUTTON,
                'button_type' => 'default',
                'text' => __('Search', 'elementor-apple-maps'),
                'event' => 'elementorAppleMaps:searchAddress',
                'separator' => 'after',
            ]
        );

        $this->end_controls_section();

        // Map Settings Section
        $this->start_controls_section(
            'section_map_settings',
            [
                'label' => __('Map Settings', 'elementor-apple-maps'),
            ]
        );

        $this->add_control(
            'zoom_level',
            [
                'label' => __('Zoom Level', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [
                    'size' => 12,
                ],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
            ]
        );

        $this->add_control(
            'map_type',
            [
                'label' => __('Map Type', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'standard',
                'options' => [
                    'standard' => __('Standard', 'elementor-apple-maps'),
                    'satellite' => __('Satellite', 'elementor-apple-maps'),
                    'hybrid' => __('Hybrid', 'elementor-apple-maps'),
                    'mutedStandard' => __('Muted Standard', 'elementor-apple-maps'),
                ],
            ]
        );
        
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

        $this->end_controls_section();
        
        // Overlay Settings
        $this->start_controls_section(
		    'section_overlays',
		    [
		        'label' => __('Overlays', 'elementor-apple-maps'),
		    ]
		);
		
		$this->add_control(
		    'overlays',
		    [
		        'label' => __('Overlays', 'elementor-apple-maps'),
		        'type' => \Elementor\Controls_Manager::REPEATER,
		        'fields' => [
		            [
		                'name' => 'overlay_type',
		                'label' => __('Overlay Type', 'elementor-apple-maps'),
		                'type' => \Elementor\Controls_Manager::SELECT,
		                'default' => 'polygon',
		                'options' => [
		                    'polygon' => __('Polygon', 'elementor-apple-maps'),
		                    'polyline' => __('Polyline', 'elementor-apple-maps'),
		                    'circle' => __('Circle', 'elementor-apple-maps'),
		                ],
		            ],
		            [
		                'name' => 'coordinates',
		                'label' => __('Coordinates', 'elementor-apple-maps'),
		                'type' => \Elementor\Controls_Manager::TEXTAREA,
		                'description' => __('For Polygon/Polyline: JSON array of [lat, lon] pairs, e.g., [[-41.2865, 174.7762], ...]. For Circle: Single [lat, lon] pair.'),
		                'default' => '',
		                'condition' => [
		                    'overlay_type' => ['polygon', 'polyline', 'circle'],
		                ],
		            ],
		            [
		                'name' => 'radius',
		                'label' => __('Radius (meters)', 'elementor-apple-maps'),
		                'type' => \Elementor\Controls_Manager::NUMBER,
		                'default' => 1000,
		                'condition' => [
		                    'overlay_type' => 'circle',
		                ],
		            ],
		            [
		                'name' => 'fill_color',
		                'label' => __('Fill Color', 'elementor-apple-maps'),
		                'type' => \Elementor\Controls_Manager::COLOR,
		                'default' => 'rgba(0, 128, 255, 0.3)',
		                'condition' => [
		                    'overlay_type' => ['polygon', 'circle'],
		                ],
		            ],
		            [
		                'name' => 'stroke_color',
		                'label' => __('Stroke Color', 'elementor-apple-maps'),
		                'type' => \Elementor\Controls_Manager::COLOR,
		                'default' => '#000000',
		            ],
		            [
		                'name' => 'stroke_width',
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
		            ],
		        ],
		        'title_field' => '{{{ overlay_type }}} Overlay',
		        'default' => [
		            [
		                'overlay_type' => 'polygon',
		                'coordinates' => '[[-41.2865, 174.7762], [-41.2500, 174.7500], [-41.3500, 174.8500], [-41.3000, 174.7000]]',
		                'fill_color' => 'rgba(0, 128, 255, 0.3)',
		                'stroke_color' => '#000000',
		            ],
		        ],
		    ]
		);
		
		// Add example Wellington Region polygon control
		$this->add_control(
		    'wellington_region_example',
		    [
		        'type' => \Elementor\Controls_Manager::RAW_HTML,
		        'raw' => __('Example: Wellington Region polygon coordinates:<br><code>[[-41.2865, 174.7762], [-41.2500, 174.7500], [-41.3500, 174.8500], [-41.3000, 174.7000]]</code>', 'elementor-apple-maps'),
		        'content_classes' => 'elementor-descriptor',
		    ]
		);
		
		$this->end_controls_section();

        // Advanced Map Controls Section
        $this->start_controls_section(
            'section_advanced_controls',
            [
                'label' => __('Advanced Controls', 'elementor-apple-maps'),
            ]
        );

        $this->add_control(
            'shows_map_type_control',
            [
                'label' => __('Show Map Type Control', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'elementor-apple-maps'),
                'label_off' => __('No', 'elementor-apple-maps'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'is_rotation_enabled',
            [
                'label' => __('Enable Rotation', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'elementor-apple-maps'),
                'label_off' => __('No', 'elementor-apple-maps'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'shows_compass',
            [
                'label' => __('Show Compass', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'adaptive',
                'options' => [
                    'hidden' => __('Hidden', 'elementor-apple-maps'),
                    'visible' => __('Visible', 'elementor-apple-maps'),
                    'adaptive' => __('Adaptive', 'elementor-apple-maps'),
                ],
                'condition' => [
                    'is_rotation_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'is_zoom_enabled',
            [
                'label' => __('Enable Zoom', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'elementor-apple-maps'),
                'label_off' => __('No', 'elementor-apple-maps'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'shows_zoom_control',
            [
                'label' => __('Show Zoom Controls', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'elementor-apple-maps'),
                'label_off' => __('No', 'elementor-apple-maps'),
                'default' => 'yes',
                'condition' => [
                    'is_zoom_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'is_scroll_enabled',
            [
                'label' => __('Enable Scroll', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'elementor-apple-maps'),
                'label_off' => __('No', 'elementor-apple-maps'),
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'shows_scale',
            [
                'label' => __('Show Scale', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'adaptive',
                'options' => [
                    'hidden' => __('Hidden', 'elementor-apple-maps'),
                    'visible' => __('Visible', 'elementor-apple-maps'),
                    'adaptive' => __('Adaptive', 'elementor-apple-maps'),
                ],
            ]
        );

        $this->end_controls_section();

        // Appearance Section
        $this->start_controls_section(
            'section_appearance',
            [
                'label' => __('Appearance', 'elementor-apple-maps'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'map_height',
            [
                'label' => __('Height', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh', '%'],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-apple-maps' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'map_border_radius',
            [
                'label' => __('Border Radius', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-apple-maps' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'map_box_shadow',
                'selector' => '{{WRAPPER}} .elementor-apple-maps',
            ]
        );

        $this->add_responsive_control(
            'map_margin',
            [
                'label' => __('Margin', 'elementor-apple-maps'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-apple-maps-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Check if MapKit credentials are set and valid
     */
    private function are_mapkit_credentials_valid() {
        $options = get_option('apple_maps_settings', []);
        
        return (
            isset($options['maps_private_key']) && !empty($options['maps_private_key']) &&
            isset($options['maps_key_id']) && !empty($options['maps_key_id']) &&
            isset($options['maps_team_id']) && !empty($options['maps_team_id']) &&
            isset($options['mapkit_status']) && $options['mapkit_status'] === 'authorized'
        );
    }

    /**
     * Render widget output on the frontend.
     */
	protected function render() {
	    $settings = $this->get_settings_for_display();
	
	    // Enqueue scripts only when widget is rendered
	    wp_enqueue_style('elementor-apple-maps-css');
	    wp_enqueue_script('elementor-apple-maps-js');
	    
	    // MapKit JS should be loaded by our JavaScript, not directly here
	    // This avoids the double-loading issue
	
	    if (!$this->are_mapkit_credentials_valid()) {
	        // Credential warning code...
	        return;
	    }
	
	    $map_id = 'elementor-apple-maps-' . $this->get_id();
	    ?>
	    <div class="elementor-apple-maps-container">
	        <div id="<?php echo esc_attr($map_id); ?>" class="elementor-apple-maps elementor-apple-maps-loading"
    			data-latitude="<?php echo esc_attr($settings['latitude']); ?>"
    			data-longitude="<?php echo esc_attr($settings['longitude']); ?>"
    			data-zoom="<?php echo esc_attr($settings['zoom_level']['size']); ?>"
    			data-map-type="<?php echo esc_attr($settings['map_type']); ?>"
    			data-color-scheme="<?php echo esc_attr($settings['color_scheme']); ?>"
    			data-shows-map-type-control="<?php echo $settings['shows_map_type_control'] === 'yes' ? 'true' : 'false'; ?>"
    			data-is-rotation-enabled="<?php echo $settings['is_rotation_enabled'] === 'yes' ? 'true' : 'false'; ?>"
    			data-shows-compass="<?php echo esc_attr($settings['shows_compass']); ?>"
    			data-is-zoom-enabled="<?php echo $settings['is_zoom_enabled'] === 'yes' ? 'true' : 'false'; ?>"
    			data-shows-zoom-control="<?php echo $settings['shows_zoom_control'] === 'yes' ? 'true' : 'false'; ?>"
    			data-is-scroll-enabled="<?php echo $settings['is_scroll_enabled'] === 'yes' ? 'true' : 'false'; ?>"
    			data-shows-scale="<?php echo esc_attr($settings['shows_scale']); ?>"
    			data-overlays="<?php echo esc_attr(json_encode($settings['overlays'])); ?>">
	        </div>
	    </div>
	    <script>
	        (function() {
	            // Only queue for initialization, don't try to load MapKit here
	            var config = {
	                /* ... configuration data ... */
	            };
	            window.elementorAppleMapsQueue = window.elementorAppleMapsQueue || [];
	            window.elementorAppleMapsQueue.push(config);
	        })();
	    </script>
	    <?php
	}

    /**
     * Render widget output in the editor.
     */
	protected function content_template() {
	    ?>
	    <#
	    var mapId = 'elementor-apple-maps-' + view.getID();
	    #>
	    <div class="elementor-apple-maps-container">
	        <div id="{{ mapId }}" class="elementor-apple-maps"
	            data-latitude="{{ settings.latitude }}"
	            data-longitude="{{ settings.longitude }}"
	            data-zoom="{{ settings.zoom_level.size }}"
	            data-map-type="{{ settings.map_type }}"
	            data-color-scheme="{{ settings.color_scheme }}"
	            data-shows-map-type-control="{{ settings.shows_map_type_control === 'yes' ? 'true' : 'false' }}"
	            data-is-rotation-enabled="{{ settings.is_rotation_enabled === 'yes' ? 'true' : 'false' }}"
	            data-shows-compass="{{ settings.shows_compass }}"
	            data-is-zoom-enabled="{{ settings.is_zoom_enabled === 'yes' ? 'true' : 'false' }}"
	            data-shows-zoom-control="{{ settings.shows_zoom_control === 'yes' ? 'true' : 'false' }}"
	            data-is-scroll-enabled="{{ settings.is_scroll_enabled === 'yes' ? 'true' : 'false' }}"
	            data-shows-scale="{{ settings.shows_scale }}"
	            data-overlays="{{ JSON.stringify(settings.overlays) }}">
	        </div>
	    </div>
	    <script>
	    (function() {
	        // Queue for initialization
	        window.elementorAppleMapsQueue = window.elementorAppleMapsQueue || [];
	        window.elementorAppleMapsQueue.push({
	            elementId: '{{ mapId }}'
	        });
	        
	        // If ElementorAppleMaps is already loaded, initialize right away
	        if (window.ElementorAppleMaps && window.ElementorAppleMaps.initMap) {
	            window.ElementorAppleMaps.initMap({
	                elementId: '{{ mapId }}'
	            });
	        }
	    })();
	    </script>
	    <?php
	}
}