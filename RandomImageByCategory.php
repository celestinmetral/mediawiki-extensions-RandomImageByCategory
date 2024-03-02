<?php
/**
 * RandomImageByCategory extension
 * Usage example: {{#randomimagebycategory: category=ImageVitrine | size=300px | position=centré| vignette}}
 * Supported parameters: position, size, category, position
 *
 * @file
 * @ingroup Extensions
 * @author Aaron Wright <aaron.wright@gmail.com>
 * @author David Pean <david.pean@gmail.com>
 * @author Jack Phoenix
 * @author Célestin Métral
 * @link https://www.mediawiki.org/wiki/Extension:RandomImageByCategory Documentation
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;

class RandomImageByCategory {

	public static function registerTag( Parser $parser ) {
		$parser->setFunctionHook( 'randomimagebycategory', [ self::class, 'getRandomImage' ] );
	}

	public static function getRandomImage( Parser $parser) {
		$parser->getOutput()->updateCacheExpiry( 0 );

		$options = extractOptions( array_slice( func_get_args(), 1 ) );

		$category = ( isset( $options["category"] ) ) ? $options["category"] : 'ImageVitrine';
		$size = ( isset ($options["size"]) ) ? $options["size"] : "300px";
		$position = ( isset ($options["position"]) ) ? $options["position"] : "centré";
		$vignette = $options["vignette"] ?  "|vignette" : "";

		# Limitation du nombre d’image récupérées au numéro du jour puisque nous allons prendre l’image correspondant au numéro du jour
		$limit = date('j');

		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$key = $cache->makeKey( 'image', 'random', $limit, str_replace( ' ', '', $categories ) );
		$data = $cache->get( $key );
		$image_list = [];

		if ( !$data ) {
			wfDebug( "Getting random image list from DB\n" );
			$ctg = $parser->replaceVariables( $category );
			$ctg = $parser->getStripState()->unstripBoth( $ctg );
			$ctg = str_replace( "\,", '#comma#', $ctg );
			$aCat = explode( ',', $ctg );

			$category_match = [];
			foreach ( $aCat as $sCat ) {
				if ( $sCat != '' ) {
					$category_match[] = Title::newFromText( trim( str_replace( '#comma#', ',', $sCat ) ) )->getDBkey();
				}
			}

			if ( count( $category_match ) == 0 ) {
				return '';
			}

			$params['ORDER BY'] = 'page_id';
			if ( !empty( $limit ) ) {
				$params['LIMIT'] = $limit;
			}

			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				[ 'page', 'categorylinks' ],
				[ 'page_title' ],
				[ 'cl_to' => $category_match, 'page_namespace' => NS_FILE ],
				__METHOD__,
				$params,
				[ 'categorylinks' => [ 'INNER JOIN', 'cl_from=page_id' ] ]
			);
			$image_list = [];
			foreach ( $res as $row ) {
				$image_list[] = $row->page_title;
			}
			$cache->set( $key, $image_list, 60 * 15 );
		} else {
			$image_list = $data;
			wfDebug( "Cache hit for random image list\n" );
		}

		$random_image = '';
		$thumbnail = '';
		# Récupération de l’image correspondant au numéro du jour (avec un modulo s’il n’y a pas assez d’images)
		if ( count( $image_list ) > 0 ) {
			$random_image = $image_list[ date('j') % sizeof($image_list) ];
			#$random_image = $image_list[ array_rand( $image_list, 1 ) ];
		}

		if ( $random_image ) {
			$image_title = Title::makeTitle( NS_FILE, $random_image );
			#$render_image = $services->getRepoGroup()->findFile( $random_image );
			#$thumb_image = $render_image->transform( [ 'width' => $width ] );
			#$thumbnail = "<a href=\"" . htmlspecialchars( $image_title->getFullURL() ) . "\">{$thumb_image->toHtml()}</a>";
			#$thumbnail = htmlspecialchars( $image_title->getPartialURL() );
			#$thumbnail = "[[Fichier:" . htmlspecialchars( $image_title->getPartialURL() ). "|". $position ."|". $size ."|alt=L’image du jour : ".$image_title . $vignette ."|link=".  htmlspecialchars( $image_title->getFullURL() ) ."|]]bla";
			$thumbnail = "{{ImageVitrine|titre=".htmlspecialchars( $image_title->getPartialURL() )."|position=". $position ."|taille=". $size ."|lien=".htmlspecialchars( $image_title->getFullURL() ) ."}}<div style='text-align: center;'>{{".$image_title."}}</div>";

		}

		return array( $parser->recursiveTagParse($thumbnail), 'noparse' => false, 'isHTML' => true );
		#return $thumbnail;
	}
}


/**
 * Converts an array of values in form [0] => "name=value"
 * into a real associative array in form [name] => value
 * If no = is provided, true is assumed like this: [name] => true
 *
 * @param array string $options
 * @return array $results
 */
function extractOptions( array $options ) {
	$results = [];
	foreach ( $options as $option ) {
		$pair = array_map( 'trim', explode( '=', $option, 2 ) );
		if ( count( $pair ) === 2 ) {
			$results[ $pair[0] ] = $pair[1];
		}
		if ( count( $pair ) === 1 ) {
			$results[ $pair[0] ] = true;
		}
	}
	return $results;
}
