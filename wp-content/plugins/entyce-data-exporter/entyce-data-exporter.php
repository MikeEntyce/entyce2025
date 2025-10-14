<?php
/*
Plugin Name: Entyce Data Exporter
Description: Export data from specific tables within a date range to CSV.
Version: 1.1
Author: Entyce-Creative
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Entyce Data Exporter',
        'Entyce Export',
        'manage_options',
        'entyce-data-exporter',
        'cde_render_admin_page',
        'dashicons-download',
        26
    );
});

add_action('admin_post_cde_export_csv', 'cde_handle_export');

function cde_render_admin_page() {
    // Handle previous values for form repopulation
    $selected_table = isset($_GET['table']) ? sanitize_text_field($_GET['table']) : '';
    $start_date     = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date       = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $error          = isset($_GET['error']) ? (int) $_GET['error'] : 0;
    ?>

    <div class="wrap">
        <h1>Entyce Data Exporter</h1>

        <?php if ($error): ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>No data found</strong> for the selected table and date range. Please try again.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="cde_export_csv">
            <?php wp_nonce_field('cde_export_csv_nonce', 'cde_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="table">Select Table</label></th>
                    <td>
                        <select name="table" id="table" required>
                            <option value="">-- Select Table --</option>
                            <?php
                            $tables = [
                                'al_clicky_callback' => 'Callback',
                                'al_clicky_enquiries' => 'Enquiries',
                                'al_clicky_will_builder' => 'Will_builder',
                                'al_conveyancing' => 'Conveyancing',
                            ];
                            foreach ($tables as $value => $label) {
                                $selected = ($selected_table === $value) ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$label</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_date">Start Date</label></th>
                    <td><input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="end_date">End Date</label></th>
                    <td><input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" required></td>
                </tr>
            </table>
            <?php submit_button('Export CSV'); ?>
        </form>
    </div>
    <?php
}

function cde_handle_export() {
    if (!current_user_can('manage_options') || !check_admin_referer('cde_export_csv_nonce', 'cde_nonce')) {
        wp_die('Unauthorized request');
    }

    global $wpdb;

    $table = sanitize_text_field($_POST['table']);
    $start = sanitize_text_field($_POST['start_date']);
    $end = sanitize_text_field($_POST['end_date']);

    $allowed_tables = ['al_clicky_callback', 'al_clicky_enquiries', 'al_clicky_will_builder', 'al_conveyancing'];

    if (!in_array($table, $allowed_tables)) {
        wp_die('Invalid table selected');
    }

    $start_dt = $start . ' 00:00:00';
    $end_dt   = $end . ' 23:59:59';

    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE created_at BETWEEN %s AND %s",
        $start_dt, $end_dt
    );
    $results = $wpdb->get_results($query, ARRAY_A);

    if (empty($results)) {
        // Redirect back with error flag and preserve inputs
        $redirect_url = admin_url('admin.php?page=entyce-data-exporter&error=1');
        $redirect_url = add_query_arg([
            'table' => urlencode($table),
            'start_date' => urlencode($start),
            'end_date' => urlencode($end),
        ], $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }

    // Prepare CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $table . '_export_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array_keys($results[0]));

    foreach ($results as $row) {
        $clean_row = [];

        foreach ($row as $value) {
            $value = (string) $value;
            $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
            $value = str_replace('"', '""', $value);
            $value = '"' . $value . '"';
            $clean_row[] = $value;
        }

        fwrite($output, implode(',', $clean_row) . "\n");
    }

    fclose($output);
    exit;
}
