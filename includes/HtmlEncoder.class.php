<?php
	require_once PlushieHorse::DIR_INCLUDES . "HtmlTranscoder.class.php";

	class HtmlEncoder extends HtmlTranscoder {
		public function __construct(string $property, string $data, bool $encodePotentialTags = true) {
			$this->data = ($encodePotentialTags) ? parent::gtltencode($data) : $data;
			$this->hash = hash("md5", $this->data);
			$this->property = $property;
		}

		public function __toString(): string {
			return PHP_EOL . "<!-- " . json_encode($this) . " -->" . PHP_EOL;
		}
	}
?>