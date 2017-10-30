<?php
	require_once self::DIR_INCLUDES . "PlushCache.class.php";
	
	class PlushTitleCache extends PlushCache {
		protected const DEFAULT_PREFIX = "PLUSHTITLE_";

		private $cache = null;
		private $name = "";

		public function __construct(string $name) {
			parent::__construct();
			$this->name = $name;
			$this->cache = parent::fetch($this->name);
		}

		public function get(string $property): string { return ($this->cache === false || empty($this->cache[$property])) ? "" : $this->cache[$property]; }

		public function set(string $property, string $value) {
			if ($this->cache === false)
				$this->cache = [];
			$this->cache[$property] = $value;
			parent::store($this->name, $this->cache);
		}
	}
?>