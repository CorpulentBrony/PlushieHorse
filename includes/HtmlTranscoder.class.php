<?php
	abstract class HtmlTranscoder implements \Ds\Hashable {
		public $context = PlushieHorse::CONTEXT;
		public $data = "";
		public $hash = "";
		public $property = "";

		abstract public function __toString(): string;

		public static function gtltencode(string $string): string {
			return str_replace("<", "&lt;", str_replace(">", "&gt;", $string));
		}

		public function equals($obj): bool {
			if (!isset($obj, $obj->context, $obj->data, $obj->hash, $obj->property))
				return false;
			return $this->context === $obj->context && $this->data === $obj->data && $this->hash === $obj->hash && $this->property === $obj->property;
		}

		public function hash() {
			return $this->hash;
		}
	}
?>