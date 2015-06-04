<?php

/* **************************************************************

 Class creates a reusable, configurable pulugin settings page

 **************************************************************** */

if( !class_exists( 'America_Publication_Settings' ) ) {

    class America_Publication_Settings {

        public $settings                = array ();
        public $default_settings        = array ();
        public $option_name             = 'appt_publication_options';                           // option name stored in db (array) - houses array of plugin options

        private $default_options        = array ();                                             // default plugin option values

        const PAGE_SLUG     = 'publication-settings-admin';                                     // settings page slug

        public function __construct() {
            $this->settings     = array (
                'appt_option_name'              => $this->option_name,                          // name entered in db options table
                'appt_page_title'               =>  __( 'Publication Settings', 'america'),     // the settings page title
                'appt_form_sections'            => $this->appt_options_form_sections(),         // the settings form sections
                'appt_form_fields'              => $this->appt_options_form_fields()            // the settings form fields
            );

            $this->default_settings = array (
                'id'                            => 'default_field_id',                          // the ID of the setting in our options array, and the ID of the HTML form element
                'title'                         => 'Default Field Label',                       // the label for the HTML form element
                'desc'                          => '',                                          // the description displayed under the HTML form element, leave blank for no desc
                'std'                           => '',                                          // the default value for this setting
                'type'                          => 'text',                                      // the HTML form element to use
                'section'                       => 'appt_main',                                 // the section this setting belongs to — must match the array key of a section in appt_options_form_sections()
                'class'                         => ''                                           // the form element class. Also used for validation purposes (see appt_sanitize function)
            );

            // populate with reasonable defaults for plugin options
            $this->default_options = array (
                'appt_publications_per_page'    => 12,                                          // number of pubs per page
            );

            add_action( 'admin_menu', array( $this, 'appt_add_options_page' ) );
            add_action( 'admin_init', array( $this, 'appt_register_settings' ) );
        }


        /**
         * Assign menu that this setting page be accessed from (i.e. Settings, Tool, etc.)
         * @return function call
         */
        //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
        function appt_add_options_page ()  {

            // Display Publication Settings Page link under the "Appearance" Admin
            // (also appears in a Settings link on the Plugins page)
            add_options_page (
                'Publication Settings',
                'Publication Settings',
                'manage_options',
                self::PAGE_SLUG,
                array( $this, 'create_admin_page' )
            );
        }

        /**
         * Register settings and its sanitization callback
         * @return function call
         */
        function appt_register_settings() {

            $settings           = $this->settings;
            $option_name        = $this->settings['appt_option_name'];

            add_option( $option_name );

            // Register an array that will house all options -- only adds 1 option to db
            register_setting( $option_name, $option_name, array( $this, 'appt_sanitize' ) );

            // add form sections
            if( !empty( $settings['appt_form_sections'] ) ) {
                foreach ( $settings['appt_form_sections'] as $id => $title ) {
                    add_settings_section( $id, $title, array( $this, 'appt_print_section_info' ), self::PAGE_SLUG );
                }
            }

            // add form fields
            if( !empty( $settings['appt_form_fields'] ) ) {
                foreach ( $settings['appt_form_fields'] as $option ) {
                    $this->appt_create_settings_field( $option );
                }
            }
        }

        /**
         * Define our settings sections
         *
         * array key=$id, array value=$title in: add_settings_section( $id, $title, $callback, $page );
         * @return array
         */
        function appt_options_form_sections() {

            // use sections array to add page sections if needed
            // add to sections array to add more sections
            // i.e. $sections['txtarea_section']    = __('Textarea Form Fields', 'america');
            $sections = array();
            $sections['appt_main'] = __('General', 'america');
            return $sections;
        }

         /**
         * Options: id, title, desc, std, class, type and section
         * If an option is excluded, field will use default value (see $defaults array in appt_create_settings_field method)
         *
         * For additional form fields, add to the options[] array
         * For example
         * $options[] = array(
         *    "section" => "txt_section",                                                           // section form field should appear in (section addedd in appt_options_form_sections method)
         *    "id"      => 'app_prefix' . "_nohtml_txt_input",                                      // for this plugin we used 'appt'
         *    "title"   => __( 'No HTML!', 'wamerica' ),                                            // form label
         *    "desc"    => __( 'A text input field where no html input is allowed.', 'america' ),   // description, leave blank if none needed
         *    "type"    => "text",                                                                  // used to display field type (i.e. text, checkbox etc. and used to determine the validation routine)
         *    "std"     => __('Some default value','wamerica'),                                     // default
         *    "class"   => "nohtml"                                                                 // used to deterine validation
         * )
         *
         * Current accepted values for type and class options:
         * type: text (update validation routine in appt_sanitize method if other types are added (i.e. textarea, select, etc.))
         * class: numeric (update validation routine in appt_sanitize method if other classes are added (i.e. url, email etc.))
         *
         * @return array options for a form field
         */
        function appt_options_form_fields() {
            $options[] = array (
                "id"      => "appt_publications_per_page",
                "title"   => __( 'Publications per page', 'america' ),
                "desc"    => '',
                "std"     => "12",
                "class"   => "numeric"
            );

            return $options;
        }


        /**
         * Helper function for registering our form field settings
         *
         * src: http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
         * @param (array) $args The array of arguments to be used in creating the field
         * @return function call
         */
        function appt_create_settings_field( $args = array() ) {

            // "extract" to be able to use the array keys as variables in our function output below
            // wp_parse_args is a generic utility for merging together an array of arguments and an array of default values.
            extract( wp_parse_args( $args, $this->default_settings  ) );

            // additional arguments for use in form field output in the function appt_form_field
            $field_args = array (
                'type'      => $type,
                'id'        => $id,
                'desc'      => $desc,
                'std'       => $std,
                'label_for' => $id,
                'class'     => $class
            );

            add_settings_field( $id, $title, array( $this, 'appt_form_field' ), self::PAGE_SLUG, $section, $field_args );
        }

        /*
         * Form Fields HTML
         * All form field types share the same function
         * @return echoes output
         */
        function appt_form_field ( $args = array() ) {

            extract( $args );

            // get the settings sections array
            $appt_option_name   = $this->settings['appt_option_name'];
            $options            = get_option( $appt_option_name );

            // pass the standard value if the option is not yet set in the database
            if ( !isset( $options[$id] ) ) {
                $options[$id] = $std;
            }

            // additional field class. output only if the class is defined in the create_setting arguments
            $field_class = ( $class != '' ) ? ' ' . $class : '';

            // switch html display based on the setting type.
            switch ( $type ) {
                case 'text':
                    $options[$id] = stripslashes( $options[$id] );
                    $options[$id] = esc_attr( $options[$id] );
                    echo "<input class='regular-text$field_class' type='text' id='$id' name='" . $appt_option_name . "[$id]' value='$options[$id]' />";
                    echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
                break;
            }
        }

        /**
         * Validates input based on class and type values entered in $options[] array
         *
         * @param  (array)
         * @return function call
         */
        function appt_sanitize( $input ) {

            // for enhanced security, create a new empty array
            $valid_input = array();

            $fields = $this->settings['appt_form_fields'];

            foreach ( $fields as $field ) {

                $id = $input[$field['id']];

                // determine validation based on the class entered in
                switch ( $field['class'] ) {
                    // validate for numeric fields
                    case 'numeric':

                        $id  = trim($id); // trim whitespace
                        $valid_input[$field['id']] = ( is_numeric( $id ) ) ? (int) $id : $field['std'];                                             // reset to default value

                        // register error
                        // TO DO: add css class to show fields with errors, deal with internationalization for concatenated strings
                        if( is_numeric($id) == FALSE ) {
                            add_settings_error(
                                $field['id'],                                                                                                        // setting title
                                'appt_txt_numeric_error',                                                                                            // error ID
                                __( 'Expecting a numeric value for ' . $field['title'] . ' field. Reset field value to default', 'america' ),    // error message
                                'error'                                                                                                              // type of message
                            );
                        }
                    break;
                }
            }

            return $valid_input;
        }

        /**
        * Displays form section information
        * @return echoes output
        */
        function appt_print_section_info() {
            echo "<p>" . __('Enter your settings below:', 'america' ) . "</p>" ;
        }

        /**
        * Displays form on page
        * @return echoes output
        */
        function create_admin_page() {
            $settings = $this->settings;
            ?>
            <div class="wrap">
                <h2><?php echo $settings['appt_page_title']; ?></h2>

                <form action="options.php" method="post">
                   <?php
                    // http://codex.wordpress.org/Function_Reference/settings_fields
                    settings_fields( $settings['appt_option_name'] );

                    // http://codex.wordpress.org/Function_Reference/do_settings_sections
                    do_settings_sections( self::PAGE_SLUG );
                    ?>
                    <p class="submit">
                        <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes','america'); ?>" />
                    </p>

                </form>
            </div><!-- wrap -->
            <?php
        }

        /**
         *
         * Getters and Setters will need to be customized per theme
         *
         */

        /**
         * Returns number of publication to display per pag
         *
         * @return string Number of publication to display per page
         */
        function get_pubs_per_page () {
            $options = get_option( $this->option_name );

            if ( !empty( $options['appt_publications_per_page'] ) ) {
                return $options['appt_publications_per_page'];
            } else {
                return $this->default_options['appt_publications_per_page'];
            }
        }

    }

}
