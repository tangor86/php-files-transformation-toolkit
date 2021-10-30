<?php

	class outputStats {

		public $stats = [];

		private $tStart = 0;
		private $tEnd = 0;

		private $t1 = 0;
		private $t2 = 0;

		private $rpad = 25;

		public $header;

		function __construct() {
			$this->tStart =	microtime(true);
		}

		function __destruct() {

			$this->tEnd = microtime(true);
			//echo 'tEnd: ' . $this->tEnd;
			//echo 'tStart: ' . $this->tStart;

			for ($i=1; $i<=(count($this->header)*$this->rpad);$i++) {echo "-";}
			echo "\n";
			for ($i=0; $i<(count($this->header));$i++) {echo ucfirst(str_pad($this->header[$i], $this->rpad));}
			echo "\n";
			for ($i=1; $i<=(count($this->header)*$this->rpad);$i++) {echo "-";}
			echo "\n";

			// body output
			for ($i=0; $i<(count($this->stats));$i++) {
				for ($j=0; $j<(count($this->header));$j++) {
					
					if (!isset($this->stats[$i][$this->header[$j]])) 
						$this->stats[$i][$this->header[$j]] = '';

					echo str_pad($this->stats[$i][$this->header[$j]], $this->rpad);
				}
				echo "\n";
			}
			echo "\n";

			for ($i=1; $i<=(count($this->header)*$this->rpad);$i++) {echo "-";}
			echo "\n";
			echo 'Total execution time: ' . number_format($this->tEnd - $this->tStart, 3) . "\n";
		}

		public function setValue($i, $k, $val) {

			if (gettype($val) == 'array') {
				$val = implode(', ', $val);
			}

			$this->t1 =	microtime(true);
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