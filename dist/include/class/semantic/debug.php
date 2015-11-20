<?php
/**
 * Debug Class
 */

namespace semantic;

class debug extends abstract_base {
	public $runtime_checkpoints;
	public $active;
	public $shutdown;
	
	public function __construct() {
		// Setup globals and query vars
		global $debug;
		$debug = $this;
		set_query_var('debug',  $this);
		
		// Setup the shutdown class
		$this->shutdown = new shutdown;
		
		$this->runtime_checkpoints = array();
		$this->active = FALSE;
		if (is_user_logged_in() && current_user_can('manage_options')) {
			$this->active = TRUE;
		}
		if (!empty($_SERVER['REQUEST_TIME_FLOAT'])) {
			$this->runtime_checkpoints[] = array(
				'name' => '[PHP] Sent Request To Server',
				'time' => $_SERVER['REQUEST_TIME_FLOAT']
			);
		}
		if (defined('PHP_START_TIME')) {
			$this->runtime_checkpoints[] = array(
				'name' => '[PHP] Started Server-Side Execution',
				'time' => PHP_START_TIME
			);
		}
		$this->shutdown->register('debug_output', array($this, '_runtime_on_shutdown'));
		parent::__construct();
	}
	
	public function runtime_checkpoint($name = '') {
		$this->runtime_checkpoints[] = array(
			'name' => (string) $name,
			'time' => microtime(TRUE)
		);
	}
	
	public function runtime_print_js_var() {
		$checkpoints   = $this->runtime_checkpoints;
		$first         = array_shift($checkpoints);
		$previous_time = $first['time'];
		echo '<script type="text/javascript">';
		echo 'var debug_checkpoints =';
		foreach ($checkpoints as &$checkpoint) {
			$checkpoint['offset']    = ($checkpoint['time'] - $first['time']) * 1000;
			$checkpoint['prev_diff'] = ($checkpoint['time'] - $previous_time) * 1000;
			$previous_time = $checkpoint['time'];
		}
		echo json_encode($checkpoints);
		echo '</script>';
	}
	
	public function runtime_print_js_log() {
		$checkpoints   = $this->runtime_checkpoints;
		$first         = array_shift($checkpoints);
		$previous_time = $first['time'];
		echo '<script type="text/javascript">';
		printf('console.log("%1$s {%2$s}");'.PHP_EOL , addslashes($first['name']), $first['time']);
		foreach ($checkpoints as $checkpoint) {
			printf(
				'console.log("%1$s {%2$s/+%3$s}");'.PHP_EOL,
				addslashes($checkpoint['name']),
				round(($checkpoint['time'] - $first['time']) * 1000, 2).'ms',
				round(($checkpoint['time'] - $previous_time) * 1000, 2).'ms'
			);
			$previous_time = $checkpoint['time'];
		}
		printf('console.log("[PHP] Memory Peak: %1$s MiB");', round(memory_get_peak_usage()/1024/1024, 3));
		echo '</script>';
	}
	
	public function _runtime_on_shutdown() {
		global $wp_styles, $wp_scripts;
		if (!empty($wp_styles->queue)) {
			foreach ($wp_styles->queue as $style) {
				if (wp_style_is($style, 'done')) {
					$this->runtime_checkpoint('[Theme] Style "'.$style.'" Was Printed');
				}
			};
		}
		if (!empty($wp_scripts->queue)) {
			foreach ($wp_scripts->queue as $script) {
				if (wp_style_is($script, 'done')) {
					$this->runtime_checkpoint('[Theme] Script "'.$script.'" Was Printed');
				}
			};
		}
		$this->runtime_checkpoint('[PHP] Stopped Server-Side Execution');
		if ($this->active) {
			if (php_sapi_name() != 'cli') {
				$this->runtime_print_js_log();
			}
		}
	}
}
