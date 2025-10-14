<?php
class Secondary_Button_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'secondary_button';
    }

    public function get_title() {
        return __( 'Secondary Button', 'entyce_base_theme' );
    }

    public function get_icon() {
        return 'eicon-button';
    }

    public function get_categories() {
        return [ 'basic' ];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'section_button',
            [
                'label' => __( 'Button', 'entyce_base_theme' ),
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __( 'Background color', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'global' => [
                    'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_SECONDARY,  // Optional, set a default global color
                ],
                'selectors' => [
                    '{{WRAPPER}} .entyce-button-1' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        // Add a text color control (supports global colors)
        $this->add_control(
            'text_color',
            [
                'label' => __( 'Text Color', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'global' => [
                    'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Colors::COLOR_SECONDARY,  // Optional, set a default global color
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_size',
            [
                'label' => __( 'Size', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'medium' => __( 'Medium', 'entyce_base_theme' ),
                    'big' => __( 'Big', 'entyce_base_theme' ),
                    'small' => __( 'Small', 'entyce_base_theme' ),
                ],
            ]
        );

        $this->add_control(
            'button_style',
            [
                'label' => __( 'Style', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'filled',
                'options' => [
                    'filled' => __( 'Filled', 'entyce_base_theme' ),
                    'outlined' => __( 'Outlined ', 'entyce_base_theme' ),
                    'text' => __( 'Text', 'entyce_base_theme' ),
                ],
            ]
        );

        $this->add_control(
            'button_corners',
            [
                'label' => __( 'Corners', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => __( 'Site default', 'entyce_base_theme' ),
                    'square' => __( 'Square', 'entyce_base_theme' ),
                    '5px' => __( '5px', 'entyce_base_theme' ),
                    '10px' => __( '10px ', 'entyce_base_theme' ),
                    'round' => __( 'Round', 'entyce_base_theme' ),
                ],
            ]
        );

        $this->add_control(
            'button_icon',
            [
                'label' => __( 'Icon', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'description' => __( 'Edit global icons in Site Settings -> Layout -> Global Button Icons' , 'entyce_base_theme' ),
                'default' => 'icon_1',
                'options' => [
                    'icon_1' => __( 'Style 1', 'entyce_base_theme' ),
                    'icon_2' => __( 'Style 2', 'entyce_base_theme' ),
                    'icon_3' => __( 'Style 3', 'entyce_base_theme' ),
                    'icon_4' => __( 'Style 4', 'entyce_base_theme' ),
                    'icon_5' => __( 'Style 5', 'entyce_base_theme' ),
                    'no_icon' => __( 'No icon', 'entyce_base_theme' ),
                ],
            ]
        );

        $this->add_control(
            'text',
            [
                'label' => __( 'Text', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Click here', 'entyce_base_theme' ),
                'placeholder' => __( 'Click here', 'entyce_base_theme' ),
            ]
        );

        $this->add_control(
            'class',
            [
                'label' => __( 'Class', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( '', 'entyce_base_theme' ),
                'placeholder' => __( 'Class name', 'entyce_base_theme' ),
            ]
        );

        $this->add_control(
            'link',
            [
                'label' => __( 'Link', 'entyce_base_theme' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://your-link.com', 'entyce_base_theme' ),
                'default' => [
                    'url' => '#',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $this->add_render_attribute( 'wrapper', 'class', 'elementor-button-wrapper' );

        $this->add_render_attribute( 'button', 'class', 
        'elementor-button-link elementor-button entyce-button-1 button-' . $settings['button_size'] . 
        ' button-' . $settings['button_style'] . 
        ' button-radius-' . $settings['button_corners'] . 
        ' button-' . $settings['button_icon']
        );

        if ( ! empty( $settings['link']['url'] ) ) {
            $this->add_link_attributes( 'button', $settings['link'] );
        }

        $color_value = !empty( $settings['background_color'] ) ? $settings['background_color'] : 'var(--e-global-color-secondary)';

        ?>
        <div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
            <a style="background-color:<?php echo esc_attr( $color_value ); ?>; border-color:<?php echo esc_attr( $color_value ); ?>" <?php echo $this->get_render_attribute_string( 'button' ); ?>>
                <span class="elementor-button-content-wrapper">
                    <span class="elementor-button-text <?php echo $settings['class']; ?>"><?php echo $settings['text']; ?></span>
                </span>
            </a>
        </div>
        <?php
    }
}