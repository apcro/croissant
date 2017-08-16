<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 *
 */
namespace Croissant;

/**
 * Map/Reduce for generating keywords from a block of text
 *
 * Allows override or addition of stopwords
 * Allows keywords sets to be set to words only, or include numbers
 *
 */
Class MapReduce {

	private static $_default_stopwords = array('bus', 'room', 'it', 'this', 'then', 'we', 'was', 'i', 'my', 'is', 'be', 'the', 'and', 'of', 'in', 'on', 'a', 'to', 'are', '');
	private static $_stop_words = array('we', 'was', 'i', 'my', 'is', 'be', 'the', 'and', 'of', 'in', 'on', 'a', 'to', 'are', '');
	private static $_wordsonly = true;
	private static $_clean_regex = '/[^a-z]/';

	final private static function _first($data) {
		return empty($data) ? NULL : $data[0];
	}

	final private static function _remainder($data) {
		if(!empty($data)) {
			array_shift($data);
		}
		return $data;
	}

	final private static function _make($word, $data) {
		array_unshift($data, $word);
		return $data;
	}

	final private static function _map($transform, $data) {
		return !empty($data) ? self::_make(self::$transform(self::_first($data)), self::_map($transform, self::_remainder($data))) : array();
	}

	final private static function _reduce($combine, $data, $result) {
		return !empty($data) ? self::$combine(self::_first($data), self::_reduce($combine, self::_remainder($data), $result)) : $result;
	}

	final private static function _sum($left, $right) {
		$keywords = array_merge(array_keys($left), array_keys($right));
		$result = array();
		foreach($keywords as $word) {
			$result[$word] = isset($left[$word]) ? $left[$word] : 0;
			$result[$word] += isset($right[$word]) ? $right[$word] : 0;
		}
		return $result;
	}

	final private static function _count($data) {
		return array_count_values(explode(' ', $data));
	}

	final private static function _setStopWords($words, $override = false) {
		if (!is_array($words)) {
			$words = strtolower(strip_tags($words));
			$words = preg_replace('/[^a-z0-9,]/', ' ', $words);
			$words = preg_replace('/\s\s+/', ' ', $words);
			$words = explode(',', $words);
		}
		if ($override) {
			self::$_stop_words = $words;
		} else {
			self::$_stop_words = array_merge(self::$_stop_words, $words);
		}
	}

	final public static function Configure($wordsonly, $stopwords = array(), $override = false) {
		self::$_wordsonly = $wordsonly;
		if ($wordsonly === true) {
			self::$_clean_regex = '/[^a-z]/';
		} else {
			self::$_clean_regex = '/[^a-z0-9]/';
		}
		if (!empty($stopwords)) {
			self::_setStopWords($stopwords, $override);
		} else {
			self::_setStopWords(self::$_default_stopwords, true);
		}
	}

	final public static function Process($data, $limit = 10, $wordsize = 3) {
		$data = strtolower(strip_tags($data));
		$data = preg_replace(self::$_clean_regex, ' ', $data);
		$data = preg_replace('/\s\s+/', ' ', $data);
		$data = preg_replace("/&#?[a-z0-9]+;/i","",$data);
		$data = explode(' ', $data);
		// xdebug messes with this routine - if it's installed, we need to override an INI setting
		$max_level = ini_get('xdebug.max_nesting_level');
		if ($max_level) {
			ini_set('xdebug.max_nesting_level', count($data)+100);
		}
		$data = array_diff($data, self::$_stop_words);
		// remove short words (< $wordsize chars)
		foreach ($data as $k => $v) {
			if (strlen($v) <= $wordsize) {
				unset($data[$k]);
			}
		}
		$keywords = self::_reduce('_sum', self::_map('_count', $data), array());
		arsort($keywords);
		if ($max_level) {
			ini_set('xdebug.max_nesting_level', $max_level);
		}
		return array_keys(array_slice($keywords, 0, $limit));
	}

}