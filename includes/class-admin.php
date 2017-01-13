<?php

Class RFMP_Admin {

    private $mollie, $wpdb;

    function __construct()
    {
        global $wpdb;

        add_action('init', array($this, 'init_plugin'));
        add_action('add_meta_boxes_rfmp', array($this, 'add_meta_boxes'));
        add_action('save_post_rfmp', array($this, 'save_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'load_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));

        add_filter('post_row_actions', array($this, 'post_actions'), 10, 2);

        $this->mollie   = new Mollie_API_Client;
        $this->wpdb     = $wpdb;
    }

    public function init_plugin()
    {
        remove_post_type_support('rfmp', 'editor');
    }

    public function admin_menu()
    {
        add_submenu_page(
            'edit.php?post_type=rfmp',
            __('Registrations', 'registration-form-with-mollie-payments'),
            __('Registrations', 'registration-form-with-mollie-payments'),
            'manage_options',
            'registrations',
            array(
                $this,
                'page_registrations'
            )
        );
        add_submenu_page(
            null,
            __('Registration', 'registration-form-with-mollie-payments'),
            __('Registration', 'registration-form-with-mollie-payments'),
            'manage_options',
            'registration',
            array(
                $this,
                'page_registration'
            )
        );
    }

    public function add_meta_boxes($post)
    {
        add_meta_box('rfmp_meta_box_fields', __('Fields', 'registration-form-with-mollie-payments'), array($this, 'build_meta_boxes_fields'), 'rfmp', 'normal', 'high');
        add_meta_box('rfmp_meta_box_settings', __('Settings', 'registration-form-with-mollie-payments'), array($this, 'build_meta_boxes_settings'), 'rfmp', 'normal', 'default');
        add_meta_box('rfmp_meta_box_priceoptions', __('Price options', 'registration-form-with-mollie-payments'), array($this, 'build_meta_boxes_priceoptions'), 'rfmp', 'normal', 'default');
        add_meta_box('rfmp_meta_box_paymentmethods', __('Payment methods', 'registration-form-with-mollie-payments'), array($this, 'build_meta_boxes_paymentmethods'), 'rfmp', 'side', 'default');
    }

    public function build_meta_boxes_fields($post)
    {
        wp_nonce_field(basename(__FILE__), 'rfmp_meta_box_fields_nonce');
        $field_type     = get_post_meta($post->ID, '_rfmp_fields_type', true);
        $field_label    = get_post_meta($post->ID, '_rfmp_fields_label', true);
        $field_value    = get_post_meta($post->ID, '_rfmp_fields_value', true);
        $field_class    = get_post_meta($post->ID, '_rfmp_fields_class', true);
        $field_required = get_post_meta($post->ID, '_rfmp_fields_required', true);

        if (empty($field_type))
        {
            $field_type = array(0 => 'name', 1 => 'email', 2 => 'priceoptions', 3 => 'payment_methods', 4 => 'submit');
            $field_label = array(0 => __('Name', 'registration-form-with-mollie-payments'), 1 => __('Email', 'registration-form-with-mollie-payments'), 2 => '', 3 => __('Payment method', 'registration-form-with-mollie-payments'), 4 => __('Submit', 'registration-form-with-mollie-payments'));
        }
        ?>
        <script id="rfmp_template_field" type="text/template">
            <tr>
                <td class="sort"></td>
                <td>
                    <select name="rfmp_fields_type[]" class="rfmp_type">
                        <option value="text"><?php esc_html_e('Text field', 'registration-form-with-mollie-payments');?></option>
                        <option value="textarea"><?php esc_html_e('Text area', 'registration-form-with-mollie-payments');?></option>
                        <option value="dropdown"><?php esc_html_e('Dropdown', 'registration-form-with-mollie-payments');?></option>
                        <option value="checkbox"><?php esc_html_e('Checkbox', 'registration-form-with-mollie-payments');?></option>
                    </select>
                </td>
                <td><input type="text" name="rfmp_fields_label[]" style="width:100%"></td>
                <td><input style="display:none;width:100%" class="rfmp_value" type="text" name="rfmp_fields_value[]" placeholder="value1|value2|value3"></td>
                <td><input type="text" name="rfmp_fields_class[]" style="width:100%"></td>
                <td><input type="hidden" name="rfmp_fields_required[]" value="0"><input type="checkbox" name="rfmp_fields_required[]" value="1"></td>
                <td width="1%"><a href="#" class="delete"><?php esc_html_e('Delete', 'registration-form-with-mollie-payments');?></a></td>
            </tr>
        </script>

        <div class='inside'>
            <table class="widefat rfmp_table" id="rfmp_fields">
                <thead>
                    <tr>
                        <th class="sort"></th>
                        <th><?php esc_html_e('Type', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Label', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Values', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Class', 'registration-form-with-mollie-payments');?></th>
                        <th width="50"><?php esc_html_e('Required', 'registration-form-with-mollie-payments');?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($field_type as $key => $type) { ?>
                        <?php if ($type == 'priceoptions') { ?>
                            <tr>
                                <td class="sort"></td>
                                <td><?php esc_html_e('Price options', 'registration-form-with-mollie-payments');?><input type="hidden" name="rfmp_fields_type[]" value="priceoptions"></td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_value[]" value=""></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="checkbox" name="rfmp_fields_required[]" value="1" disabled checked><input type="hidden" name="rfmp_fields_required[]" value="1"></td>
                                <td width="1%"></td>
                            </tr>
                        <?php } elseif ($type == 'submit') { ?>
                            <tr>
                                <td class="sort"></td>
                                <td><?php esc_html_e('Submit button', 'registration-form-with-mollie-payments');?><input type="hidden" name="rfmp_fields_type[]" value="submit"></td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_value[]" value=""></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="checkbox" name="rfmp_fields_required[]" value="1" disabled checked><input type="hidden" name="rfmp_fields_required[]" value="1"></td>
                                <td width="1%"></td>
                            </tr>
                        <?php } elseif ($type == 'payment_methods') { ?>
                            <tr>
                                <td class="sort"></td>
                                <td><?php esc_html_e('Payment methods', 'registration-form-with-mollie-payments');?><input type="hidden" name="rfmp_fields_type[]" value="payment_methods"></td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_value[]" value=""></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="checkbox" name="rfmp_fields_required[]" value="1" disabled checked><input type="hidden" name="rfmp_fields_required[]" value="1"></td>
                                <td width="1%"></td>
                            </tr>
                        <?php } elseif ($type == 'name') { ?>
                            <tr>
                                <td class="sort"></td>
                                <td><?php esc_html_e('Name', 'registration-form-with-mollie-payments');?><input type="hidden" name="rfmp_fields_type[]" value="name"></td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_value[]" value=""></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="checkbox" name="rfmp_fields_required[]" value="1" disabled checked><input type="hidden" name="rfmp_fields_required[]" value="1"></td>
                                <td width="1%"></td>
                            </tr>
                        <?php } elseif ($type == 'email') { ?>
                            <tr>
                                <td class="sort"></td>
                                <td><?php esc_html_e('Email address', 'registration-form-with-mollie-payments');?><input type="hidden" name="rfmp_fields_type[]" value="email"></td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_value[]" value=""></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="checkbox" name="rfmp_fields_required[]" value="1" disabled checked><input type="hidden" name="rfmp_fields_required[]" value="1"></td>
                                <td width="1%"></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td class="sort"></td>
                                <td>
                                    <select name="rfmp_fields_type[]" class="rfmp_type">
                                        <option value="text"><?php esc_html_e('Text field', 'registration-form-with-mollie-payments');?></option>
                                        <option value="textarea"<?php echo ($type == 'textarea' ? ' selected' : '');?>><?php esc_html_e('Text area', 'registration-form-with-mollie-payments');?></option>
                                        <option value="dropdown"<?php echo ($type == 'dropdown' ? ' selected' : '');?>><?php esc_html_e('Dropdown', 'registration-form-with-mollie-payments');?></option>
                                        <option value="checkbox"<?php echo ($type == 'checkbox' ? ' selected' : '');?>><?php esc_html_e('Checkbox', 'registration-form-with-mollie-payments');?></option>
                                    </select>
                                </td>
                                <td><input type="text" name="rfmp_fields_label[]" value="<?php echo esc_attr($field_label[$key]);?>" style="width:100%"></td>
                                <td><input style="<?php echo ($type != 'dropdown' ? 'display:none;' : '');?>width:100%;" class="rfmp_value" type="text" name="rfmp_fields_value[]" value="<?php echo esc_attr($field_value[$key]);?>" placeholder="value1|value2|value3"></td>
                                <td><input type="text" name="rfmp_fields_class[]" value="<?php echo esc_attr($field_class[$key]);?>" style="width:100%"></td>
                                <td><input type="hidden" name="rfmp_fields_required[]" value="0"><input type="checkbox" value="1" name="rfmp_fields_required[<?php echo $key;?>]"<?php echo (isset($field_required[$key]) && $field_required[$key] ? ' checked' : '');?>></td>
                                <td width="1%"><a href="#" class="delete"><?php esc_html_e('Delete', 'registration-form-with-mollie-payments');?></a></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="7"><input type="button" id="rfmp_add_field" class="button" value="<?php esc_html_e('Add new field', 'registration-form-with-mollie-payments');?>"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function build_meta_boxes_priceoptions($post)
    {
        wp_nonce_field(basename(__FILE__), 'rfmp_meta_box_priceoptions_nonce');
        $option_desc        = get_post_meta($post->ID, '_rfmp_priceoption_desc', true);
        $option_price       = get_post_meta($post->ID, '_rfmp_priceoption_price', true);
        $option_frequency   = get_post_meta($post->ID, '_rfmp_priceoption_frequency', true);
        $option_frequencyval= get_post_meta($post->ID, '_rfmp_priceoption_frequencyval', true);
        ?>
        <script id="rfmp_template_priceoption" type="text/template">
            <tr>
                <td class="sort"></td>
                <td><input type="text" name="rfmp_priceoptions_desc[]" style="width:100%;"></td>
                <td><input type="text" name="rfmp_priceoptions_price[]"></td>
                <td>
                    <input type="number" name="rfmp_priceoptions_frequencyval[]" style="width:50px;display:none;">
                    <select name="rfmp_priceoptions_frequency[]" class="rfmp_frequency">
                        <option value="once"><?php esc_html_e('Once', 'registration-form-with-mollie-payments');?></option>
                        <option value="months"><?php esc_html_e('Months', 'registration-form-with-mollie-payments');?></option>
                        <option value="weeks"><?php esc_html_e('Weeks', 'registration-form-with-mollie-payments');?></option>
                        <option value="days"><?php esc_html_e('Days', 'registration-form-with-mollie-payments');?></option>
                    </select>
                </td>
                <td width="1%"><a href="#" class="delete"><?php esc_html_e('Delete', 'registration-form-with-mollie-payments');?></a></td>
            </tr>
        </script>

        <div class='inside'>
            <table class="widefat rfmp_table" id="rfmp_priceoptions">
                <thead>
                    <tr>
                        <th class="sort"></th>
                        <th><?php esc_html_e('Description', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Price', 'registration-form-with-mollie-payments');?> &euro;</th>
                        <th><?php esc_html_e('Frequency', 'registration-form-with-mollie-payments');?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($option_desc as $key => $desc) { ?>
                        <tr>
                            <td class="sort"></td>
                            <td><input type="text" required style="width:100%;" name="rfmp_priceoptions_desc[]" value="<?php echo esc_attr($desc);?>"></td>
                            <td><input type="number" min="0" step="any" required name="rfmp_priceoptions_price[]" value="<?php echo esc_attr($option_price[$key]);?>"></td>
                            <td>
                                <input type="number" name="rfmp_priceoptions_frequencyval[]" value="<?php echo esc_attr($option_frequencyval[$key]);?>" style="width:50px;<?php echo ($option_frequency[$key] == 'once' ? 'display:none;' : '');?>">
                                <select name="rfmp_priceoptions_frequency[]" class="rfmp_frequency">
                                    <option value="once"><?php esc_html_e('Once', 'registration-form-with-mollie-payments');?></option>
                                    <option value="months"<?php echo ($option_frequency[$key] == 'months' ? ' selected' : '');?>><?php esc_html_e('Months', 'registration-form-with-mollie-payments');?></option>
                                    <option value="weeks"<?php echo ($option_frequency[$key] == 'weeks' ? ' selected' : '');?>><?php esc_html_e('Weeks', 'registration-form-with-mollie-payments');?></option>
                                    <option value="days"<?php echo ($option_frequency[$key] == 'days' ? ' selected' : '');?>><?php esc_html_e('Days', 'registration-form-with-mollie-payments');?></option>
                                </select>
                            </td>
                            <td width="1%"><a href="#" class="delete"><?php esc_html_e('Delete', 'registration-form-with-mollie-payments');?></a></td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5"><input type="button" id="rfmp_add_priceoption" class="button" value="<?php esc_html_e('Add new price option', 'registration-form-with-mollie-payments');?>"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function build_meta_boxes_settings($post)
    {
        wp_nonce_field(basename(__FILE__), 'rfmp_meta_box_settings_nonce');
        $api_key            = get_post_meta($post->ID, '_rfmp_api_key', true);
        $display_pm         = get_post_meta($post->ID, '_rfmp_payment_methods_display', true);
        $display_po         = get_post_meta($post->ID, '_rfmp_priceoptions_display', true);
        $class_success      = get_post_meta($post->ID, '_rfmp_class_success', true);
        $class_error        = get_post_meta($post->ID, '_rfmp_class_error', true);
        $message_success    = get_post_meta($post->ID, '_rfmp_msg_success', true);
        $message_error      = get_post_meta($post->ID, '_rfmp_msg_error', true);
        ?>
        <div class='inside'>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_shortcode"><?php esc_html_e('Shortcode', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input id="rfmp_shortcode" value='[rfmp id="<?php echo esc_attr($post->ID);?>"]' readonly type="text" style="width: 350px" onfocus="this.select();"><br>
                        <small><?php echo esc_html_e('Place this shortcode on a page or in a post', 'registration-form-with-mollie-payments');?></small>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_api_key"><?php esc_html_e('Mollie API-key', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="rfmp_api_key" id="rfmp_api_key" value="<?php echo esc_attr($api_key);?>" required type="text" style="width: 350px">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_priceoptions_display"><?php esc_html_e('Price options display', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <select name="rfmp_priceoptions_display" style="width: 350px;">
                            <option value="dropdown"><?php esc_html_e('Dropdown', 'registration-form-with-mollie-payments');?></option>
                            <option value="list"<?php echo ($display_po == 'list' ? ' selected' : '');?>><?php esc_html_e('List', 'registration-form-with-mollie-payments');?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_payment_methods_display"><?php esc_html_e('Payment methods display', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <select name="rfmp_payment_methods_display" style="width: 350px;">
                            <option value="dropdown"><?php esc_html_e('Dropdown', 'registration-form-with-mollie-payments');?></option>
                            <option value="list"<?php echo ($display_pm == 'list' ? ' selected' : '');?>><?php esc_html_e('List with icons and text', 'registration-form-with-mollie-payments');?></option>
                            <option value="text"<?php echo ($display_pm == 'text' ? ' selected' : '');?>><?php esc_html_e('List with text', 'registration-form-with-mollie-payments');?></option>
                            <option value="icons"<?php echo ($display_pm == 'icons' ? ' selected' : '');?>><?php esc_html_e('List with icons', 'registration-form-with-mollie-payments');?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_msg_success"><?php esc_html_e('Success message', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="rfmp_msg_success" id="rfmp_msg_success" value="<?php echo esc_attr($message_success);?>" required type="text" style="width: 350px">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_msg_error"><?php esc_html_e('Error message', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="rfmp_msg_error" id="rfmp_msg_error" value="<?php echo esc_attr($message_error);?>" required type="text" style="width: 350px">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_class_success"><?php esc_html_e('Class success message', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="rfmp_class_success" id="rfmp_class_success" value="<?php echo esc_attr($class_success);?>" type="text" style="width: 350px">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label for="rfmp_class_error"><?php esc_html_e('Class error message', 'registration-form-with-mollie-payments');?></label>
                    </th>
                    <td class="forminp forminp-text">
                        <input name="rfmp_class_error" id="rfmp_class_error" value="<?php echo esc_attr($class_error);?>" type="text" style="width: 350px">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function build_meta_boxes_paymentmethods($post)
    {
        wp_nonce_field(basename(__FILE__), 'rfmp_meta_box_paymentmethods_nonce');
        $api_key    = get_post_meta($post->ID, '_rfmp_api_key', true);
        $active     = get_post_meta($post->ID, '_rfmp_payment_method', true);
        $fixed      = get_post_meta($post->ID, '_rfmp_payment_method_fixed', true);
        $variable   = get_post_meta($post->ID, '_rfmp_payment_method_variable', true);

        try {

            if (!$api_key)
                echo '<p style="color: red">' . esc_html__('No API-key set', 'registration-form-with-mollie-payments') . '</p>';
            else
            {
                $this->mollie->setApiKey($api_key);

                foreach ($this->mollie->methods->all(0, 0, array('locale' => get_locale())) as $method)
                {
                    echo '<input type="hidden" value="0" name="rfmp_payment_method[' . $method->id . ']">';
                    echo '<label><input type="checkbox" name="rfmp_payment_method[' . $method->id . ']" ' . ($active[$method->id] ? 'checked' : '') . ' value="1"> <img style="vertical-align:middle;display:inline-block;width:25px;" src="' . esc_url($method->image->normal) . '"> ' . esc_html($method->description) . '</label><br>';
                    echo esc_html_e('Surcharge:', 'registration-form-with-mollie-payments') . ' &euro; <input type="number" step="any" min="0" name="rfmp_payment_method_fixed[' . $method->id . ']" value="' . esc_attr($fixed[$method->id]) . '" style="width: 50px;"> + <input type="number" step="any" min="0" name="rfmp_payment_method_variable[' . $method->id . ']" value="' . esc_attr($variable[$method->id]) . '" style="width: 50px;"> %<br><hr>';
                }
            }


        } catch (Mollie_API_Exception $e) {
            echo '<p style="color: red">' . $e->getMessage() . '</p>';
        }
    }

    public function save_meta_boxes($post_id)
    {
        // verify meta box nonce
        if (!isset($_POST['rfmp_meta_box_fields_nonce']) || !wp_verify_nonce($_POST['rfmp_meta_box_fields_nonce'], basename(__FILE__)))
            return;

        // verify meta box nonce
        if (!isset($_POST['rfmp_meta_box_priceoptions_nonce']) || !wp_verify_nonce($_POST['rfmp_meta_box_priceoptions_nonce'], basename(__FILE__)))
            return;

        // verify meta box nonce
        if (!isset($_POST['rfmp_meta_box_settings_nonce']) || !wp_verify_nonce($_POST['rfmp_meta_box_settings_nonce'], basename(__FILE__)))
            return;

        // verify meta box nonce
        if (!isset($_POST['rfmp_meta_box_paymentmethods_nonce']) || !wp_verify_nonce($_POST['rfmp_meta_box_paymentmethods_nonce'], basename(__FILE__)))
            return;

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id))
            return;

        // Store custom fields
        update_post_meta($post_id, '_rfmp_api_key', $_POST['rfmp_api_key']);
        update_post_meta($post_id, '_rfmp_payment_methods_display', $_POST['rfmp_payment_methods_display']);
        update_post_meta($post_id, '_rfmp_priceoptions_display', $_POST['rfmp_priceoptions_display']);
        update_post_meta($post_id, '_rfmp_class_success', $_POST['rfmp_class_success']);
        update_post_meta($post_id, '_rfmp_class_error', $_POST['rfmp_class_error']);
        update_post_meta($post_id, '_rfmp_msg_success', $_POST['rfmp_msg_success']);
        update_post_meta($post_id, '_rfmp_msg_error', $_POST['rfmp_msg_error']);

        update_post_meta($post_id, '_rfmp_fields_type', $_POST['rfmp_fields_type']);
        update_post_meta($post_id, '_rfmp_fields_label', $_POST['rfmp_fields_label']);
        update_post_meta($post_id, '_rfmp_fields_value', $_POST['rfmp_fields_value']);
        update_post_meta($post_id, '_rfmp_fields_class', $_POST['rfmp_fields_class']);
        update_post_meta($post_id, '_rfmp_fields_required', $_POST['rfmp_fields_required']);

        update_post_meta($post_id, '_rfmp_priceoption_desc', $_POST['rfmp_priceoptions_desc']);
        update_post_meta($post_id, '_rfmp_priceoption_price', $_POST['rfmp_priceoptions_price']);
        update_post_meta($post_id, '_rfmp_priceoption_frequency', $_POST['rfmp_priceoptions_frequency']);
        update_post_meta($post_id, '_rfmp_priceoption_frequencyval', $_POST['rfmp_priceoptions_frequencyval']);

        update_post_meta($post_id, '_rfmp_payment_method', $_POST['rfmp_payment_method']);
        update_post_meta($post_id, '_rfmp_payment_method_fixed', $_POST['rfmp_payment_method_fixed']);
        update_post_meta($post_id, '_rfmp_payment_method_variable', $_POST['rfmp_payment_method_variable']);
    }

    public function load_scripts()
    {
        wp_enqueue_script('rfmp_admin_scripts', plugin_dir_url(__FILE__) . 'js/admin-scripts.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable'));
        wp_enqueue_style('rfmp_admin_styles', plugin_dir_url(__FILE__) . 'css/admin-styles.css');
    }

    public function post_actions($actions, $post)
    {
        if ($post->post_type=='rfmp')
        {
            unset($actions['inline hide-if-no-js']);
            unset($actions['view']);
            $actions['registrations'] = '<a href="edit.php?post_type=rfmp&page=registrations&post=' . $post->ID . '">' . __('Registrations', 'registration-form-with-mollie-payments') . '</a>';
        }
        return $actions;
    }

    public function page_registrations()
    {
        $table = new RFMP_Registrations_Table();
        $table->prepare_items();

        if (isset($_GET['post']))
            $post = get_post($_GET['post']);

        if (isset($_GET['msg']))
        {
            switch ($_GET['msg'])
            {
                case 'delete-ok':
                    $rfmp_msg = '<div class="updated notice"><p>' . esc_html__('The registration is successful deleted', 'registration-form-with-mollie-payments') . '</p></div>';
                    break;
            }

            echo isset($rfmp_msg) ? $rfmp_msg : '';
        }
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Registrations', 'registration-form-with-mollie-payments'); echo (isset($post) ? ' <small>(' . $post->post_title . ')</small>' : '');?></h2>

            <?php $table->display();?>
        </div>
        <?php
    }

    public function page_registration()
    {
        if (!isset($_GET['view']))
            return esc_html__('Registration not found', 'registration-form-with-mollie-payments');

        $id = (int) $_GET['view'];

        $registration   = $this->wpdb->get_row("SELECT * FROM " . RFMP_TABLE_REGISTRATIONS . " WHERE id=" . $id);
        if ($registration == null)
            return esc_html__('Registration not found', 'registration-form-with-mollie-payments');

        // Delete registration
        if (isset($_GET['delete']) && check_admin_referer('delete-reg_' . $_GET['view']))
        {
            $this->wpdb->query($this->wpdb->prepare("DELETE FROM " . RFMP_TABLE_REGISTRATIONS . " WHERE id = %s",
                $id
            ));

            wp_redirect('?post_type=rfmp&page=registrations&msg=delete-ok');
            exit;
        }

        $fields         = $this->wpdb->get_results("SELECT * FROM " . RFMP_TABLE_REGISTRATION_FIELDS . " WHERE registration_id=" . $id);
        $subscriptions  = $this->wpdb->get_results("SELECT * FROM " . RFMP_TABLE_SUBSCRIPTIONS . " WHERE registration_id=" . $id);
        $payments       = $this->wpdb->get_results("SELECT * FROM " . RFMP_TABLE_PAYMENTS . " WHERE registration_id=" . $id);

        $api_key        = get_post_meta($registration->post_id, '_rfmp_api_key', true);

        // Connect with Mollie
        $mollie = new Mollie_API_Client;
        $mollie->setApiKey($api_key);

        // Cancel subscription
        if (isset($_GET['cancel']) && check_admin_referer('cancel-sub_' . $_GET['cancel']))
        {
            try {
                $cancelledSub   = $mollie->customers_subscriptions->withParentId($registration->customer_id)->cancel($_GET['cancel']);

                $this->wpdb->query($this->wpdb->prepare("UPDATE " . RFMP_TABLE_SUBSCRIPTIONS . " SET sub_status = %s WHERE subscription_id = %s",
                    $cancelledSub->status,
                    $cancelledSub->id
                ));

                wp_redirect('?post_type=' . $_REQUEST['post_type'] . '&page=' . $_REQUEST['page'] . '&view=' . $_REQUEST['view'] . '&msg=cancel-ok');
            } catch(Mollie_API_Exception $e) {
                echo '<div class="error notice">' . $e->getMessage() . '</div>';
            }
        }

        // Refund payment
        if (isset($_GET['refund']) && check_admin_referer('refund-payment_' . $_GET['refund']))
        {
            try {
                $payment = $mollie->payments->get($_GET['refund']);
                if ($payment->canBeRefunded())
                {
                    $refund = $mollie->payments->refund($payment);

                    $this->wpdb->query($this->wpdb->prepare("UPDATE " . RFMP_TABLE_PAYMENTS . " SET payment_status = %s WHERE payment_id = %s",
                        $payment->status,
                        $payment->id
                    ));

                    wp_redirect('?post_type=' . $_REQUEST['post_type'] . '&page=' . $_REQUEST['page'] . '&view=' . $_REQUEST['view'] . '&msg=refund-ok');
                }
                else
                    wp_redirect('?post_type=' . $_REQUEST['post_type'] . '&page=' . $_REQUEST['page'] . '&view=' . $_REQUEST['view'] . '&msg=refund-nok');
            } catch(Mollie_API_Exception $e) {
                echo '<div class="error notice">' . $e->getMessage() . '</div>';
            }
        }

        if (isset($_GET['msg']))
        {
            switch ($_GET['msg'])
            {
                case 'refund-ok':
                    $rfmp_msg = '<div class="updated notice"><p>' . esc_html__('The payment is successful refunded', 'registration-form-with-mollie-payments') . '</p></div>';
                    break;
                case 'refund-nok':
                    $rfmp_msg = '<div class="error notice"><p>' . esc_html__('The payment can not be refunded', 'registration-form-with-mollie-payments') . '</p></div>';
                    break;
                case 'cancel-ok':
                    $rfmp_msg = '<div class="updated notice"><p>' . esc_html__('The subscription is successful cancelled', 'registration-form-with-mollie-payments') . '</p></div>';
                    break;
            }

            echo isset($rfmp_msg) ? $rfmp_msg : '';
        }
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Registration', 'registration-form-with-mollie-payments');?></h2>

            <table class="wp-list-table widefat fixed striped rfmp_page_registration">
                <tbody id="the-list">
                    <?php foreach ($fields as $row) { ?>
                        <tr>
                            <td class="field column-field column-primary"><strong><?php echo esc_html($row->field);?></strong></td>
                            <td class="value column-value"><?php echo nl2br(esc_html($row->value));?></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td class="field column-field column-primary"><strong><?php echo esc_html_e('Total price', 'registration-form-with-mollie-payments');?></strong></td>
                        <td class="value column-value"><?php echo '&euro; ' . number_format($registration->total_price, 2, ',', '');?></td>
                    </tr>
                    <tr>
                        <td class="field column-field column-primary"><strong><?php echo esc_html_e('Mollie Customer ID', 'registration-form-with-mollie-payments');?></strong></td>
                        <td class="value column-value"><?php echo esc_html($registration->customer_id);?></td>
                    </tr>
                </tbody>
            </table><br>

            <?php if ($registration->price_frequency != 'once' && $subscriptions != null) { ?>
                <h3><?php esc_html_e('Subscriptions', 'registration-form-with-mollie-payments');?></h3>
                <table class="wp-list-table widefat fixed striped rfmp_page_registration_subscriptions">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Subscription ID', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Created at', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription mode', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription amount', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription method', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription interval', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription description', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Subscription status', 'registration-form-with-mollie-payments');?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="the-list">
                    <?php
                    foreach ($subscriptions as $subscription) {
                        $url_cancel = wp_nonce_url('?post_type=rfmp&page=registration&view=' . $id . '&cancel=' . $subscription->subscription_id, 'cancel-sub_' . $subscription->subscription_id);
                        ?>
                        <tr>
                            <td class="column-subscription_id"><?php echo esc_html($subscription->subscription_id);?></td>
                            <td class="column-created_at"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($subscription->created_at)));?></td>
                            <td class="column-sub_mode"><?php echo esc_html($subscription->sub_mode);?></td>
                            <td class="column-sub_amount">&euro;<?php echo esc_html(number_format($subscription->sub_amount, 2, ',', ''));?></td>
                            <td class="column-sub_method"><?php echo esc_html($subscription->sub_method);?></td>
                            <td class="column-sub_interval"><?php echo esc_html($this->frequency_label($subscription->sub_interval));?></td>
                            <td class="column-sub_description"><?php echo esc_html($subscription->sub_description);?></td>
                            <td class="column-sub_status"><?php echo esc_html($subscription->sub_status);?></td>
                            <td class="column-cancel"><?php if ($subscription->sub_status == 'active') { ?><a href="<?php echo $url_cancel;?>" style="color:#a00;"><?php echo esc_html_e('Cancel', 'registration-form-with-mollie-payments');?></a><?php } ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table><br>
            <?php } ?>

            <h3><?php esc_html_e('Payments', 'registration-form-with-mollie-payments');?></h3>
            <table class="wp-list-table widefat fixed striped rfmp_page_registration_payments">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Payment ID', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Created at', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Payment method', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Payment mode', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Payment status', 'registration-form-with-mollie-payments');?></th>
                        <th><?php esc_html_e('Amount', 'registration-form-with-mollie-payments');?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                <?php
                foreach ($payments as $payment) {
                    $url_refund = wp_nonce_url('?post_type=rfmp&page=registration&view=' . $id . '&refund=' . $payment->payment_id, 'refund-payment_' . $payment->payment_id);
                    ?>
                    <tr>
                        <td class="column-rfmp_id"><?php echo esc_html($payment->rfmp_id);?></td>
                        <td class="column-payment_id"><?php echo esc_html($payment->payment_id);?></td>
                        <td class="column-created_at"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment->created_at)));?></td>
                        <td class="column-payment_method"><?php echo esc_html($payment->payment_method);?></td>
                        <td class="column-payment_mode"><?php echo esc_html($payment->payment_mode);?></td>
                        <td class="column-payment_status"><?php echo esc_html($payment->payment_status);?></td>
                        <td class="column-amount"><?php echo '&euro; ' . number_format($payment->amount, 2, ',', '');?></td>
                        <td class="column-cancel"><?php if ($payment->payment_status == 'paid') { ?><a href="<?php echo $url_refund;?>" style="color:#a00;"><?php echo esc_html_e('Refund', 'registration-form-with-mollie-payments');?></a><?php } ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table><br>
        </div>
        <?php
    }

    private function frequency_label($frequency)
    {
        $frequency = trim($frequency);
        switch ($frequency)
        {
            case 'once':
                $return = __('Once', 'registration-form-with-mollie-payments');
                break;
            case '1 months':
                $return = __('per month', 'registration-form-with-mollie-payments');
                break;
            case '1 month':
                $return = __('per month', 'registration-form-with-mollie-payments');
                break;
            case '3 months':
                $return = __('each quarter', 'registration-form-with-mollie-payments');
                break;
            case '12 months':
                $return = __('per year', 'registration-form-with-mollie-payments');
                break;
            case '1 weeks':
                $return = __('per week', 'registration-form-with-mollie-payments');
                break;
            case '1 week':
                $return = __('per week', 'registration-form-with-mollie-payments');
                break;
            case '1 days':
                $return = __('per day', 'registration-form-with-mollie-payments');
                break;
            case '1 day':
                $return = __('per day', 'registration-form-with-mollie-payments');
                break;
            default:
                $return = __('each', 'registration-form-with-mollie-payments') . ' ' . $frequency;
        }

        return $return;
    }
}