<?php
	require_once self::DIR_INCLUDES . "PlushCache.class.php";

	class PlushmancerListCache extends PlushCache {
		protected const DEFAULT_PREFIX = "PLUSHMANCERLIST_";

		public function tryFetchCheck(string $key, callable $generatorIfNotExistsOrStale, int $checkValue, string $objectCheckValueName = "hash", string $objectValueName = "value") {
			if (parent::exists($key)) {
				$object = parent::fetch($key);

				if ($object->{$objectCheckValueName} === $checkValue)
					return $object->{$objectValueName};
			}
			$object = new \stdClass();
			$object->{$objectCheckValueName} = $checkValue;
			$object->{$objectValueName} = $generatorIfNotExistsOrStale();
			parent::store($key, $object);
			return $object->{$objectValueName};
		}
	}
?>