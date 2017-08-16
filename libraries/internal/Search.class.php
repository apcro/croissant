<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Search extends Core {
	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Find results
	 *
	 * By default, terms are applied as AND. Set $solrparams['alltids'] = 'OR' to swap this behaviour
	 */
	static function Find($keys, $types = array(), $terms = array(), $start=0, $limit=10, $solrparams = array()) {
		$terms = array_unique($terms);
		$types = array_unique($types);

		// this is a custom extension for the $solrparams array
		if (isset($solrparams['geolocate']) && strlen($keys) > 6) {
			unset($solrparams['geolocate']);
			$latlon = Location::Geolocate($keys);
			if (!empty($latlon)) {
				// we need to do a geosearch as we have a lat/lon, so extend the search to include the area
				// top left
				$tl = Location::GetDistanceCoords($latlon['lat'], $latlon['lon'], 315, 2, 'm', true);
				// bottom right
				$br = Location::GetDistanceCoords($latlon['lat'], $latlon['lon'], 135, 2, 'm', true);
			} else {
				$br['lat'] = $lat[0];
				$br['lon'] = $lon[1];
				$tl['lat'] = $lon[0];
				$tl['lon'] = $lat[1];
			}
			// set up the search bounds
			$params['latitude'] = '['.$br['lat'].' TO '.$tl['lat'].']';
			$params['longitude'] = '['.$tl['lon'].' TO '.$br['lon'].']';

		}
		// use Solr
		$params = array(	'types' => $types,
							'terms' => $terms);
		$params = array_merge($params, $solrparams);
		// dump($params);
		//get search data
		$data = Solr::Search($keys, $start, $limit, $params);
// 		dump($data);die();

		if ($data) {
			// now process into something that the page expects
			foreach($data->response->docs as $rk => $result) {
				$row = (array)$result;
				// ARGH! Solr returns data with HTML entities encoded!!
				// replace title & teaser if they're in the highlighter fragment array
				if (isset($data->highlighting->$row['id']->title)) {
					$row['title'] = html_entity_decode($data->highlighting->{$row['id']}->title[0]);
				} else {
					$row['title'] = html_entity_decode($row['title']);
				}

				if (isset($data->highlighting->$row['id']->teaser)) {
					$row['teaser'] = html_entity_decode($data->highlighting->{$row['id']}->teaser[0]);
				} else {
					$row['teaser'] = html_entity_decode($row['teaser']);
				}

				if (isset($data->highlighting->$row['id']->description)) {
					$row['synopsis'] = html_entity_decode($data->highlighting->{$row['id']}->description[0]);
				} else {
					$row['synopsis'] = html_entity_decode($row['synopsis']);
				}

				$row['type'] = $result->content_type[0];

				// get thumb sizes
				// we're going to assume they exist
				$row['sizes'] = array('600' => 1, '175' => 1, '122' => 1, '90' => 1, '70' => 1, 'capture_image' => $result->capture_image);
				$row['programme_title'] = $row['title'];
				$row['programme_teaser'] = $row['teaser'];
				// get series data
				if (isset($result->series_cid)) {
					$row['series']['name'] = $row['series_name'];
					$row['series']['label'] = $row['series_label'];
					if ($result->content_type[0] == 'series' && !empty($row['synopsis'])) {
						$row['programme_teaser'] = $row['synopsis'];
						$row['teaser'] = $row['synopsis'];
					}
				}

				$row['comment_count'] = $row['commentcount'];

				// change legacy durations
				if ($row['duration']) {
					if (strstr($row['duration'], ':')) {
						$dur = explode(':', $row['duration']);
						$row['duration'] = $dur[0];
					}
				}

				$row['title'] = htmlspecialchars_decode($row['title']);
				$row['title'] = htmlspecialchars_decode($row['title']);

				$row['description'] = htmlspecialchars_decode($row['description']);
				$row['description'] = htmlspecialchars_decode($row['description']);
				$response['result'][] = $row;
			}
			if (isset($data->facet_counts)) {
				$tidcounts = (array)$data->facet_counts->facet_fields->tid;
				$vocabs = explode(',', VOCABULARIES);
				$taxonomy = array();
				foreach($vocabs as $vocab) {
					$taxonomy = array_merge($taxonomy, Taxonomy::GetAllTermsByVocabulary($vocab));
				}
				foreach($taxonomy as $key => $value) {
					$taxonomy[$value['tid']] = $value;
					unset($taxonomy[$key]);
				}
				$tidsc=array();
				foreach($tidcounts as $k => $v) {
					if (isset($taxonomy[$k])) {
						$tidsc[$k]['name'] = $taxonomy[$k]['name'];
						$tidsc[$k]['label'] = $taxonomy[$k]['label'];
						$tidsc[$k]['count'] = $v;
					}
				}
				$response['tidcounts'] = $tidsc;
			}
			$response['totalvideos'] = $data->response->numFound;
			$response['statusCode'] = 0;
		} else {
			$response['statusCode'] = 1;
			$response['errorData'] = 'Exception thrown by Solr';
		}
		return $response;
	}
}