<?php
/**
 * Gravity Flow Common Functions
 *
 *
 * @package     GravityFlow
 * @copyright   Copyright (c) 2015-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.3
 */


if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Gravity_Flow_Common {

	/**
	 * Returns a URl to a workflow page.
	 *
	 * @param int|null              $page_id
	 * @param Gravity_Flow_Assignee $assignee
	 * @param string                $access_token
	 *
	 * @return string
	 */
	public static function get_workflow_url( $query_args, $page_id = null, $assignee = null, $access_token = '' ) {
		if ( $assignee && $assignee->get_type() == 'email' ) {
			$token_lifetime_days        = apply_filters( 'gravityflow_entry_token_expiration_days', 30, $assignee );
			$token_expiration_timestamp = strtotime( '+' . (int) $token_lifetime_days . ' days' );
			$access_token               = $access_token ? $access_token : gravity_flow()->generate_access_token( $assignee, null, $token_expiration_timestamp );
		}

		$base_url = '';
		if ( ! empty( $page_id ) && $page_id != 'admin' ) {
			$base_url = get_permalink( $page_id );
		}

		if ( empty( $base_url ) ) {
			$base_url = admin_url( 'admin.php' );
		}

		if ( ! empty( $access_token ) ) {
			$query_args['gflow_access_token'] = $access_token;
		}

		return add_query_arg( $query_args, $base_url );
	}

	/**
	 * If form and field ids have bee specified for display on the inbox/status page add the columns.
	 *
	 * @param array $columns   The inbox/status page columns.
	 * @param int   $form_id   The form ID of the entries to be displayed or 0 to display entries from all forms.
	 * @param array $field_ids The field IDs or entry properties/meta to be displayed.
	 *
	 * @return array
	 */
	public static function get_field_columns( $columns, $form_id, $field_ids ) {
		if ( empty( $form_id ) || ! is_array( $field_ids ) || empty( $field_ids ) ) {
			return $columns;
		}

		$form       = GFAPI::get_form( $form_id );
		$entry_meta = GFFormsModel::get_entry_meta( $form_id );

		foreach ( $field_ids as $id ) {
			switch ( strtolower( $id ) ) {
				case 'ip' :
					$columns[ $id ] = __( 'User IP', 'gravityflow' );
					break;
				case 'source_url' :
					$columns[ $id ] = __( 'Source Url', 'gravityflow' );
					break;
				case 'payment_status' :
					$columns[ $id ] = __( 'Payment Status', 'gravityflow' );
					break;
				case 'transaction_id' :
					$columns[ $id ] = __( 'Transaction Id', 'gravityflow' );
					break;
				case 'payment_date' :
					$columns[ $id ] = __( 'Payment Date', 'gravityflow' );
					break;
				case 'payment_amount' :
					$columns[ $id ] = __( 'Payment Amount', 'gravityflow' );
					break;
				case ( ( is_string( $id ) || is_int( $id ) ) && array_key_exists( $id, $entry_meta ) ) :
					$columns[ $id ] = $entry_meta[ $id ]['label'];
					break;

				default:
					$field = GFFormsModel::get_field( $form, $id );

					if ( is_object( $field ) ) {
						$input_label_only = apply_filters( 'gform_entry_list_column_input_label_only', true, $form, $field );
						$columns[ $id ]   = GFFormsModel::get_label( $field, $id, $input_label_only );
					}
			}
		}

		return $columns;
	}

	/**
	 * Get an array of choices containing the user roles.
	 *
	 * @param bool $prefix_values Indicates if the choice value should be prefixed 'role|'. Default is true.
	 * @param bool $reverse       Indicates if the choices should be reversed. Default is false.
	 * @param bool $frontend      Indicates if the choices are being used by a front-end field. Default is false.
	 *
	 * @since 1.4.2-dev
	 *
	 * @return array
	 */
	public static function get_roles_as_choices( $prefix_values = true, $reverse = false, $frontend = false ) {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/user.php' );
		}

		$roles = get_editable_roles();

		if ( $reverse ) {
			$roles = array_reverse( $roles );
		}

		$choices = array();
		$prefix  = $prefix_values ? 'role|' : '';
		$key     = $frontend ? 'text' : 'label';

		foreach ( $roles as $role => $details ) {
			$name      = translate_user_role( $details['name'] );
			$choices[] = array( 'value' => $prefix . $role, $key => $name );
		}

		return $choices;
	}

	/**
	 * Retrieve attributes from a string (i.e. merge tag modifiers).
	 *
	 * @since 1.7.1-dev
	 *
	 * @param string $string   The string to retrieve the attributes from.
	 * @param array  $defaults The supported attributes and their defaults.
	 *
	 * @return array
	 */
	public static function get_merge_tag_attributes( $string, $defaults = array() ) {
		$attributes = shortcode_parse_atts( $string );

		if ( empty( $attributes ) ) {
			$attributes = array();
		}

		if ( ! empty( $defaults ) ) {
			$attributes = shortcode_atts( $defaults, $attributes );

			foreach ( $defaults as $attribute => $default ) {
				if ( $default === true ) {
					$attributes[ $attribute ] = strtolower( $attributes[ $attribute ] ) == 'false' ? false : true;
				} elseif ( $default === false ) {
					$attributes[ $attribute ] = strtolower( $attributes[ $attribute ] ) == 'true' ? true : false;
				}
			}
		}

		return $attributes;
	}

	/**
	 * Format the date/time or timestamp for display.
	 *
	 * @since 1.7.1-dev
	 *
	 * @param int|string $date_or_timestamp The unix timestamp or string in the Y-m-d H:i:s format to be formatted.
	 * @param string     $format            The format the date/time should be returned in. Default is d M Y g:i a.
	 * @param bool       $is_human          Indicates if the date/time should be returned in a human readable format such as "1 hour ago". Default is false.
	 * @param bool       $include_time      Indicates if the time should be included in the returned string. Default is false.
	 *
	 * @return string
	 */
	public static function format_date( $date_or_timestamp, $format = 'd M Y g:i a', $is_human = false, $include_time = false ) {
		$date_time = is_integer( $date_or_timestamp ) ? date( 'Y-m-d H:i:s', $date_or_timestamp ) : $date_or_timestamp;

		return GFCommon::format_date( $date_time, $is_human, $format, $include_time );
	}

	/**
	 * Get the 'workflow_notes' entry meta item.
	 *
	 * @since 1.7.1-dev
	 *
	 * @param int  $entry_id   The ID of the entry the notes are to be retrieved for.
	 * @param bool $for_output Should the notes be ordered newest to oldest? Default is false.
	 *
	 * @return array
	 */
	public static function get_workflow_notes( $entry_id, $for_output = false ) {
		$notes_json  = gform_get_meta( $entry_id, 'workflow_notes' );
		$notes_array = empty( $notes_json ) ? array() : json_decode( $notes_json, true );

		if ( $for_output && ! empty( $notes_array ) ) {
			$notes_array = array_reverse( $notes_array );
		}

		return $notes_array;
	}

	/**
	 * Add a note to the 'workflow_notes' entry meta item.
	 *
	 * @since 1.7.1-dev
	 *
	 * @param string $note           The note to be added.
	 * @param int    $entry_id       The ID of the entry the note is to be added to.
	 * @param int    $step_id        The ID of the current step.
	 * @param bool   $user_submitted Indicates if the note was added by the user.
	 */
	public static function add_workflow_note( $note, $entry_id, $step_id, $user_submitted = false ) {
		$notes = self::get_workflow_notes( $entry_id );

		$notes[] = array(
			'id'             => uniqid( '', true ),
			'step_id'        => $step_id,
			'assignee_key'   => gravity_flow()->get_current_user_assignee_key(),
			'user_submitted' => $user_submitted,
			'timestamp'      => time(),
			'value'          => $note,
		);

		gform_update_meta( $entry_id, 'workflow_notes', json_encode( $notes ) );
	}

	/**
	 * Get an array containing the notes from the entry meta and the legacy notes from the GF notes table.
	 *
	 * @since 1.7.1-dev
	 *
	 * @param array $entry The current entry.
	 *
	 * @return array
	 */
	public static function get_notes( $entry ) {
		$notes       = self::get_workflow_notes( $entry['id'], true );
		$entry_notes = array_reverse( RGFormsModel::get_lead_notes( $entry['id'] ) );

		foreach ( $entry_notes as $note ) {
			if ( $note->note_type !== 'gravityflow' ) {
				continue;
			}

			$notes[] = array(
				'id'             => $note->id,
				'step_id'        => ! $note->user_id ? $note->user_name : 0,
				'assignee_key'   => $note->user_id ? 'user_id|' . $note->user_id : false,
				'user_submitted' => (bool) $note->user_id,
				'timestamp'      => $note->date_created,
				'value'          => $note->value,
			);
		}

		$notes[] = self::get_initial_note( $entry );

		return $notes;
	}

	/**
	 * Get the Workflow Submitted note.
	 *
	 * @since 1.7.1-dev
	 *
	 * @param array $entry The current entry.
	 *
	 * @return array
	 */
	public static function get_initial_note( $entry ) {
		$user = get_userdata( $entry['created_by'] );

		return array(
			'id'             => 0,
			'step_id'        => 0,
			'assignee_key'   => $user ? 'user_id|' . $user->ID : false,
			'user_submitted' => false,
			'timestamp'      => $entry['date_created'],
			'value'          => esc_html__( 'Workflow Submitted', 'gravityflow' ),
		);
	}

	public static function get_gravityforms_db_version() {

		if ( method_exists( 'GFFormsModel', 'get_database_version' ) ) {
			$db_version = GFFormsModel::get_database_version();
		} else {
			$db_version = GFForms::$version;
		}

		return $db_version;
	}

	public static function get_entry_table_name() {
		return version_compare( self::get_gravityforms_db_version(), '2.3-dev-1', '<' ) ? GFFormsModel::get_lead_table_name() : GFFormsModel::get_entry_table_name();
	}

	public static function get_entry_meta_table_name() {
		return version_compare( self::get_gravityforms_db_version(), '2.3-dev-1', '<' ) ? GFFormsModel::get_lead_meta_table_name() : GFFormsModel::get_entry_meta_table_name();
	}

	public static function get_entry_id_column_name() {
		return version_compare( self::get_gravityforms_db_version(), '2.3-dev-1', '<' ) ? 'lead_id' : 'entry_id';
	}

}
