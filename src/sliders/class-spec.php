<?php
/**
 * Sanitizes nested data against a declarative spec.
 *
 * @package SlideCraft
 */

namespace SlideCraft\Sliders;

/**
 * A spec is an array: [ type, default, extra ].
 *
 * Scalar types:
 * - bool
 * - int, float (extra: [ min, max ])
 * - enum (extra: allowed values)
 * - color (#rrggbb, or '' when the default is '')
 * - text (one line), textarea (plain, multi-line), inline (limited inline HTML), url, id (attachment or post ID)
 * - key (lowercase letters, digits, - and _; at most 64 characters), date (Y-m-d\TH:i, or '')
 *
 * Compound types:
 * - object (extra: child specs keyed by field name; default unused)
 * - list (extra: [ item spec, max items ]; default is the empty list)
 * - ids (list of positive integers; extra: max items)
 * - flags (list of enum values; extra: allowed values)
 */
final class Spec {

	/**
	 * Default value of a spec, resolving objects recursively.
	 *
	 * @param array $spec Spec.
	 * @return mixed
	 */
	public static function default_value( array $spec ) {
		if ( 'object' === $spec[0] ) {
			$out = array();
			foreach ( $spec[2] as $key => $child ) {
				$out[ $key ] = self::default_value( $child );
			}
			return $out;
		}
		if ( in_array( $spec[0], array( 'list', 'ids', 'flags' ), true ) ) {
			return is_array( $spec[1] ) ? $spec[1] : array();
		}
		return $spec[1];
	}

	/**
	 * Sanitize a value. Unknown keys are dropped; invalid values fall back to the default.
	 *
	 * @param mixed $value Value.
	 * @param array $spec  Spec.
	 * @return mixed
	 */
	public static function sanitize( $value, array $spec ) {
		$type    = $spec[0];
		$default = isset( $spec[1] ) ? $spec[1] : null;
		$extra   = isset( $spec[2] ) ? $spec[2] : null;

		switch ( $type ) {
			case 'object':
				$value = is_array( $value ) ? $value : array();
				$out   = array();
				foreach ( $extra as $key => $child ) {
					$out[ $key ] = array_key_exists( $key, $value ) ? self::sanitize( $value[ $key ], $child ) : self::default_value( $child );
				}
				return $out;
			case 'list':
				if ( ! is_array( $value ) ) {
					return self::default_value( $spec );
				}
				$out = array();
				foreach ( array_slice( array_values( $value ), 0, (int) $extra[1] ) as $item ) {
					if ( is_array( $item ) || 'object' !== $extra[0][0] ) {
						$out[] = self::sanitize( $item, $extra[0] );
					}
				}
				return $out;
			case 'ids':
				$ids = is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array();
				return array_slice( array_values( array_unique( $ids ) ), 0, $extra ? (int) $extra : 100 );
			case 'flags':
				$flags = is_array( $value ) ? array_values( array_intersect( (array) $extra, $value ) ) : array();
				return array_values( array_unique( $flags ) );
			case 'bool':
				return self::to_bool( $value );
			case 'int':
				return is_numeric( $value ) ? (int) min( max( (int) round( (float) $value ), $extra[0] ), $extra[1] ) : $default;
			case 'float':
				return is_numeric( $value ) ? round( (float) min( max( (float) $value, $extra[0] ), $extra[1] ), 3 ) : $default;
			case 'enum':
				return in_array( $value, (array) $extra, true ) ? $value : $default;
			case 'color':
				if ( '' === $value && '' === $default ) {
					return '';
				}
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
				return $color ? $color : $default;
			case 'text':
				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $default;
			case 'textarea':
				return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : $default;
			case 'inline':
				return is_scalar( $value ) ? wp_kses( (string) $value, self::inline_tags() ) : $default;
			case 'url':
				return is_string( $value ) ? esc_url_raw( trim( $value ), array( 'http', 'https', 'mailto', 'tel' ) ) : $default;
			case 'id':
				return absint( $value );
			case 'key':
				$key = is_string( $value ) ? strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', $value ) ) : '';
				return '' !== $key ? substr( $key, 0, 64 ) : $default;
			case 'date':
				return is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $value ) ? $value : $default;
		}
		return $default;
	}

	/**
	 * Inline HTML allowed in headings and text layers.
	 *
	 * @return array
	 */
	public static function inline_tags() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'u'      => array(),
			's'      => array(),
			'mark'   => array(),
			'br'     => array(),
			'small'  => array(),
			'sup'    => array(),
			'sub'    => array(),
			'span'   => array( 'class' => true ),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	/**
	 * Loose boolean conversion for values that arrive as strings.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes', 'Yes' ), true );
	}
}
