<?php
	class PlushRandom {
		public static function integer(int $upperLimit): int {
			if ($upperLimit <= 1)
				return 0;
			$numBytes = ceil(log($upperLimit, 2) / 8);
			$randomNum = hexdec(bin2hex(random_bytes($numBytes)));
			return $randomNum / (1 << ($numBytes << 3)) * $upperLimit >> 0;
		}

		private function __construct() {}
	}
?>