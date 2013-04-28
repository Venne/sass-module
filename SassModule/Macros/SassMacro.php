<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace SassModule\Macros;

use Venne;

/**
 * @author Pavlína Ostrá <pave.pr@gmail.com>
 */
class SassMacro extends \Nette\Latte\Macros\MacroSet
{

	/** @var Venne\Module\Helpers */
	protected $moduleHelpers;

	/** @var string */
	protected $wwwCacheDir;

	/** @var string */
	protected $wwwDir;

	/** @var string */
	protected $debugMode = FALSE;


	public function setModuleHelpers($moduleHelpers)
	{
		$this->moduleHelpers = $moduleHelpers;
	}


	public function setWwwCacheDir($wwwCacheDir)
	{
		$this->wwwCacheDir = $wwwCacheDir;
	}


	public function setWwwDir($wwwDir)
	{
		$this->wwwDir = $wwwDir;
	}


	public function setDebugMode($debugMode)
	{
		$this->debugMode = $debugMode;
	}


	public function filter(\Nette\Latte\MacroNode $node, \Nette\Latte\PhpWriter $writer)
	{
		$path = $node->tokenizer->fetchWord();
		$params = $writer->formatArray();
		$path = $this->moduleHelpers->expandPath($path, 'Resources/public');

		if (!$this->debugMode) {
			$sass = new \SassParser();

			$file = new \SplFileInfo($path);
			$targetFile = $file->getBasename() . '-' . md5($path . filemtime($path)) . '.css';
			$targetDir = $this->wwwCacheDir . '/sass';
			$target = $targetDir . '/' . $targetFile;
			$targetUrl = substr($target, strlen($this->wwwDir));

			if (!file_exists($targetDir)) {
				umask(0000);
				mkdir($targetDir, 0777, true);
			}

			$css = $sass->toCss($path);
			file_put_contents($target, $css);

			return ('$control->getPresenter()->getContext()->getService("assets.assetManager")->addStylesheet("' . $targetUrl . '", ' . $params . '); ');
		} else {
			return ('
				$_sass_file = new \SplFileInfo("' . $path . '");
				$_sass_targetFile = $_sass_file->getBasename() .  \'-\' . md5(\'' . $path . '\') . \'-\' . md5(\'' . $path . '\' . filemtime("' . $path . '")) . \'.css\';
				$_sass_targetDir = \'' . $this->wwwCacheDir . '/sass\';
				$_sass_target = $_sass_targetDir  . \'/\' . $_sass_targetFile;
				$_sass_targetUrl = substr($_sass_target, strlen(\'' . $this->wwwDir . '\'));

				if (!file_exists($_sass_target)) {
					$_sass = new \SassParser();
					if (!file_exists($_sass_targetDir)) {
						umask(0000);
						mkdir($_sass_targetDir, 0777, true);
					}

					// Remove old files
					foreach (\Nette\Utils\Finder::findFiles($_sass_file->getBasename() . \'-\' . md5(\'' . $path . '\') . \'-*\')->from($_sass_targetDir) as $_sass_old) {
						unlink($_sass_old->getPathname());
					}

					$_sass_css = $_sass->toCss(\'' . $path . '\', $_sass_target);
					file_put_contents($_sass_target, $_sass_css);	
				}

				$control->getPresenter()->getContext()->getService("assets.assetManager")->addStylesheet($_sass_targetUrl, ' . $params . ');
			');
		}
	}


	public static function install(\Nette\Latte\Compiler $compiler, Venne\Module\Helpers $moduleHelpers = NULL, $wwwCacheDir = NULL, $wwwDir = NULL, $debugMode = NULL)
	{
		$me = new SassMacro($compiler);

		$me->moduleHelpers = $moduleHelpers;
		$me->wwwCacheDir = $wwwCacheDir;
		$me->wwwDir = $wwwDir;
		$me->debugMode = (bool) $debugMode;

		$me->addMacro('sass', array($me, "filter"));
	}

}

