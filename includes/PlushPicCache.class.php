<?php
	require_once self::DIR_INCLUDES . "PlushCache.class.php";

	class PlushPicCache extends PlushCache {
		protected const DEFAULT_PREFIX = "DERPIBOORU_PLUSHPIC_PAGE_";

		private const PAGE_TOTAL_SUFFIX = "TOTAL";

		private static $cache;

		public static function clearPics() { self::getCache()->clear(); }
		public static function dumpPics() { self::getCache()->dump(); }
		public static function fetchPage(int $pageNumber) { return self::getCache()->fetch(strval($pageNumber)); }
		public static function fetchTotal() { return self::getCache()->fetch(self::PAGE_TOTAL_SUFFIX); }
		private static function getCache(): self { return self::$cache ?? self::$cache = new self(); }
		public static function storePage(int $pageNumber, stdClass $page) { self::getCache()->store(strval($pageNumber), $page); }
		public static function storeTotal(int $numberPages) { self::getCache()->store(self::PAGE_TOTAL_SUFFIX, $numberPages); }
	}
	// class PlushPicCache {
	// 	private const NAME_PREFIX = "DERPIBOORU_PLUSHPIC_PAGE_";
	// 	private const TTL_SECONDS = 24 /* hours */ * 60 /* minutes in an hour */ * 60 /* seconds in a minute */;

	// 	private const NAME_PAGE_TOTAL = self::NAME_PREFIX . "TOTAL";
	// 	private const NAME_PREFIX_REGEX = "/^" . self::NAME_PREFIX . "/";

	// 	public static function clear() {
	// 		apcu_delete(new \APCUIterator(self::NAME_PREFIX_REGEX, APC_ITER_VALUE));
	// 	}

	// 	/* for debugging only */
	// 	public static function dump() {
	// 		$cachedKeys = new \APCUIterator(self::NAME_PREFIX_REGEX, APC_ITER_VALUE);

	// 		foreach ($cachedKeys as $key => $value) {
	// 			var_dump($key);
	// 			var_dump($value);
	// 		}
	// 	}

	// 	private static function fetch(string $key) {
	// 		return apcu_fetch($key);
	// 	}

	// 	public static function fetchPage(int $pageNumber) {
	// 		return self::fetch(self::NAME_PREFIX . strval($pageNumber));
	// 	}

	// 	public static function fetchTotal() {
	// 		return self::fetch(self::NAME_PAGE_TOTAL);
	// 	}

	// 	private static function store(string $key, $value) {
	// 		apcu_store($key, $value, self::TTL_SECONDS);
	// 	}

	// 	public static function storePage(int $pageNumber, stdClass $page) {
	// 		self::store(self::NAME_PREFIX . strval($pageNumber), $page);
	// 	}

	// 	public static function storeTotal(int $numberPages) {
	// 		self::store(self::NAME_PAGE_TOTAL, $numberPages);
	// 	}

	// 	private function __construct() {}
	// }
?>