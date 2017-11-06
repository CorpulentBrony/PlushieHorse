<?php
	abstract class PlushCache {
		protected const DEFAULT_PREFIX = "";
		protected const DEFAULT_TTL_SECONDS = 24 /* hours */ * 60 /* minutes in an hour */ * 60 /* seconds in a minute */;

		protected $prefix = self::DEFAULT_PREFIX;
		protected $ttlSeconds = self::DEFAULT_TTL_SECONDS;

		public function __construct(string $prefix = null, int $ttlSeconds = null) {
			$this->prefix = $prefix ?? static::DEFAULT_PREFIX;
			$this->ttlSeconds = $ttlSeconds ?? static::DEFAULT_TTL_SECONDS;
		}

		protected function __get(string $property) {
			// if ($property === "iterator")
			// 	return new \APCUIterator("/^{$this->prefix}/", APC_ITER_VALUE);
			return null;
		}

		public function clear() { apcu_delete($this->iterator); }

		/* for debugging only */
		public function dump() {
			foreach ($this->iterator as $key => $value) {
				var_dump($key);
				var_dump($value);
			}
		}

		public function exists(string $key): bool { return apcu_exists($this->prefix . $key); }
		public function fetch(string $key) { return apcu_fetch($this->prefix . $key); }
		public function store(string $key, $value) { apcu_store($this->prefix . $key, $value, $this->ttlSeconds); }
		public function tryFetch(string $key, callable $generator) { return apcu_entry($this->prefix . $key, $generator, $this->ttlSeconds); }
	}
?>