<?php
	require_once PlushieHorse::DIR_INCLUDES . "HtmlTranscoder.class.php";

	class HtmlDecoder extends HtmlTranscoder {
		public function __construct(string $item) {
			$item = preg_replace("/<p>|<\/p>|<br \/>|<!-- | -->/", "", $item);
			$obj = json_decode($item);

			if (is_null($obj)) {
				$this->data = PlushieHorse::error(wfMessage("plushiehorse-error-decoding-value", $this->context, $item)->inContentLanguage()->plain());
				return;
			}
			$this->data = $obj->data;
			$this->hash = $obj->hash;
			$this->property = $obj->property;
			unset($obj);
			$hash = hash("md5", $this->data);

			if ($hash !== $this->hash) {
				$this->data = PlushieHorse::error(wfMessage("plushiehorse-error-hash-mismatch", $this->context, $this->property, $this->data)->inContentLanguage()->plain());
				return;
			}
		}

		public function __toString(): string {
			return $this->data;
		}
	}
?>