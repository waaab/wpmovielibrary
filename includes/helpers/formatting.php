<?php
/**
 * The file that defines the plugin formatting functions.
 *
 * @link       http://wpmovielibrary.com
 * @since      3.0
 *
 * @package    WPMovieLibrary
 * @subpackage WPMovieLibrary/includes/helpers
 */

/*
 * Generic formatting functions.
 */

/**
 * Format a money value.
 * 
 * @since    3.0
 * 
 * @param    string    $money field value
 * 
 * @return   string    Formatted value
 */
function get_formatted_money( $money ) {

	$money = intval( $money );
	if ( ! $money ) {
		return get_formatted_empty_value( $money );
	}

	/**
	 * Filter a money value.
	 * 
	 * @since    3.0
	 * 
	 * @param    string     $formatted_money Formatted money value.
	 * @param    string     $money Raw money value.
	 */
	$money = apply_filters( 'wpmoly/filter/format/money', '$' . number_format_i18n( $money ), $money );

	return get_formatted_empty_value( $money );
}

/**
 * Format Movies empty fields.
 * 
 * This is used by almost every other formatting function get_formatted_movie_to filter and replace
 * empty values.
 * 
 * @since    3.0
 * 
 * @param    string    $value field value
 * 
 * @return   string    Formatted value
 */
function get_formatted_empty_value( $value ) {

	if ( ! empty( $value ) ) {
		return $value;
	}

	/**
	 * Filter empty meta value.
	 * 
	 * Use a long dash for replacer.
	 * 
	 * @param    string    $value Empty value replacer
	 */
	return apply_filters( 'wpmoly/filter/meta/empty/value', '&mdash;' );
}

/**
 * Format Movies misc actors/genres list depending on
 * existing terms.
 * 
 * This is used to provide links for actors and genres lists
 * by using the metadata lists instead of taxonomies. But since
 * actors and genres can be added to the metadata and not terms,
 * we rely on metadata to show a correct list.
 * 
 * @since    3.0
 * 
 * @param    string    $terms Field value.
 * @param    string    $taxonomy Taxonomy we're dealing with.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_terms_list( $terms, $taxonomy, $options = array() ) {

	if ( empty( $terms ) ) {
		return get_formatted_empty_value( $terms );
	}

	$options = wp_parse_args( (array) $options, array(
		'is_link' => true
	) );

	$has_taxonomy = (boolean) wpmoly_o( "enable-{$taxonomy}" );

	if ( is_string( $terms ) ) {
		$terms = explode( ',', $terms );
	}

	foreach ( $terms as $key => $term ) {
		
		$term = trim( str_replace( array( '&#039;', "’" ), "'", $term ) );

		if ( ! $has_taxonomy ) {
			$t = $term;
		}
		else {
			$t = get_term_by( 'name', $term, $taxonomy );
			if ( ! $t ) {
				$t = get_term_by( 'slug', sanitize_title( $term ), $taxonomy );
			}
		}

		if ( ! $t ) {
			$t = $term;
		}

		if ( is_object( $t ) && '' != $t->name ) {
			if ( true === $options['is_link'] ) {
				$link = get_term_link( $t, $taxonomy );
				if ( ! is_wp_error( $link ) ) {
					$t = sprintf( '<a href="%s" title="%s">%s</a>', $link, sprintf( __( 'More movies from %s', 'wpmovielibrary' ), $t->name ), $t->name );
				} else {
					$t = $t->name;
				}
			} else {
				$t = $t->name;
			}
		}

		$terms[ $key ] = $t;
	}

	if ( empty( $terms ) ) {
		return '';
	}

	return implode( ', ', $terms );
}

/*
 * Movie specific functions.
 * 
 * Handle formatting for Movie metadata and details.
 */

/**
 * Format Movies details.
 * 
 * @since    3.0
 * 
 * @param    string     $detail Detail slug.
 * @param    array      $value Detail value.
 * @param    array      $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_detail( $detail, $value, $options = array() ) {

	$value = (array) $value;
	if ( empty( $value ) ) {
		return get_formatted_empty_value( $value );
	}

	$details = wpmoly_o( 'default_details' );
	if ( ! isset( $details[ $detail ]['options'] ) ) {
		return '';
	}

	$options = wp_parse_args( (array) $options, array(
		'show_text'  => true,
		'show_icon'  => true,
		'is_link'    => true,
		'attr_title' => ''
	) );

	$details = $details[ $detail ]['options'];
	foreach ( $value as $key => $slug ) {
		if ( isset( $details[ $slug ] ) ) {
			$filtered_value = '';
			if ( true === $options['show_text'] ) {
				$filtered_value = __( $details[ $slug ], 'wpmovielibrary' );
			}

			if ( _is_bool( $options['show_icon'] ) ) {
				$icon = '<span class="wpmolicon icon-' . $slug . '"></span>&nbsp;';
			} else {
				$icon = false;
			}

			/**
			 * Filter single detail value.
			 * 
			 * This is used to generate permalinks for details and can be extended to
			 * post-formatting modifications.
			 * 
			 * @since    3.0
			 * 
			 * @param    string    $filtered_value Filtered detail value.
			 * @param    string    $slug Detail slug value.
			 * @param    array     $options Formatting options.
			 */
			$filtered_value = apply_filters( "wpmoly/filter/detail/{$detail}/single", $filtered_value, $slug, $options );

			if ( _is_bool( $options['is_link'] ) ) {

				$url   = get_movie_meta_url( 'status', $slug );
				$title = sprintf( __( 'Movies filed as “%s”', 'wpmovielibrary' ), $filtered_value );
				$link  = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . $filtered_value . '</a>';

				/**
				 * Filter meta permalink.
				 * 
				 * @since    3.0
				 * 
				 * @param    string    $link HTML link.
				 * @param    string    $url Permalink URL.
				 * @param    string    $title Permalink title attribute.
				 * @param    string    $content Permalink content.
				 */
				$link = apply_filters( "wpmoly/filter/detail/{$detail}/single/link", $link, $url, $title, $filtered_value );

				$value[ $key ] = $icon . $link;
			} else {
				$value[ $key ] = $icon . $filtered_value;
			}
		}
	}

	/**
	 * Filter final detail value.
	 * 
	 * This is used to generate permalinks for details and can be extended to
	 * post-formatting modifications.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $filtered_value Filtered detail value.
	 * @param    string    $value Detail slug value.
	 * @param    array     $options Formatting options.
	 */
	return apply_filters( "wpmoly/filter/detail/{$detail}", implode( ', ', $value ), $value, $options );
}

/**
 * Format Movies casting.
 * 
 * Alias for get_formatted_movie_cast()
 * 
 * @since    3.0
 * 
 * @param    string     $actors Movie actors list.
 * @param    array      $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_actors( $actors, $options = array() ) {

	return get_formatted_movie_cast( $actors, $options = array() );
}

/**
 * Format Movies adult status.
 * 
 * @since    3.0
 * 
 * @param    string    $adult field value.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_adult( $adult, $options = array() ) {

	if ( empty( $adult ) ) {
		$status = '';
	} elseif ( _is_bool( $adult ) ) {
		$status = __( 'Yes', 'wpmovielibrary' );
	} else {
		$status = __( 'No', 'wpmovielibrary' );
	}

	if ( empty( $status ) ) {
		return get_formatted_empty_value( $status );
	}

	/**
	 * Filter final adult restriction.
	 * 
	 * @since    3.0
	 * 
	 * @param    string     $status Filtered adult restriction.
	 * @param    boolean    $is_adult Adult restriction?
	 */
	return apply_filters( 'wpmoly/filter/meta/adult', $status, _is_bool( $adult ) );
}

/**
 * Format Movies author.
 * 
 * @since    3.0
 * 
 * @param    string    $author Movie author.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_author( $author, $options = array() ) {

	if ( empty( $author ) ) {
		return get_formatted_empty_value( $author );
	}

	$authors = explode( ',', $author );
	foreach ( $authors as $key => $author ) {

		/**
		 * Filter single author meta value.
		 * 
		 * This is used to generate permalinks for authors and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $author Filtered author.
		 */
		$authors[ $key ] = apply_filters( 'wpmoly/filter/meta/author/single', trim( $author ) );
	}

	/**
	 * Filter final authors lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $authors Filtered authors list.
	 */
	return apply_filters( 'wpmoly/filter/meta/author', implode( ', ', $authors ) );
}

/**
 * Format Movies budget.
 * 
 * Alias for get_formatted_money()
 * 
 * @since    3.0
 * 
 * @param    string     $budget Movie budget.
 * @param    array      $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_budget( $budget, $options = array() ) {

	return get_formatted_money( $budget );
}

/**
 * Format Movies casting.
 * 
 * Match each actor against the actor taxonomy to detect missing
 * terms. If term actor exists, provide a link, raw text value
 * if no matching term could be found.
 * 
 * @since    3.0
 * 
 * @param    string    $actors Movie actors list.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_cast( $actors, $options = array() ) {

	$actors = get_formatted_terms_list( $actors,  'actor', $options = array() );

	return get_formatted_empty_value( $actors );
}

/**
 * Format Movies certification.
 * 
 * @since    3.0
 * 
 * @param    string    $certification Movie certification.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_certification( $certification, $options = array() ) {

	if ( empty( $certification ) ) {
		return $certification;
	}

	/**
	 * Filter final certification.
	 * 
	 * @since    3.0
	 * 
	 * @param    string     $certification Filtered certification.
	 */
	return apply_filters( 'wpmoly/filter/meta/certification', $certification );
}

/**
 * Format Movies composer.
 * 
 * @since    3.0
 * 
 * @param    string    $composer Movie original music composer.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_composer( $composer, $options = array() ) {

	if ( empty( $composer ) ) {
		return get_formatted_empty_value( $composer );
	}

	$composers = explode( ',', $composer );
	foreach ( $composers as $key => $composer ) {

		/**
		 * Filter single composer meta value.
		 * 
		 * This is used to generate permalinks for producers and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $composer Filtered composer.
		 */
		$composers[ $key ] = apply_filters( 'wpmoly/filter/meta/composer/single', trim( $composer ) );
	}

	/**
	 * Filter final production producers lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $composers Filtered producers list.
	 */
	return apply_filters( 'wpmoly/filter/meta/composer', implode( ', ', $composers ) );
}

/**
 * Format Movies countries.
 * 
 * Alias for get_formatted_movie_production_countries()
 * 
 * @since    3.0
 * 
 * @param    string     $countries Countries list.
 * @param    array      $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_countries( $countries, $options = array() ) {

	return get_formatted_movie_production_countries( $countries, $options );
}

/**
 * Format Movies release date.
 * 
 * Alias for get_formatted_movie_release_date()
 * 
 * @since    3.0
 * 
 * @param    string    $date Movie release date.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_date( $date, $options = array() ) {

	return get_formatted_movie_release_date( $date, $options );
}

/**
 * Format Movies director.
 * 
 * Match each name against the collection taxonomy to detect missing
 * terms. If term collection exists, provide a link, raw text value
 * if no matching term could be found.
 * 
 * @since    3.0
 * 
 * @param    string    $director field value.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_director( $director, $options = array() ) {

	$director = get_formatted_terms_list( $director, 'collection', $options = array() );

	return get_formatted_empty_value( $director );
}

/**
 * Format Movies format.
 * 
 * @since    3.0
 * 
 * @param    array     $format Movie formats.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_format( $format, $options = array() ) {

	return get_formatted_movie_detail( 'format', $format, $options );
}

/**
 * Format Movies genres.
 * 
 * Match each genre against the genre taxonomy to detect missing
 * terms. If term genre exists, provide a link, raw text value
 * if no matching term could be found.
 * 
 * @since    3.0
 * 
 * @param    string    $genres field value.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_genres( $genres, $options = array() ) {

	$genres = get_formatted_terms_list( $genres, 'genre', $options );

	return get_formatted_empty_value( $genres );
}

/**
 * Format movie homepage link.
 * 
 * @since    3.0
 * 
 * @param    string    $homepage Homepage link.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_homepage( $homepage, $options = array() ) {

	if ( empty( $homepage ) ) {
		return get_formatted_empty_value( $homepage );
	}

	$homepage = sprintf( '<a href="%1$s" title="%2$s">%1$s</a>', esc_url( $homepage ), __( 'Official Website', 'wpmovielibrary' ) );

	return get_formatted_empty_value( $homepage );
}

/**
 * Format Movies language.
 * 
 * Alias for get_formatted_movie_spoken_languages()
 * 
 * @since    3.0
 * 
 * @param    array     $languages Movie languages.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_language( $languages, $options = array() ) {

	$options = (array) $options;
	$options['variant'] = 'my_';

	return get_formatted_movie_spoken_languages( $languages, $options );
}

/**
 * Format Movies languages.
 * 
 * Alias for get_formatted_movie_spoken_languages()
 * 
 * @since    3.0
 * 
 * @param    array     $languages Languages.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_languages( $languages, $options = array() ) {

	return get_formatted_movie_spoken_languages( $languages, $options );
}

/**
 * Format Movies local release date.
 * 
 * Alias for get_formatted_movie_release_date()
 * 
 * @since    3.0
 * 
 * @param    string    $date Movie local release date.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_local_release_date( $date, $options = array() ) {

	$options = (array) $options;
	$options['variant'] = 'local_';

	return get_formatted_movie_release_date( $date, $options );
}

/**
 * Format Movies media.
 * 
 * @since    3.0
 * 
 * @param    string    $medias Movie media.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_media( $media, $options = array() ) {

	return get_formatted_movie_detail( 'media', $media, $options );
}

/**
 * Format Movies director of photography.
 * 
 * @since    3.0
 * 
 * @param    string    $photography Movie director of photography.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_photography( $photography, $options = array() ) {

	if ( empty( $photography ) ) {
		return $photography;
	}

	$photography = explode( ',', $photography );
	foreach ( $photography as $key => $photographer ) {

		/**
		 * Filter single DOP meta value.
		 * 
		 * This is used to generate permalinks for DOPs and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $producer Filtered producer.
		 */
		$photography[ $key ] = apply_filters( 'wpmoly/filter/meta/photography/single', trim( $photographer ) );
	}

	/**
	 * Filter final production directors of photography lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $producers Filtered directors of photography list.
	 */
	return apply_filters( 'wpmoly/filter/meta/photography', implode( ', ', $photography ) );
}

/**
 * Format Movies producers.
 * 
 * @since    3.0
 * 
 * @param    string    $producers Movie producers.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_producer( $producers, $options = array() ) {

	if ( empty( $producers ) ) {
		return get_formatted_empty_value( $producers );
	}

	$producers = explode( ',', $producers );
	foreach ( $producers as $key => $producer ) {

		/**
		 * Filter single producer meta value.
		 * 
		 * This is used to generate permalinks for producers and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $producer Filtered producer.
		 */
		$producers[ $key ] = apply_filters( 'wpmoly/filter/meta/producer/single', trim( $producer ) );
	}

	/**
	 * Filter final production producers lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $producers Filtered producers list.
	 */
	return apply_filters( 'wpmoly/filter/meta/producer', implode( ', ', $producers ) );
}

/**
 * Format Movies production companies.
 * 
 * Alias for get_formatted_movie_production_companies()
 * 
 * @since    3.0
 * 
 * @param    string    $companies Movie production companies.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_production( $companies, $options = array() ) {

	return get_formatted_movie_production_companies( $companies, $options );
}

/**
 * Format Movies production companies.
 * 
 * @since    3.0
 * 
 * @param    string    $companies Movie production companies.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_production_companies( $companies, $options = array() ) {

	if ( empty( $companies ) ) {
		return get_formatted_empty_value( $companies );
	}

	$companies = explode( ',', $companies );
	foreach ( $companies as $key => $company ) {

		/**
		 * Filter single country meta value.
		 * 
		 * This is used to generate permalinks for countries and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $company Filtered company.
		 */
		$companies[ $key ] = apply_filters( 'wpmoly/filter/meta/production/single', trim( $company ) );
	}

	/**
	 * Filter final production companies lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $companies Filtered companies list.
	 */
	return apply_filters( 'wpmoly/filter/meta/production_companies', implode( ', ', $companies ) );
}

/**
 * Format Movies countries.
 * 
 * @since    3.0
 * 
 * @param    string    $countries Countries list.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_production_countries( $countries, $options = array() ) {

	if ( empty( $countries ) ) {
		return get_formatted_empty_value( $countries );
	}

	if ( '1' == wpmoly_o( 'translate-countries' ) ) {
		$formats = wpmoly_o( 'countries-format', array() );
	} elseif ( false === $options['show_icon'] ) {
		$formats = array( 'flag', 'original' );
	} else {
		$formats = array( 'original' );
	}

	$countries_data = array();

	$countries = explode( ',', $countries );
	foreach ( $countries as $key => $country ) {

		$country = get_country( $country );

		$items = array();
		foreach ( $formats as $format ) {

			switch ( $format ) {
				case 'flag':
					$item = $country->flag();
					break;
				case 'original':
					$item = $country->standard_name;
					break;
				case 'translated':
					$item = $country->localized_name;
					break;
				case 'ptranslated':
					$item = sprintf( '(%s)', $country->localized_name );
					break;
				case 'poriginal':
					$item = sprintf( '(%s)', $country->standard_name );
					break;
				default:
					$item = '';
					break;
			}

			/**
			 * Filter single country meta value.
			 * 
			 * This is used to generate permalinks for countries and can be extended to
			 * post-formatting modifications.
			 * 
			 * @since    3.0
			 * 
			 * @param    string    $country Filtered country.
			 * @param    array     $country_data Country instance.
			 * @param    object    $format Country format.
			 */
			$items[] = apply_filters( 'wpmoly/filter/meta/country/single', $item, $country, $format );
		}

		$countries_data[ $key ] = $items;
		$countries[ $key ] = implode( '&nbsp;', $items );
	}

	if ( empty( $countries ) ) {
		return get_formatted_empty_value( $countries );
	}

	/**
	 * Filter final countries lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $countries Filtered countries list.
	 * @param    array     $countries_data Countries data array.
	 * @param    array     $formats Countries format.
	 */
	return apply_filters( 'wpmoly/filter/meta/production_countries', implode( ', ', $countries ), $countries_data, $formats );
}

/**
 * Format Movies rating.
 * 
 * @since    3.0
 * 
 * @param    string    $rating Movie rating.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_rating( $rating, $options = array() ) {

	// Parse formatting options
	$options = wp_parse_args( (array) $options, array(
		'show_icon' => true,
		'show_text' => true,
		'is_link'   => true,
		'include_empty' => true
	) );

	$base = (int) wpmoly_o( 'format-rating' );
	if ( 10 != $base ) {
		$base = 5;
	}

	$value = floatval( $rating );
	if ( 0 > $value ) {
		$value = 0.0;
	}
	if ( 5.0 < $value ) {
		$value = 5.0;
	}

	$value = number_format( $value, 1 );
	$details = wpmoly_o( 'default_details' );
	if ( isset( $details['rating']['options'][ $value ] ) ) {
		$title = $details['rating']['options'][ $value ];
	} else {
		$title = '';
	}

	$id = preg_replace( '/([0-5])(\.|_)(0|5)/i', '$1-$3', $value );
	$class = "wpmoly-movie-rating wpmoly-movie-rating-$id";

	$label = '';
	if ( _is_bool( $options['show_text'] ) ) {
		$label = $title;
	}

	$html = '';
	if ( _is_bool( $options['show_icon'] ) ) {

		$stars = array();

		/**
		 * Filter filled stars icon HTML block.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $html Filled star icon default HTML block
		 */
		$stars['filled'] = apply_filters( 'wpmoly/filter/html/filled/star', '<span class="wpmolicon icon-star-filled"></span>' );

		/**
		 * Filter half-filled stars icon HTML block.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $html Half-filled star icon default HTML block
		 */
		$stars['half']= apply_filters( 'wpmoly/filter/html/half/star', '<span class="wpmolicon icon-star-half"></span>' );

		/**
		 * Filter empty stars icon HTML block.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $html Empty star icon default HTML block
		 */
		$stars['empty'] = apply_filters( 'wpmoly/filter/html/empty/star', '<span class="wpmolicon icon-star-empty"></span>' );

		$filled = floor( $value );
		$half   = ceil( $value - floor( $value ) );
		$empty  = ceil( 5.0 - ( $filled + $half ) );

		
		if ( 0.0 == $value ) {
			if ( _is_bool( $options['include_empty'] ) ) {
				$html = str_repeat( $stars['empty'], 10 );
			} else {
				$class = 'not-rated';
				$html  = sprintf( '<small><em>%s</em></small>', __( 'Not rated yet!', 'wpmovielibrary' ) );
			}
		} else if ( 10 == $base ) {
			$_filled = $value * 2;
			$_empty  = 10 - $_filled;
			$title   = "{$_filled}/10 − {$title}";

			$html = str_repeat( $stars['filled'], $_filled ) . str_repeat( $stars['empty'], $_empty );
		} else {
			$title = "{$value}/5 − {$title}";
			$html  = str_repeat( $stars['filled'], $_filled ) . str_repeat( $stars['half'], $_half ) . str_repeat( $stars['empty'], $_empty );
		}

		$html = '<span class="' . $class . '" title="' . $title . '">' . $html . '</span> ';

		/**
		 * Filter generated HTML markup.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $html Stars HTML markup
		 * @param    float     $value Rating value
		 * @param    string    $title Rating title
		 * @param    string    $class CSS classes
		 */
		$html = apply_filters( 'wpmoly/filter/html/rating/stars', $html, $value, $title, $class );
	} else {
		$html = $title;
	}

	if ( _is_bool( $options['is_link'] ) ) {

		$link = '<a href="' . esc_url( get_movie_meta_url( 'rating', $value ) ) . '" title="' . esc_attr( $title ) . '">' . $html . '</a>';

		return apply_filters( 'wpmoly/filter/detail/rating/link', $link );
	}

	/**
	 * Filter final rating stars.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $rating Rating Stars HTML markup
	 * @param    string    $html Rating Stars HTML block
	 * @param    string    $label Rating label
	 * @param    float     $value Rating value
	 * @param    string    $title Rating title
	 */
	return apply_filters( 'wpmoly/filter/detail/rating', $html . $label, $html, $label, $value, $title );
}

/**
 * Format Movies revenue.
 * 
 * Alias for get_formatted_money()
 * 
 * @since    3.0
 * 
 * @param    string    $medias Movie revenue.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_revenue( $revenue, $options = array() ) {

	return get_formatted_money( $revenue );
}

/**
 * Format Movies release date.
 * 
 * If no format is provided, use the format defined in settings. If no such
 * settings can be found, fallback to a standard 'day Month Year' format.
 * 
 * @since    3.0
 * 
 * @param    string    $date Movie release date.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_release_date( $date, $options = array() ) {

	if ( empty( $date ) ) {
		return get_formatted_empty_value( $date );
	}

	// Parse formatting options
	$options = wp_parse_args( (array) $options, array(
		'format'  => '',
		'variant' => '',
		'is_link' => true
	) );

	$variant = (string) $options['variant'];
	if ( 'local_' != $variant ) {
		$variant = '';
	}

	$timestamp  = strtotime( $date );
	$date_parts = array();

	$date_format = (string) $options['format'];
	if ( empty( $date_format ) ) {
		$date_format = wpmoly_o( 'format-date' );
		if ( empty( $date_format ) ) {
			$date_format = 'j F Y';
		}
	}

	if ( 'j F Y' == $date_format ) {
		$date_parts[] = date_i18n( 'j F', $timestamp );
		$date_parts[] = date_i18n( 'Y', $timestamp );
		$date = implode( '&nbsp;', $date_parts );
	} else {
		$date = date_i18n( $date_format, $timestamp );
	}

	/**
	 * Filter release date meta final value.
	 * 
	 * This is used to generate permalinks for dates and can be extended to
	 * post-formatting modifications.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $date Filtered date.
	 * @param    array     $raw_date Unfiltered date
	 * @param    array     $date_parts Date parts, if need be
	 * @param    string    $date_format Date format
	 * @param    int       $timestamp Date UNIX Timestamp
	 * @param    string    $variant Local release date variant
	 */
	return apply_filters( "wpmoly/filter/meta/{variant}release_date", get_formatted_empty_value( $date ), $date, $date_parts, $date_format, $timestamp, $variant );
}

/**
 * Format Movies runtime.
 * 
 * If no format is provided, use the format defined in settings. If no such
 * settings can be found, fallback to a standard 'X h Y min' format.
 * 
 * @since    3.0
 * 
 * @param    string    $runtime Movie runtime.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_runtime( $runtime, $options = array() ) {

	$runtime = intval( $runtime );
	if ( ! $runtime ) {
		return get_formatted_empty_value( __( 'Duration unknown', 'wpmovielibrary' ) );
	}

	// Parse formatting options
	$options = wp_parse_args( (array) $options, array(
		'format' => '',
	) );

	$time_format = (string) $options['format'];
	if ( empty( $time_format ) ) {
		$time_format = wpmoly_o( 'format-time' );
		if ( empty( $time_format ) ) {
			$time_format = 'G \h i \m\i\n';
		}
	}

	$runtime = date_i18n( $time_format, mktime( 0, $runtime ) );
	if ( false !== stripos( $runtime, 'am' ) || false !== stripos( $runtime, 'pm' ) ) {
		$runtime = date_i18n( 'G:i', mktime( 0, $runtime ) );
	}

	return get_formatted_empty_value( $runtime );
}

/**
 * Format Movies languages.
 * 
 * $options['show_text'] and $options['show_icon'] parameters are essentially
 * used by language and subtitles details.
 * 
 * @since    3.0
 * 
 * @param    array     $languages Languages.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_spoken_languages( $languages, $options = array() ) {

	if ( empty( $languages ) ) {
		return get_formatted_empty_value( $languages );
	}

	// Parse formatting options
	$options = wp_parse_args( (array) $options, array(
		'show_text' => true,
		'show_icon' => true,
		'variant'   => '',
	) );

	if ( is_string( $languages ) ) {
		$languages = explode( ',', $languages );
	}

	$variant = (string) $options['variant'];
	if ( 'my_' != $variant ) {
		$variant = '';
	}

	$languages_data = array();

	foreach ( $languages as $key => $language ) {

		$language = get_language( $language );
		$languages_data[ $key ] = $language;

		if ( true !== $options['show_text'] ) {
			$name = '';
		} elseif ( '1' == wpmoly_o( 'translate-languages' ) ) {
			$name = $language->localized_name;
		} else {
			$name = $language->standard_name;
		}

		if ( _is_bool( $options['show_icon'] ) ) {
			$icon = '<span class="wpmoly language iso icon" title="' . esc_attr( $language->localized_name ) . ' (' . esc_attr( $language->standard_name ) . ')">' . esc_attr( $language->code ) . '</span>&nbsp;';
		} else {
			$icon = false;
		}

		/**
		 * Filter single language meta value.
		 * 
		 * This is used to generate permalinks for languages and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $language Filtered language.
		 * @param    array     $language_data Language instance.
		 * @param    string    $icon Language icon string.
		 */
		$languages[ $key ] = apply_filters( 'wpmoly/filter/meta/language/single', $name, $language, $icon, $variant );
	}

	if ( empty( $languages ) ) {
		return get_formatted_empty_value( $languages );
	}

	/**
	 * Filter final languages lists.
	 * 
	 * This is used to generate permalinks for languages and can be extended to
	 * post-formatting modifications.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $languages Filtered languages list.
	 * @param    array     $languages_data Languages data array.
	 * @param    array     $options Formatting options.
	 */
	return apply_filters( 'wpmoly/filter/meta/spoken_languages', implode( ', ', $languages ), $languages_data, $options );
}

/**
 * Format Movies status.
 * 
 * @since    3.0
 * 
 * @param    array     $data Movie statuses.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_status( $statuses, $options = array() ) {

	return get_formatted_movie_detail( 'status', $statuses, $options );
}

/**
 * Format Movies subtitles.
 * 
 * Alias for get_formatted_movie_spoken_languages() since subtitles are languages
 * names.
 * 
 * @since    3.0
 * 
 * @param    array     $subtitles Movie subtitles.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_subtitles( $subtitles, $options = array() ) {

	return get_formatted_movie_spoken_languages( $subtitles, $options );
}

/**
 * Format Movies writers.
 * 
 * @since    3.0
 * 
 * @param    string    $writer Movie writers.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_writer( $writer, $options = array() ) {

	if ( empty( $writer ) ) {
		return get_formatted_empty_value( $writer );
	}

	$writers = explode( ',', $writer );
	foreach ( $writers as $key => $writer ) {

		/**
		 * Filter single writer meta value.
		 * 
		 * This is used to generate permalinks for writers and can be extended to
		 * post-formatting modifications.
		 * 
		 * @since    3.0
		 * 
		 * @param    string    $writer Filtered writer.
		 */
		$writers[ $key ] = apply_filters( 'wpmoly/filter/meta/writer/single', trim( $writer ) );
	}

	/**
	 * Filter final writers lists.
	 * 
	 * @since    3.0
	 * 
	 * @param    string    $writers Filtered writers list.
	 */
	return apply_filters( 'wpmoly/filter/meta/writer', implode( ', ', $writers ) );
}

/**
 * Format Movies release date.
 * 
 * Alias for get_formatted_movie_release_date()
 * 
 * @since    3.0
 * 
 * @param    string    $date Movie release date.
 * @param    array     $options Formatting options.
 * 
 * @return   string    Formatted value
 */
function get_formatted_movie_year( $date, $options = array() ) {

	$options = (array) $options;
	$options['format'] = 'Y';

	return get_formatted_movie_release_date( $date, $options );
}