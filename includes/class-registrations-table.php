<?php

class RFMP_Registrations_Table extends WP_List_Table {

    function get_columns()
    {
        $columns = array();
        $columns['created_at'] = __('Date/time', RFMP_TXT_DOMAIN);
        $columns['post_id'] = __('Form', RFMP_TXT_DOMAIN);
        $columns['customer'] = __('Customer', RFMP_TXT_DOMAIN);
        $columns['total_price'] = __('Total price', RFMP_TXT_DOMAIN);
        $columns['price_frequency'] = __('Frequency', RFMP_TXT_DOMAIN);
        $columns['description'] = __('Description', RFMP_TXT_DOMAIN);
        $columns['actions'] = '';

        return $columns;
    }

    function column_actions($item)
    {
        $url_view   = 'edit.php?post_type=rfmp&page=registration&view=' . $item['id'];
        $url_delete = wp_nonce_url('edit.php?post_type=rfmp&page=registration&view=' . $item['id'] . '&delete=true', 'delete-reg_' . $item['id']);
        return sprintf('<a href="%s">' . esc_html__('View', RFMP_TXT_DOMAIN) . '</a> <a href="%s" style="color:#a00;" onclick="return confirm(\'' . esc_html__('Are you sure?', RFMP_TXT_DOMAIN) . '\');">' . esc_html__('Delete', RFMP_TXT_DOMAIN) . '</a>', $url_view, $url_delete);
    }

    function prepare_items()
    {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $where = '';
        if (isset($_GET['post']))
            $where .= ' WHERE post_id="' . esc_sql($_GET['post']) . '"';

        $registrations = $wpdb->get_results("SELECT * FROM " . RFMP_TABLE_REGISTRATIONS . $where . " ORDER BY id DESC", ARRAY_A);

        $per_page = 25;
        $current_page = $this->get_pagenum();
        $total_items = count($registrations);

        $d = array_slice($registrations,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
        $this->items = $d;
    }


    function column_default($item, $column_name)
    {
        global $wpdb;
        switch($column_name) {
            case 'customer':
                $name = $wpdb->get_row("SELECT value FROM " . RFMP_TABLE_REGISTRATION_FIELDS . " WHERE type='name' AND registration_id=" . $item['id']);
                return $name->value;
                break;
            case 'total_price':
                return '&euro; ' . number_format($item[$column_name], 2, ',', '');
                break;
            case 'post_id':
                $post = get_post($item[$column_name]);
                return $post->post_title;
                break;
            case 'created_at':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[$column_name]));
                break;
            case 'price_frequency':
                return $this->frequency_label($item[$column_name]);
                break;
            default:
                return $item[$column_name];
        }
    }

    public function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <?php $this->pagination( $which );?>
            <br class="clear" />
        </div>
        <?php
    }

    private function frequency_label($frequency)
    {
        $frequency = trim($frequency);
        switch ($frequency)
        {
            case 'once':
                $return = '';
                break;
            case '1 months':
                $return = __('per month', RFMP_TXT_DOMAIN);
                break;
            case '1 month':
                $return = __('per month', RFMP_TXT_DOMAIN);
                break;
            case '3 months':
                $return = __('each quarter', RFMP_TXT_DOMAIN);
                break;
            case '12 months':
                $return = __('per year', RFMP_TXT_DOMAIN);
                break;
            case '1 weeks':
                $return = __('per week', RFMP_TXT_DOMAIN);
                break;
            case '1 week':
                $return = __('per week', RFMP_TXT_DOMAIN);
                break;
            case '1 days':
                $return = __('per day', RFMP_TXT_DOMAIN);
                break;
            case '1 day':
                $return = __('per day', RFMP_TXT_DOMAIN);
                break;
            default:
                $return = __('each', RFMP_TXT_DOMAIN) . ' ' . $frequency;
        }

        return $return;
    }
}