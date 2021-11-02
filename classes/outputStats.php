<?php

	class outputStats {

		public $stats = [];

		private $tStart = 0;
		private $tEnd = 0;

		private $t1 = 0;
		private $t2 = 0;

		public $header;

		function __construct() {
			$this->tStart =	microtime(true);
		}

		function __destruct() {

			$this->tEnd = microtime(true);
			//echo 'tEnd: ' . $this->tEnd;
			//echo 'tStart: ' . $this->tStart;

			for ($i=1; $i <= array_sum($this->header); $i++) {echo "-";}
			echo "\n";
			foreach ($this->header as $j => $rpad) {echo ucfirst(str_pad($j, $rpad));}
			echo "\n";
			for ($i=1; $i <= array_sum($this->header); $i++) {echo "-";}
			echo "\n";

			// body output
			for ($i=0; $i<(count($this->stats));$i++) {
				foreach ($this->header as $j => $rpad) {
					
					if (!isset($this->stats[$i][$j]))
						$this->stats[$i][$j] = '';

					$x = $this->stats[$i][$j];
					$x = trim($x, '{"}');
					$x = str_replace('":"', ': ', $x);
					$x = str_replace('","', ', ', $x);

					if (strlen($x) >= $rpad) {
						$x = substr($x, 0, $rpad-5) . "...";
					}

					echo str_pad($x, $rpad);
				}
				echo "\n";
			}
			echo "\n";

			for ($i=1; $i <= array_sum($this->header); $i++) {echo "-";}
			echo "\n";
			echo 'Total execution time: ' . number_format($this->tEnd - $this->tStart, 3) . "\n";
		}

		public function setValue($i, $k, $val, $markTime = false) {
			
			if (gettype($val) == 'array') {
				$val = implode(', ', $val);
			}

			if ($markTime) 
				$this->t1 = microtime(true);

			$this->stats[$i][$k] = $val;
		}

		public function setTs($i) {
			$this->t2 =	microtime(true);
			$ts = number_format($this->t2 - $this->t1, 3);
			$this->stats[$i]['time'] = (string)$ts;
		}

		/*
		public function outputStats() {
			print_r($this->stats);
		}
		*/
	}

?>