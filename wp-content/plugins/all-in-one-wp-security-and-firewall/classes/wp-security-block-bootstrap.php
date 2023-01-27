<?php
if (!defined('ABSPATH')) {
	exit; //Exit if accessed directly
}

/**
 * Firewall bootstrap file content block.
 */
class AIOWPSecurity_Block_Bootstrap extends AIOWPSecurity_Block_File {

	/**
	 * Keeps track of our bootstrap file version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Inserts our code into our bootstrap file.
	 *
	 * @return boolean|WP_Error
	 */
	public function insert_contents() {
		$info = pathinfo($this->file_path);
	
		if (!isset($info['dirname'])) {
			return new WP_Error(
				'file_no_directory',
				'No directory has been set',
				$this->file_path
			);
		}

		if (!is_writable($info['dirname'])) {
			return new WP_Error(
				'file_directory_not_writable',
				'The directory has incorrect write permissions. Please double check its permissions and try again.',
				$info['dirname']
			);
		}

		return (false !== @file_put_contents($this->file_path, $this->get_contents())); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore this
	}

	/**
	 * Checks whether the bootstrap contents are valid
	 *
	 * @param string $contents
	 * @return boolean
	 */
	protected function is_content_valid($contents) {

		//The regexes we extract the paths from
		$regexes = array('/file_exists\(\'?(.*)\'?\)/isU', '/include_once\(\'?(.*)\'?\)/isU');
		$firewall_path_str = $this->get_firewall_path_str();
		
		foreach ($regexes as $regex) {
			$matches = array();
			$result  = preg_match($regex, $contents, $matches);
		
			if (empty($matches[1]) || false === $result) {
				continue;
			}
					
			if ($firewall_path_str !== $matches[1]) {
				return false;
			}
		
		}
		
		return true;
	}

	/**
	 * Get the firewall path string that contains "__DIR__" for home dir, if plugin dir isn't a symbolic link..
	 *
	 * @return string The firewall path string.
	 */
	private function get_firewall_path_str() {
		$firewall_path = AIOWPSecurity_Utility_Firewall::get_firewall_path();
		$firewall_path_str = $this->get_path_str_for_given_absolute_path($firewall_path);
		return $firewall_path_str;
	}

	/**
	 * Get path string to write bootstrap file from given path.
	 *
	 * @param string $path a path that we want to write to the bootstrap file.
	 * @return string The path that can be written in the bootstrap file.
	 */
	private function get_path_str_for_given_absolute_path($path) {
		$home_path = AIOWPSecurity_Utility_File::get_home_path();
		// If the plugin is symbolic linked, then the plugin's firewall path is not started with home_path.
		$path_str = (0 === strpos($path, $home_path)) ? "__DIR__.'/".substr($path, strlen($home_path))."'" : "'".$path."'";
		return $path_str;
	}

	/**
	 * Get the firewall rules path string that contains "__DIR__" for home dir, if plugin dir isn't a symbolic link.
	 *
	 * @return string The firewall rule path string.
	 */
	private function get_firewall_rules_path_str() {
		$firewall_rules_path  = AIOWPSecurity_Utility_Firewall::get_firewall_rules_path();
		$firewall_rules_path_str = $this->get_path_str_for_given_absolute_path($firewall_rules_path);
		return $firewall_rules_path_str;
	}

	/**
	 * The regex pattern that demarcates our contents
	 *
	 * @return string
	 */
	protected function get_regex_pattern() {
		 return '#// Begin AIOWPSEC Firewall(.*)// End AIOWPSEC Firewall#isU';
	}

	/**
	 * Bootstrap file contents to insert
	 *
	 * @return string
	 */
	public function get_contents() {
		$firewall_path_str = $this->get_firewall_path_str();
		$firewall_rules_path_str  = $this->get_firewall_rules_path_str();

		$code  = "<?php\n";
		$code .= $this->get_warning_message();
	
		$directive = AIOWPSecurity_Utility_Firewall::get_already_set_directive();

		if (!empty($directive) && $directive !== $this->file_path) {
			$code .= "// Previously set auto_prepend_file\n";
			$code .= "if (file_exists('{$directive}')) {\n";
			$code .= "\tinclude_once('{$directive}');\n";
			$code .= "}\n";
		}

		$code .= "\$aiowps_firewall_rules_path = {$firewall_rules_path_str};\n\n";
		$code .= "// Begin AIOWPSEC Firewall\n";
		$code .= "if (file_exists({$firewall_path_str})) {\n";
		$code .= "\tinclude_once({$firewall_path_str});\n";
		$code .= "}\n";
		$code .= "// End AIOWPSEC Firewall\n";

		return $code;
	}

	/**
	 * Gets our warning message for users
	 *
	 * @return string
	 */
	protected function get_warning_message() {

		$warning  = "/**	\n";
		$warning .= " * @version {$this->version}\n";
		$warning .= " * WARNING: Please do not delete this file.\n";
		$warning .= " * \n";
		$warning .= " * This will cause PHP to throw a fatal error and render your site unusable.\n";
		$warning .= " * \n";
		$warning .= " * To safely delete this file, please check both your .user.ini file and your php.ini file and ensure this file is not set in the auto_prepend_file directive.\n";
		$warning .= " * \n";
		$warning .= " * Please ask your web hosting provider if you need guidance with executing the aforementioned steps.\n";
		$warning .= " */\n";

		return $warning;
	}
}
