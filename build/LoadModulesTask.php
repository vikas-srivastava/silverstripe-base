<?php

/**
 * A phing task to load modules from a specific URL via SVN or git checkouts
 *
 * Passes commands directly to the commandline to actually perform the
 * svn checkout/updates, so you must have these on your path when this
 * runs.
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class LoadModulesTask extends Task {
	/**
	 * Character used to separate the module/revision name from the output path
	 */
	const MODULE_SEPARATOR = ':';

	/**
	 * The file that defines the dependency
	 *
	 * @var String
	 */
	private $file = '';

	/**
	 * Optionally specify a module name
	 *
	 * @var String
	 */
	private $name = '';

	/**
	 * And a module url
	 * @var String
	 */
	private $url = '';

	public function setFile($v)
	{
		$this->file = $v;
	}

	public function setName($v)
	{
		$this->name = $v;
	}

	public function setUrl($v)
	{
		$this->url = $v;
	}


	public function main()
	{
		if ($this->name) {
			$this->loadModule($this->name, $this->url);
		} else {
			// load the items from the dependencies file
			if (!file_exists($this->file)) {
				throw new BuildException("Modules file ".$this->modulesFile." cannot be read");
			}

			$items = file($this->file);
			foreach ($items as $item) {
				$item = trim($item);
				if (strpos($item, '#') === 0) {
					continue;
				}
				$bits = preg_split('/\s+/', $item);
				// skip malformed lines
				if (count($bits) < 2) {
					continue;
				}

				$moduleName = trim($bits[0], '/');
				$svnUrl = trim($bits[1], '/');
				$devBuild = isset($bits[2]) ? $bits[2] != 'false' : true;

				$this->loadModule($moduleName, $svnUrl, $devBuild);
			}
		}
	}

	/**
	 * Actually load the module!
	 *
	 * @param String $moduleName
	 * @param String $svnUrl
	 * @param boolean $devBuild
	 * 			Do we run a dev/build?
	 */
	protected function loadModule($moduleName, $svnUrl, $devBuild = true)
	{
		$git = strrpos($svnUrl, '.git') == (strlen($svnUrl) - 4);
		$branch = 'master';
		$cmd = '';

		if (strpos($moduleName, self::MODULE_SEPARATOR) > 0) {
			$branch = substr($moduleName, strpos($moduleName, self::MODULE_SEPARATOR) + 1);
			$moduleName = substr($moduleName, 0, strpos($moduleName, self::MODULE_SEPARATOR));
		}

		// check the module out if it doesn't exist
		if (!file_exists($moduleName)) {
			echo "Check out $moduleName from $svnUrl\n";
			// check whether it's git or svn
			if ($git) {
				$this->exec("git clone $svnUrl $moduleName");
				if ($branch != 'master') {
					// need to make sure we've pulled from the correct branch also
					$currentDir = getcwd();
					$this->exec("cd $moduleName && git checkout -f -b $branch --track origin/$branch && cd $currentDir");
				}
			} else {
				$revision = '';
				if ($branch != 'master') {
					$revision = " --revision $branch ";
				}
				$this->exec("svn co $revision $svnUrl $moduleName");
			}
			
			// make sure to append it to the .gitignore file
			if (file_exists('.gitignore')) {
				$gitIgnore = file_get_contents('.gitignore');
				if (strpos($gitIgnore, $moduleName) === false) {
					$this->exec("echo $moduleName >> .gitignore");
				}
			}
		} else {
			echo "Updating $moduleName $branch from $svnUrl\n";
			if ($git) {
				$currentDir = getcwd();
				$this->exec("cd $moduleName && git checkout $branch && git pull origin $branch && cd $currentDir");

			} else {
				$revision = '';
				if ($branch != 'master') {
					$revision = " --revision $branch ";
				}
				echo $this->exec("svn up $revision $moduleName");
			}
		}

		if ($devBuild && file_exists('sapphire/cli-script.php')) {
			echo "Running dev/build\n";
			exec('php sapphire/cli-script.php dev/build', $output, $result);
		}
	}

	protected function exec($cmd) {
		passthru($cmd, $return);
		if ($return != 0) {
			throw new BuildException("Command '$cmd' failed");
		}
	}
}

?>
