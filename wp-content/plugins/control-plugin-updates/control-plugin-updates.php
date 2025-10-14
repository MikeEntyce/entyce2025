<?php
/**
 * Plugin Name: Control Plugin Updates
 * Description: Disable updates for selected plugins.
 * Version: 1.1.0
 * Author: Entyce-Creative
 */

// Admin menu
add_action('admin_menu', function() {
    add_plugins_page(
        'Control Plugin Updates',
        'Control Plugin Updates',
        'manage_options',
        'control-plugin-updates',
        'control_plugin_updates_page'
    );
});

// Settings page
function control_plugin_updates_page() {
    ?>
    <div class="wrap">
        <h1>Control Plugin Updates</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('control_plugin_updates_settings');
            do_settings_sections('control-plugin-updates');
            submit_button('Save Disabled Plugins');
            ?>
        </form>
    </div>
    <?php
}

// Settings registration
add_action('admin_init', function() {
    register_setting(
        'control_plugin_updates_settings',
        'control_plugin_updates',
        'control_plugin_updates_validate'
    );

    add_settings_section(
        'control_plugin_updates_section',
        'Disable Updates for Plugins',
        'control_plugin_updates_section_desc',
        'control-plugin-updates'
    );

    add_settings_field(
        'control_plugin_updates_field',
        'Plugins',
        'control_plugin_updates_field',
        'control-plugin-updates',
        'control_plugin_updates_section'
    );
});

// Description
function control_plugin_updates_section_desc() {
    echo '<p>Tick the plugins you want to disable updates for.</p>';
}

// Validation
function control_plugin_updates_validate($input) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $valid_input = [];
    $all_plugins = array_keys(get_plugins());

    foreach ($all_plugins as $plugin_slug) {
        $valid_input[$plugin_slug] = isset($input[$plugin_slug]) ? 1 : 0;
    }

    return $valid_input;
}

// Field output
function control_plugin_updates_field() {
    $options = get_option('control_plugin_updates', []);
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = get_plugins();
    $default_disabled = [
        'advanced-custom-fields-pro/acf.php',
        'duplicator-pro/duplicator-pro.php',
        'elementor/elementor.php',
        'elementor-pro/elementor-pro.php',
        'gravityforms/gravityforms.php',
        'search-filter-pro/search-filter-pro.php',
        'wp-rocket/wp-rocket.php'
    ];

    foreach ($all_plugins as $plugin_slug => $plugin_data) {
        $is_disabled = isset($options[$plugin_slug])
            ? $options[$plugin_slug]
            : (in_array($plugin_slug, $default_disabled) ? 1 : 0);

        $checked = checked($is_disabled, 1, false);
        echo "<label><input type='checkbox' name='control_plugin_updates[{$plugin_slug}]' value='1' {$checked} /> {$plugin_data['Name']}<br /></label>";
    }
}

// Disable updates
add_filter('site_transient_update_plugins', function($value) {
    $options = get_option('control_plugin_updates', []);
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $all_plugins = array_keys(get_plugins());

    if (isset($value->response) && is_array($value->response)) {
        foreach ($all_plugins as $plugin) {
            if (isset($options[$plugin]) && $options[$plugin] == 1) {
                unset($value->response[$plugin]);
            }
        }
    }

    return $value;
});
