<?php
namespace AIOWPS\Firewall;

/**
 * Gives us access to our firewall's config
 */
class Config {

	/**
	 * The path to our config file
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Constructs object
	 *
	 * @param string $path
	 */
	public function __construct($path) {
		$this->path = $path;
		$this->init_file();
	}

	/**
	 * Initialise the file if it doesn't exist
	 *
	 * @return void
	 */
	private function init_file() {
		clearstatcache();
		if (!file_exists($this->path)) {
			@file_put_contents($this->path, $this->get_file_content_prefix() . json_encode(array())); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore this
		}
	}

	/**
	 * Get the config file's prefix content. N.B. Some code assumes that this doesn't change, so review all consumers of this method before changing its output.
	 *
	 * @return string
	 */
	private function get_file_content_prefix() {
		return "<?php __halt_compiler();\n";
	}

	/**
	 * Gets the value from the config array
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function get_value($key) {

		$contents = $this->get_contents();

		if (null === $contents) {
			return null;
		}

		if (!isset($contents[$key])) {
			return null;
		}

		return $contents[$key];

	}

	/**
	 * Sets a value in our config array
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return boolean
	 */
	public function set_value($key, $value) {

		$contents = $this->get_contents();

		if (null === $contents) {
			return false;
		}

		$contents[$key] = $value;

		return (false !== @file_put_contents($this->path, $this->get_file_content_prefix() . json_encode($contents), LOCK_EX)); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore this


	}

	/**
	 * Loads the config array from file
	 *
	 * @return string
	 */
	private function get_contents() {
		// __COMPILER_HALT_OFFSET__ doesn't define in a few PHP versions. It's a PHP bug.
		// https://bugs.php.net/bug.php?id=70164
		$contents = @file_get_contents($this->path, false, null, strlen($this->get_file_content_prefix())); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- ignore this

		if (false === $contents) {
			return null;
		}

		if (empty($contents)) {
			return array();
		}
		
		return json_decode($contents, true);
	}

	/**
	 * Returns the path
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->path;
	}

}
