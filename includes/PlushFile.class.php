<?php
	require_once self::DIR_INCLUDES . "PlushFileCache.class.php";

	class PlushFile {
		private $cache = null;
		private $file = null;
		private $name = "";
		private $title = null;

		public function __construct(string $name) {
			if (!empty($name)) {
				$this->name = $name;
				$this->cache = new PlushFileCache($this->name);
			}
		}

		private function accessFile(string $action): string {
			$result = "";

			if (isset($this->cache) && !empty($result = $this->cache->get($action))) {
				return $result;
			}
			$this->getFile();
			$this->cache->set($action, $result = ($this->file instanceof File) ? strval($this->file->{$action}()) : "");
			return $result;
		}

		public function getFile() {
			$this->title = Title::newFromText($this->name);

			if ($this->title instanceof Title) {
				if ($this->title->getNamespace() != NS_FILE) 
					$this->title = Title::makeTitle(NS_FILE, $this->title->getText());
				$this->file = wfFindFile($this->title);

				if (!($this->file instanceof File))
					$this->file = null;
			}
		}

		public function getHeight(): string {
			return $this->accessFile("getHeight");
		}

		public function getMimeType(): string {
			return $this->accessFile("getMimeType");
		}

		public function getProperty(string $property): string {
			switch ($property) {
				case "height": return $this->getHeight();
				case "mime": return $this->getMimeType();
				case "url": return $this->getUrl();
				case "width": return $this->getWidth();
			}
			return "";
		}

		public function getUrl(): string {
			return $this->accessFile("getCanonicalUrl");
		}

		public function getWidth(): string {
			return $this->accessFile("getWidth");
		}
	}
?>