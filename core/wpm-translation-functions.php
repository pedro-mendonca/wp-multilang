<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wpm_translate_url( $url, $language = '' ) {

	$locale         = get_locale();
	$default_locale = wpm_get_default_locale();
	$languages      = wpm_get_languages();

	if ( $language ) {
		if ( ( $language == $languages[ $locale ] ) || ! in_array( $language, $languages ) ) {
			return $url;
		}
	}

	$url_lang = '';

	$path_url = parse_url( $url, PHP_URL_PATH );
	$path     = $path_url ? $path_url : '/';

	if ( preg_match( '!^/([a-z]{2})(/|$)!i', $path, $match ) ) {
		$url_lang = $match[1];
	}

	$new_path = '';

	if ( $language ) {

		if ( ! $url_lang && ( $language == $languages[ $default_locale ] ) ) {
			return $url;
		} elseif ( $url_lang && ( $language == $languages[ $default_locale ] ) ) {
			$new_path = str_replace( '/' . $url_lang . '/', '/', $path );
		} elseif ( $url_lang && ( $language != $languages[ $default_locale ] ) ) {
			$new_path = str_replace( '/' . $url_lang . '/', '/' . $language . '/', $path );
		} elseif ( ! $url_lang && ( $path != $languages[ $default_locale ] ) ) {
			$new_path = '/' . $language . $path;
		}
	} else {
		if ( ! $url_lang && ( $locale == $default_locale ) ) {
			return $url;
		} elseif ( ! $url_lang && ( $locale != $default_locale ) ) {
			$new_path = '/' . $languages[ $locale ] . $path;
		} elseif ( $url_lang && ( $locale == $default_locale ) ) {
			$new_path = str_replace( '/' . $url_lang . '/', '/', $path );
		} elseif ( $url_lang && ( $locale != $default_locale ) ) {
			$new_path = str_replace( '/' . $url_lang . '/', '/' . $languages[ $locale ] . '/', $path );
		}
	}

	if ( $new_path ) {
		if ( $path == '/' ) {
			$url .= substr( $new_path, 1 );
		} else {
			$url = str_replace( $path, $new_path, $url );
		}
	}

	return $url;
}

function wpm_translate_string( $string, $lang = '' ) {

	$strings = wpm_string_to_ml_array( $string );

	if ( ! is_array( $strings ) || empty( $strings ) ) {
		return $string;
	}

	if ( ! wpm_is_ml_array( $strings ) ) {
		return $strings;
	}

	$languages = wpm_get_languages();

	if ( $lang ) {
		if ( in_array( $lang, $strings ) ) {
			return $strings[ $lang ];
		} else {
			return '';
		}
	}

	$edit_lang = wpm_get_edit_lang();

	$default_locale = wpm_get_default_locale();

	if ( isset( $strings[ $edit_lang ] ) ) {
		return $strings[ $edit_lang ];
	} elseif ( isset( $strings[ $languages[ $default_locale ] ] ) ) {
		return $strings[ $languages[ $default_locale ] ];
	} else {
		return $string;
	}
}

function wpm_translate_value( $value, $lang = '' ) {
	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $k => $item ) {
			$result[ $k ] = wpm_translate_value( $item, $lang );
		}

		return $result;
	} elseif ( is_string( $value ) ) {
		return wpm_translate_string( $value, $lang );
	} else {
		return $value;
	}
}


function wpm_string_to_ml_array( $string ) {

	if ( ! is_string( $string ) ) {
		return $string;
	}

	$string = htmlspecialchars_decode( $string );

	$split_regex = "#(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\]|\{:[a-z]{2}\}|\{:\})#ism";
	$blocks      = preg_split( $split_regex, $string, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

	if ( empty( $blocks ) || count( $blocks ) == 1 ) {
		return $string;
	}

	$result = array();

	$languages = wpm_get_languages();

	foreach ( $languages as $language ) {
		$result[ $language ] = '';
	}

	$language = '';
	foreach ( $blocks as $block ) {

		if ( preg_match( "#^<!--:([a-z]{2})-->$#ism", $block, $matches ) ) {
			$language = $matches[1];
			continue;

		} elseif ( preg_match( "#^\[:([a-z]{2})\]$#ism", $block, $matches ) ) {
			$language = $matches[1];
			continue;

		} elseif ( preg_match( "#^\{:([a-z]{2})\}$#ism", $block, $matches ) ) {
			$language = $matches[1];
			continue;
		}

		switch ( $block ) {
			case '[:]':
			case '{:}':
			case '<!--:-->':
				$language = '';
				break;
			default:
				if ( $language ) {
					if ( isset( $result[ $language ] ) ) {
						$result[ $language ] .= $block;
					}
					$language = '';
				}
		}
	}

	foreach ( $result as $lang => $string ) {
		$result[ $lang ] = trim( $string );
	}

	return $result;
}


function wpm_value_to_ml_array( $value ) {
	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $k => $item ) {
			$result[ $k ] = wpm_value_to_ml_array( $item );
		}

		return $result;
	} elseif ( is_string( $value ) ) {
		return wpm_string_to_ml_array( $value );
	} else {
		return $value;
	}
}

function wpm_ml_array_to_string( $strings ) {

	$string = '';

	if ( ! wpm_is_ml_array( $strings ) ) {
		return $string;
	}

	$languages = wpm_get_languages();
	foreach ( $strings as $key => $value ) {
		if ( in_array( $key, $languages ) && ! empty( $value ) ) {
			if ( wpm_is_ml_string( $value ) ) {
				$string = wpm_translate_string( $string );
			}
			$string .= '[:' . $key . ']' . trim( $value );
		}
	}

	if ( ! $string ) {
		return '';
	}

	$string .= '[:]';

	return $string;
}


function wpm_ml_value_to_string( $value ) {

	if ( is_array( $value ) ) {
		if ( wpm_is_ml_array( $value ) ) {
			return wpm_ml_array_to_string( $value );
		} else {
			$result = array();
			foreach ( $value as $key => $item ) {
				$result[ $key ] = wpm_ml_value_to_string( $item );
			}

			return $result;
		}
	} else {
		return $value;
	}
}


function wpm_set_language_value( $localize_array, $value, $config = null, $lang = '' ) {
	$languages = wpm_get_languages();

	if ( ! $lang && isset( $_POST['lang'] ) && in_array( $_POST['lang'], $languages ) ) {
		$lang = wpm_clean( $_POST['lang'] );
	}

	if ( ! $lang || ! in_array( $lang, $languages ) ) {
		$lang = wpm_get_edit_lang();
	}

	if ( is_array( $value ) && ! is_null( $config ) ) {
		foreach ( $value as $key => $item ) {
			if ( isset( $config['wpm_each'] ) ) {
				$config_key = $config['wpm_each'];
			} else {
				$config_key = ( isset( $config[ $key ] ) ? $config[ $key ] : null );
			}

			if ( isset( $localize_array[ $key ] ) && isset( $value[ $key ] ) ) {
				$localize_array[ $key ] = wpm_set_language_value( $localize_array[ $key ], $value[ $key ], $config_key, $lang );
			}
		}
	} else {
		if ( ! is_null( $config ) && ! is_bool( $value ) ) {
			if ( wpm_is_ml_array( $localize_array ) ) {
				$localize_array[ $lang ] = $value;
			} else {
				$result = array();
				foreach ( $languages as $language ) {
					$result[ $language ] = '';
				}
				$result[ $lang ] = $value;
				$localize_array  = $result;
			}
		} else {
			$localize_array = $value;
		}
	}

	return $localize_array;
}

function wpm_translate_object( $object, $lang = '' ) {

	if ( $object instanceof WP_Post || $object instanceof WP_Term ) {

		foreach ( get_object_vars( $object ) as $key => $content ) {
			switch ( $key ) {
				case 'attr_title':
				case 'post_title':
				case 'post_excerpt':
				case 'name':
				case 'title':
				case 'description':
					$object->$key = wpm_translate_string( $content, $lang );
					break;
				case 'post_content':
					$object->$key = maybe_serialize( wpm_translate_value( maybe_unserialize( $content ), $lang ) );
					break;
			}
		}
	}

	return $object;
}

function wpm_untranslate_post( $post ) {

	if ( $post instanceof WP_Post ) {

		foreach ( get_object_vars( $post ) as $key => $content ) {
			switch ( $key ) {
				case 'post_title':
				case 'post_content':
				case 'post_excerpt':
					$post->$key = get_post_field( $key, $post->ID, 'edit' );
					break;
			}
		}
	}

	return $post;
}

function wpm_is_ml_array( $array ) {

	if ( ! is_array( $array ) ) {
		return false;
	}

	$languages = wpm_get_languages();

	foreach ( $array as $key => $item ) {
		if ( ! is_string( $key ) || ! in_array( $key, $languages ) ) {
			return false;
		}
	}

	return true;
}

function wpm_is_ml_string( $string ) {

	if ( is_array( $string ) || is_bool( $string ) ) {
		return false;
	}

	$strings = wpm_string_to_ml_array( $string );

	if ( is_array( $strings ) && ! empty( $strings ) ) {
		return true;
	}

	return false;
}

function wpm_is_ml_value( $value ) {

	if ( is_array( $value ) && ! empty( $value ) ) {
		$result = array();
		foreach ( $value as $item ) {
			$result[] = wpm_is_ml_value( $item );
		}

		if ( in_array( true, $result ) ) {
			return true;
		}

		return false;
	} else {
		return wpm_is_ml_string( $value );
	}
}
