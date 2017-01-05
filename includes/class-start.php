<?php

Class RFMP_Start {

    private $wpdb, $mollie, $required_errors;

    function __construct()
    {
        global $wpdb;

        add_action('init', array($this, 'add_registration_form_type'), 0);
        add_shortcode('rfmp', array($this, 'add_rfmp_shortcode'));

        $this->wpdb     = $wpdb;
        $this->mollie   = new Mollie_API_Client;
    }

    public function add_registration_form_type()
    {
        $labels = array(
            'name'                  => _x('Mollie Forms', 'Registration Forms General Name', RFMP_TXT_DOMAIN),
            'singular_name'         => _x('Mollie Form', 'Registration Form Singular Name', RFMP_TXT_DOMAIN),
            'menu_name'             => __('Mollie Forms', RFMP_TXT_DOMAIN),
            'name_admin_bar'        => __('Registration Form', RFMP_TXT_DOMAIN),
            'archives'              => __('Item Archives', RFMP_TXT_DOMAIN),
            'parent_item_colon'     => __('Parent Item:', RFMP_TXT_DOMAIN),
            'all_items'             => __('All Forms', RFMP_TXT_DOMAIN),
            'add_new_item'          => __('Add New Form', RFMP_TXT_DOMAIN),
            'add_new'               => __('Add New', RFMP_TXT_DOMAIN),
            'new_item'              => __('New Form', RFMP_TXT_DOMAIN),
            'edit_item'             => __('Edit Form', RFMP_TXT_DOMAIN),
            'update_item'           => __('Update Form', RFMP_TXT_DOMAIN),
            'view_item'             => __('View Form', RFMP_TXT_DOMAIN),
            'search_items'          => __('Search Form', RFMP_TXT_DOMAIN),
            'not_found'             => __('Not found', RFMP_TXT_DOMAIN),
            'not_found_in_trash'    => __('Not found in Trash', RFMP_TXT_DOMAIN),
            'featured_image'        => __('Featured Image', RFMP_TXT_DOMAIN),
            'set_featured_image'    => __('Set featured image', RFMP_TXT_DOMAIN),
            'remove_featured_image' => __('Remove featured image', RFMP_TXT_DOMAIN),
            'use_featured_image'    => __('Use as featured image', RFMP_TXT_DOMAIN),
            'insert_into_item'      => __('Insert into form', RFMP_TXT_DOMAIN),
            'uploaded_to_this_item' => __('Uploaded to this form', RFMP_TXT_DOMAIN),
            'items_list'            => __('Forms list', RFMP_TXT_DOMAIN),
            'items_list_navigation' => __('Forms list navigation', RFMP_TXT_DOMAIN),
            'filter_items_list'     => __('Filter forms list', RFMP_TXT_DOMAIN),
        );
        $args = array(
            'label'                 => __('Registration Form', RFMP_TXT_DOMAIN),
            'description'           => __('Registration Form Description', RFMP_TXT_DOMAIN),
            'labels'                => $labels,
            'supports'              => array(),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'rewrite'               => false,
            'menu_icon'             => 'dashicons-list-view',
        );
        register_post_type('rfmp', $args);
    }

    public function add_rfmp_shortcode($atts)
    {
        $output = '<form method="post">';
        $atts = shortcode_atts(array(
            'id' => ''
        ), $atts);

        $post = get_post($atts['id']);

        if (!$post->ID)
            return __('Form not found', RFMP_TXT_DOMAIN);

        $fields_type = get_post_meta($post->ID, '_rfmp_fields_type', true);

        // POST request and check required fields
        if ($this->check_required($post->ID) && $_SERVER['REQUEST_METHOD'] == 'POST')
            $this->do_post($post->ID);

        // Message after payment
        if (isset($_GET['payment']))
        {
            $class_success      = get_post_meta($post->ID, '_rfmp_class_success', true);
            $class_error        = get_post_meta($post->ID, '_rfmp_class_error', true);
            $message_success    = get_post_meta($post->ID, '_rfmp_msg_success', true);
            $message_error      = get_post_meta($post->ID, '_rfmp_msg_error', true);

            $payment = $this->wpdb->get_row("SELECT * FROM " . RFMP_TABLE_PAYMENTS . " WHERE rfmp_id='" . esc_sql($_GET['payment']) . "'");
            if ($payment == null)
                return '<p class="' . esc_attr($class_error) . '">' . esc_html__('No payment found', RFMP_TXT_DOMAIN) . '</p>';
            elseif ($payment->payment_status == 'paid')
                return '<p class="' . esc_attr($class_success) . '">' . esc_html($message_success) . '</p>';
            else
                $output .= '<p class="' . esc_attr($class_error) . '">' . esc_html($message_error) . '</p>';
        }

        // Display form errors
        $output .= $this->required_errors;

        // Form fields
        foreach ($fields_type as $key => $type)
        {
            $output .= '<p>';
            $output .= $this->field_form($post->ID, $key, $type);
            $output .= '</p>';
        }

        $output .= '</form>';

        return $output;
    }

    private function field_form($post, $key, $type)
    {
        $fields_label = get_post_meta($post, '_rfmp_fields_label', true);
        $fields_value = get_post_meta($post, '_rfmp_fields_value', true);
        $fields_class = get_post_meta($post, '_rfmp_fields_class', true);
        $fields_required = get_post_meta($post, '_rfmp_fields_required', true);

        $required = ($fields_required[$key] ? ' <span style="color:red;">*</span>' : '');

        $name = 'form_' . $post . '_field_' . $key;
        $form_value = isset($_POST[$name]) ? $_POST[$name] : '';
        switch ($type)
        {
            case 'text':
                $return = '<label>' . esc_html($fields_label[$key]) . $required . '<br><input type="text" name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '" ' . ($fields_required[$key] ? 'required' : '') . ' value="' . esc_attr($form_value) . '" style="width: 100%;"></label>';
                break;
            case 'textarea':
                $return = '<label>' . esc_html($fields_label[$key]) . $required . '<br><textarea name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '" ' . ($fields_required[$key] ? 'required' : '') . ' style="width: 100%;">' . esc_html($form_value) . '</textarea></label>';
                break;
            case 'name':
                $return = '<label>' . esc_html($fields_label[$key]) . $required . '<br><input type="text" name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '" ' . ($fields_required[$key] ? 'required' : '') . ' value="' . esc_attr($form_value) . '" style="width: 100%;"></label>';
                break;
            case 'email':
                $return = '<label>' . esc_html($fields_label[$key]) . $required . '<br><input type="email" name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '" ' . ($fields_required[$key] ? 'required' : '') . ' value="' . esc_attr($form_value) . '" style="width: 100%;"></label>';
                break;
            case 'checkbox':
                $return = '<label>' . esc_html($fields_label[$key]) . $required . ' <input type="checkbox" name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '" value="1" ' . ($fields_required[$key] ? 'required' : '') . ($form_value == '1' ? ' checked' : '') . '></label>';
                break;
            case 'dropdown':
                $values = explode('|', $fields_value[$key]);
                $options = '';
                foreach ($values as $value)
                    $options .= '<option' . ($form_value == $value ? ' selected' : '') . '>' . esc_html($value) . '</option>';

                $return = '<label>' . esc_html($fields_label[$key]) . $required . '<br><select name="' . $name . '" class="' . esc_attr($fields_class[$key]) . '">' . $options . '</select></label>';
                break;
            case 'submit':
                $return = '<input type="submit" name="' . $name . '" value="' . esc_attr($fields_label[$key]) . '" class="' . esc_attr($fields_class[$key]) . '">';
                break;
            case 'payment_methods':
                $return = '<label>' . esc_html($fields_label[$key]) . '<br>' . $this->payment_methods($post, $fields_class[$key]) . '</label>';
                break;
            case 'priceoptions':
                $return = '<label>' . esc_html($fields_label[$key]) . '<br>' . $this->price_options($post, $fields_class[$key]) . '</label>';
                break;
        }

        return $return;
    }

    public function payment_methods($post, $class)
    {
        $api_key    = get_post_meta($post, '_rfmp_api_key', true);
        $active     = get_post_meta($post, '_rfmp_payment_method', true);
        $fixed      = get_post_meta($post, '_rfmp_payment_method_fixed', true);
        $variable   = get_post_meta($post, '_rfmp_payment_method_variable', true);
        $display    = get_post_meta($post, '_rfmp_payment_methods_display', true);
        $form_value = isset($_POST['rfmp_payment_method']) ? $_POST['rfmp_payment_method'] : '';

        try {
            $this->mollie->setApiKey($api_key);

            $script = '';
            $rcur = array();
            foreach ($this->mollie->methods->all(0,0,array('recurringType' => 'first')) as $method)
            {
                if ($active[$method->id])
                {
                    $rcur[] = $method->id;
                    $script .= 'document.getElementById("rfmp_pm_' . $method->id . '_' . $post . '").style.display = "block";' . "\n";
                }
            }
            foreach ($this->mollie->methods->all(0,0) as $method)
            {
                if ($active[$method->id] && !in_array($method->id, $rcur))
                    $script .= 'document.getElementById("rfmp_pm_' . $method->id . '_' . $post . '").style.display = (frequency!="once" ? "none" : "block");' . "\n";
            }

            $methods = '
            <script>
            window.onload = rfmp_recurring_methods_' . $post . '();
            function rfmp_recurring_methods_' . $post . '() {
                var priceoptions = document.getElementsByName("rfmp_priceoptions_' . $post . '");
                if (priceoptions[0].tagName == "INPUT")
                {
                    for (var i = 0, length = priceoptions.length; i < length; i++) {
                        if (priceoptions[i].checked) {
                            var frequency = priceoptions[i].dataset.frequency;
                            break;
                        }
                    }
                } else {
                    var frequency = priceoptions[0].options[priceoptions[0].selectedIndex].dataset.frequency;
                }
                                   
                
                ' . $script . '
            }
            </script>';

            if ($display != 'dropdown')
            {
                $first = true;
                $methods .= '<ul class="' . esc_attr($class) . '" style="list-style-type:none;margin:0;">';
                foreach ($this->mollie->methods->all(0,0, array('locale' => get_locale(), 'recurringType' => null)) as $method)
                {
                    if ($active[$method->id])
                    {
                        $subcharge = array();
                        if (isset($fixed[$method->id]) && $fixed[$method->id])
                            $subcharge[] = '&euro; ' . str_replace(',','.',$fixed[$method->id]);

                        if (isset($variable[$method->id]) && $variable[$method->id])
                            $subcharge[] = str_replace(',','.',$variable[$method->id]) . '%';

                        if ($display == 'list')
                        {
                            $methods .= '<li id="rfmp_pm_' . esc_attr($method->id) . '_' . $post . '"><label><input type="radio" name="rfmp_payment_method_' . $post . '" value="' . esc_attr($method->id) . '"' . ($form_value == $method->id || $first ? ' checked' : '') . '> <img style="vertical-align:middle;display:inline-block;" src="' . esc_url($method->image->normal) . '"> ' . esc_html($method->description) . (!empty($subcharge) ? ' (+ ' . implode(' & ', $subcharge) . ')' : '') . '</label></li>';
                        }
                        elseif ($display == 'text')
                        {
                            $methods .= '<li id="rfmp_pm_' . esc_attr($method->id) . '_' . $post . '"><input type="radio" name="rfmp_payment_method_' . $post . '" value="' . esc_attr($method->id) . '"' . ($form_value == $method->id || $first ? ' checked' : '') . '> ' . esc_html($method->description) . (!empty($subcharge) ? ' (+ ' . implode(' & ', $subcharge) . ')' : '') . '</li>';
                        }
                        elseif ($display == 'icons')
                        {
                            $methods .= '<li id="rfmp_pm_' . esc_attr($method->id) . '_' . $post . '"><input type="radio" name="rfmp_payment_method_' . $post . '" value="' . esc_attr($method->id) . '"' . ($form_value == $method->id || $first ? ' checked' : '') . '> <img style="vertical-align:middle;display:inline-block;" src="' . esc_url($method->image->normal) . '"> ' . (!empty($subcharge) ? ' (+ ' . implode(' & ', $subcharge) . ')' : '') . '</li>';
                        }
                        $first = false;
                    }
                }
                $methods .= '</ul>';
            }
            else
            {
                $methods .= '<select name="rfmp_payment_method_' . $post . '" class="' . esc_attr($class) . '">';
                foreach ($this->mollie->methods->all(0,0, array('locale' => get_locale())) as $method)
                {
                    if ($active[$method->id])
                    {
                        $subcharge = array();
                        if (isset($fixed[$method->id]) && $fixed[$method->id])
                            $subcharge[] = '&euro; ' . str_replace(',','.',$fixed[$method->id]);

                        if (isset($variable[$method->id]) && $variable[$method->id])
                            $subcharge[] = str_replace(',','.',$variable[$method->id]) . '%';

                        $methods .= '<option id="rfmp_pm_' . esc_attr($method->id) . '_' . $post . '" value="' . esc_attr($method->id) . '"' . ($form_value == $method->id ? ' selected' : '') . '>' . esc_html($method->description) . (!empty($subcharge) ? ' (+ ' . implode(' & ', $subcharge) . ')' : '') . '</option>';
                    }
                }
                $methods .= '</select>';
            }

        } catch (Mollie_API_Exception $e) {
            $methods = '<p style="color: red">' . $e->getMessage() . '</p>';
        }

        return $methods;
    }

    private function price_options($post, $class)
    {
        $option_desc        = get_post_meta($post, '_rfmp_priceoption_desc', true);
        $option_price       = get_post_meta($post, '_rfmp_priceoption_price', true);
        $option_frequency   = get_post_meta($post, '_rfmp_priceoption_frequency', true);
        $option_frequencyval= get_post_meta($post, '_rfmp_priceoption_frequencyval', true);
        $option_display     = get_post_meta($post, '_rfmp_priceoptions_display', true);
        $form_value         = isset($_POST['rfmp_priceoptions_' . $post ]) ? $_POST['rfmp_priceoptions_' . $post] : '';

        $priceoptions = '';
        $first = true;
        if ($option_display == 'list')
        {
            $priceoptions .= '<ul class="' . esc_attr($class) . '" style="list-style-type:none;margin:0;">';
            foreach ($option_desc as $key => $desc)
            {
                $priceoptions .= '<li><label><input type="radio" onchange="rfmp_recurring_methods_' . $post . '();" data-frequency="' . esc_attr($option_frequency[$key]) . '" name="rfmp_priceoptions_' . $post . '" value="' . esc_attr($key) . '"' . ($form_value == $key || $first ? ' checked' : '') . '> ' . esc_html($desc) . ' (&euro;' . number_format($option_price[$key], 2, ',', '') . ' ' . $this->frequency_label($option_frequencyval[$key] . ' ' . $option_frequency[$key]) . ')</label></li>';
                $first = false;
            }
            $priceoptions .= '</ul>';
        }
        else
        {
            $priceoptions .= '<select name="rfmp_priceoptions_' . $post . '" onchange="rfmp_recurring_methods_' . $post . '();" class="' . esc_attr($class) . '">';
            foreach ($option_desc as $key => $desc)
            {
                $priceoptions .= '<option data-frequency="' . esc_attr($option_frequency[$key]) . '" value="' . esc_attr($key) . '"' . ($form_value == $key ? ' selected' : '') . '>' . esc_html($desc) . ' (&euro;' . number_format($option_price[$key], 2, ',', '') . ' ' . $this->frequency_label($option_frequencyval[$key] . ' ' . $option_frequency[$key]) . ')</option>';
            }
            $priceoptions .= '</select>';
        }

        return $priceoptions;
    }

    private function check_required($post)
    {
        $fields_label       = get_post_meta($post, '_rfmp_fields_label', true);
        $fields_value       = get_post_meta($post, '_rfmp_fields_value', true);
        $fields_required    = get_post_meta($post, '_rfmp_fields_required', true);

        $return = true;
        $this->required_errors = '';

        foreach ($fields_required as $key => $required)
        {
            $name = 'form_' . $post . '_field_' . $key;
            if (isset($_POST[$name]) && empty($_POST[$name]) && $required)
            {
                $return = false;
                $this->required_errors .= '<p class="rfmp_error" style="color:red;">- ' . sprintf(esc_html__('%s is a required field', RFMP_TXT_DOMAIN), $fields_label[$key]) . '</p>';
            }
        }

        return $return;
    }

    private function do_post($post)
    {
        $api_key    = get_post_meta($post, '_rfmp_api_key', true);
        $url        = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on'?'https://':'http://') . $_SERVER['HTTP_HOST'];
        $webhook    = $url . RFMP_WEBHOOK . $post . '/';
        $redirect   = $url . $_SERVER['REQUEST_URI'] . (strstr($_SERVER['REQUEST_URI'], '?') ? '&' : '?');


        try {

            if (!$api_key)
                echo '<p style="color: red">' . esc_html__('No API-key set', RFMP_TXT_DOMAIN) . '</p>';
            else
            {
                $this->mollie->setApiKey($api_key);

                $rfmp_id = uniqid('rfmp-' . $post . '-');

                $option             = $_POST['rfmp_priceoptions_' . $post];
                $option_desc        = get_post_meta($post, '_rfmp_priceoption_desc', true);
                $option_price       = get_post_meta($post, '_rfmp_priceoption_price', true);
                $option_frequency   = get_post_meta($post, '_rfmp_priceoption_frequency', true);
                $option_frequencyval= get_post_meta($post, '_rfmp_priceoption_frequencyval', true);

                $field_type         = get_post_meta($post, '_rfmp_fields_type', true);
                $field_label        = get_post_meta($post, '_rfmp_fields_label', true);

                $name_field         = array_search('name', $field_type);
                $email_field        = array_search('email', $field_type);
                $name_field_value   = trim($_POST['form_' . $post . '_field_' . $name_field]);
                $email_field_value  = trim($_POST['form_' . $post . '_field_' . $email_field]);

                $method             = $_POST['rfmp_payment_method_' . $post];
                $fixed              = get_post_meta($post, '_rfmp_payment_method_fixed', true);
                $variable           = get_post_meta($post, '_rfmp_payment_method_variable', true);

                $desc               = $option_desc[$option];
                $price              = $option_price[$option];

                if ($option_frequency[$option] == 'once')
                    $option_frequencyval[$option] = '';

                $frequency          = trim($option_frequencyval[$option] . ' ' . $option_frequency[$option]);

                // Calculate total price
                if (isset($variable[$method]) && $variable[$method])
                {
                    // Add variable surcharge for payment method
                    $price *= (1 + str_replace(',','.',$variable[$method]) / 100);
                }
                if (isset($fixed[$method]) && $fixed[$method])
                {
                    // Add fixed surcharge for payment method
                    $price += str_replace(',','.',$fixed[$method]);
                }

                $total = number_format($price, 2, '.', '');

                // Create new customer at Mollie
                $customer = $this->mollie->customers->create(array(
                    'name'  => $name_field_value,
                    'email' => $email_field_value,
                ));

                // Add customer to database
                $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . RFMP_TABLE_CUSTOMERS . "
                ( created_at, post_id, customer_id, name, email )
                VALUES ( NOW(), %d, %s, %s, %s )",
                    $post,
                    $customer->id,
                    $customer->name,
                    $customer->email
                ));

                // Create registration
                $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . RFMP_TABLE_REGISTRATIONS . "
                    ( created_at, post_id, customer_id, subscription_id, total_price, price_frequency, description )
                    VALUES ( NOW(), %d, %s, NULL, %s, %s, %s )",
                    $post,
                    $customer->id,
                    $total,
                    $frequency,
                    $desc
                ));
                $registration_id = $this->wpdb->insert_id;

                // Check frequency
                if ($option_frequency[$option] == 'once')
                {
                    // Single payment
                    $payment = $this->mollie->payments->create(array(
                        'amount'            => $total,
                        'description'       => $desc,
                        'method'            => $method,
                        'redirectUrl'       => $redirect . 'payment=' . $rfmp_id,
                        'webhookUrl'        => $webhook,
                        'customerId'        => $customer->id,
                        'metadata'          => array(
                            'rfmp_id'   => $rfmp_id
                        )
                    ));
                }
                else
                {
                    // Recurring payment, subscription
                    $payment = $this->mollie->payments->create(array(
                        'amount'            => $total,
                        'description'       => $desc,
                        'method'            => $method,
                        'redirectUrl'       => $redirect . 'payment=' . $rfmp_id ,
                        'webhookUrl'        => $webhook . 'first/' . $registration_id,
                        'customerId'        => $customer->id,
                        'recurringType'     => 'first',
                        'metadata'          => array(
                            'rfmp_id'   => $rfmp_id
                        )
                    ));
                }

                // Add field values of registration
                foreach ($field_label as $key => $field)
                {
                    if ($field_type[$key] != 'submit')
                    {
                        $value = $_POST['form_' . $post . '_field_' . $key];
                        if ($field_type[$key] == 'payment_methods')
                            $value = $_POST['rfmp_payment_method_' . $post];
                        elseif ($field_type[$key] == 'priceoptions')
                            $value = $desc;

                        $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . RFMP_TABLE_REGISTRATION_FIELDS . "
                    ( registration_id, field, `value`, `type` )
                    VALUES ( %d, %s, %s, %s )",
                            $registration_id,
                            $field,
                            $value,
                            $field_type[$key]
                        ));
                    }
                }

                // Create payment for registration
                $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . RFMP_TABLE_PAYMENTS . "
                    ( created_at, registration_id, payment_id, payment_method, payment_mode, payment_status, amount, rfmp_id )
                    VALUES ( NOW(), %d, %s, %s, %s, %s, %s, %s )",
                    $registration_id,
                    $payment->id,
                    $payment->method,
                    $payment->mode,
                    $payment->status,
                    $payment->amount,
                    $rfmp_id
                ));

                return wp_redirect($payment->getPaymentUrl());
            }


        } catch (Mollie_API_Exception $e) {
            echo '<p style="color: red">' . $e->getMessage() . '</p>';
        }
    }

    private function frequency_label($frequency)
    {
        $words = array(
            'days',
            'weeks',
            'months',
        );
        $translations = array(
            __('days', RFMP_TXT_DOMAIN),
            __('weeks', RFMP_TXT_DOMAIN),
            __('months', RFMP_TXT_DOMAIN),
        );

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
                $return = __('each', RFMP_TXT_DOMAIN) . ' ' . str_replace($words, $translations, $frequency);
        }

        return $return;
    }

}