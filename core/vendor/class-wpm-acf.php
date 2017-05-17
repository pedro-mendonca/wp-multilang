<?php

namespace WPM\Core\Vendor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'acf' ) ) {

	class WPM_Acf {

		public function __construct() {
			add_filter( "acf/load_field", 'wpm_translate_value', 0 );
			add_filter( "acf/translate_field_group", 'wpm_translate_string', 0 );
			add_filter( "acf/update_field", array( $this, 'save_field' ), 99 );
			add_filter( "acf/update_field/type=text", array( $this, 'save_text_field' ), 99 );
			add_filter( "acf/update_field/type=textarea", array( $this, 'save_text_field' ), 99 );
			add_filter( "acf/update_field/type=wysiwyg", array( $this, 'save_text_field' ), 99 );
			add_filter( "acf/load_value", 'wpm_translate_value', 0 );
			add_filter( "acf/update_value/type=text", __NAMESPACE__ . '\WPM_Acf::save_value', 99, 3 );
			add_filter( "acf/update_value/type=textarea", __NAMESPACE__ . '\WPM_Acf::save_value', 99, 3 );
			add_filter( "acf/update_value/type=wysiwyg", __NAMESPACE__ . '\WPM_Acf::save_value', 99, 3 );
			add_filter( 'wpm_load_config', array( $this, 'add_config' ) );
		}


		public function add_config( $config ) {
			$config['post_types']['acf-field-group'] = array(
				"post_content" => null,
				"post_excerpt" => null
			);

			return $config;
		}


		public function save_field( $field ) {

			$old_field          = maybe_unserialize( get_post_field( 'post_content', $field['ID'] ) );
			$old_field          = wpm_value_to_ml_array( $old_field );
			$field_name         = get_post_field( 'post_title', $field['ID'] );
			$old_field['label'] = wpm_value_to_ml_array( $field_name );

			$default_config = array(
				'label'        => array(),
				'placeholder'  => array(),
				'instructions' => array()
			);

			$new_field = wpm_set_language_value( $old_field, $field, $default_config );
			$field     = wpm_array_merge_recursive( $field, $new_field );
			$field     = wpm_ml_value_to_string( $field );

			return $field;
		}


		public function save_text_field( $field ) {

			$old_field = maybe_unserialize( get_post_field( 'post_content', $field['ID'] ) );
			$old_field = wpm_value_to_ml_array( $old_field );

			$default_config = array(
				'default_value' => array()
			);

			$field     = wpm_value_to_ml_array( $field );
			$new_field = wpm_set_language_value( $old_field, $field, $default_config );
			$field     = wpm_array_merge_recursive( $field, $new_field );
			$field     = wpm_ml_value_to_string( $field );

			return $field;
		}


		static public function save_value( $value, $post_id, $field ) {
			remove_filter( 'acf/load_value', 'wpm_translate_value', 0 );
			$old_value = get_field( $field['name'], $post_id );
			add_filter( "acf/load_value", 'wpm_translate_value', 0 );
			$old_value = wpm_value_to_ml_array( $old_value );
			$new_value = wpm_set_language_value( $old_value, $value, array() );
			$new_value = wpm_ml_value_to_string( $new_value );

			return $new_value;
		}
	}

	new WPM_Acf();

}