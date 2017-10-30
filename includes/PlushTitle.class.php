<?php
	require_once self::DIR_INCLUDES . "PlushTitleCache.class.php";

	class PlushTitle {
		private $cache = null;
		private $name = "";
		private $firstRevision = null;
		private $title = null;

		public function __construct(string $name) {
			if (!empty($name)) {
				$this->name = $name;
				$this->cache = new PlushTitleCache($this->name);
			}
		}

		public function getOriginalAuthor(): string {
			if ($this->firstRevision instanceof Revision)
				return $this->firstRevision->getUserText(Revision::RAW);
			return "";
		}

		public function getProperty(string $property): string {
			switch ($property) {
				case "original author": return $this->updateCache($property, [$this, "getOriginalAuthor"]);
				case "published date": return $this->updateCache($property, [$this, "getPublishedDate"]);
			}
			return "";
		}

		public function getRevision() {
			$this->title = Title::newFromText($this->name);

			if (!($this->title instanceof Title))
				$this->title = null;
			else
				$this->firstRevision = $this->title->getFirstRevision();

			if (!($this->firstRevision instanceof Revision))
				$this->firstRevision = null;
		}

		public function getPublishedDate(): string {
			if ($this->firstRevision instanceof Revision)
				return wfTimestamp(TS_ISO_8601, $this->firstRevision->getTimestamp());
			return "";
		}

		private function updateCache(string $property, callable $newValueGenerator): string {
			$result = "";

			if (isset($this->cache) && !empty($result = $this->cache->get($property))) {
				return $result;
			}
			$this->getRevision();
			$this->cache->set($property, $result = ($this->title instanceof Title) ? strval($newValueGenerator()) : "");
			return $result;
		}
	}
?>