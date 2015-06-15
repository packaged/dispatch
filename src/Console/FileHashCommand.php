<?php
namespace Packaged\Dispatch\Console;

use Packaged\Dispatch\AssetResponse;
use Packaged\Dispatch\ResourceGenerator;
use Packaged\Helpers\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FileHashCommand extends Command
{
  protected $_projectBase;

  /**
   * Configure the command options.
   *
   * @return void
   */
  protected function configure()
  {
    $this->setName('filehash');
    $this->setDescription('Create a file hash of all your project assets');
    $this->addArgument(
      'base',
      InputArgument::OPTIONAL,
      'The base path to build resources from'
    );
    $this->addOption(
      'output',
      'o',
      InputOption::VALUE_OPTIONAL,
      'Location of the output file',
      'conf/dispatch.filehash.ini'
    );
  }

  public function setDefaultBasePath($base)
  {
    $this->_projectBase = $base;
    return $this;
  }

  /**
   * Execute the command.
   *
   * @param  InputInterface  $input
   * @param  OutputInterface $output
   *
   * @return void
   */
  public function execute(InputInterface $input, OutputInterface $output)
  {
    $baseDir = $input->getArgument('base');
    if($baseDir === null)
    {
      $baseDir = $this->_projectBase;
    }

    $baseDir = rtrim($baseDir, '/') . '/';

    $extensions = AssetResponse::getExtensions();
    $pattern = '*.' . implode(',*.', $extensions);
    $fileList = $this->globRecursive(
      $baseDir . '{' . $pattern . '}',
      GLOB_BRACE
    );

    $hashMap = [];
    $data = '';
    foreach($fileList as $file)
    {
      $key = str_replace($baseDir, '', $file);
      $hash = ResourceGenerator::getFileHash($file);
      $hashMap[$key] = $hash;
      $data .= "$key = $hash\n";
    }

    $outputFile = $input->getOption('output');
    $filename = Path::build($baseDir, $outputFile);
    if(!file_exists($filename) || is_writable($filename))
    {
      file_put_contents($filename, $data);
      $output->writeln(
        "Written " . count($fileList) . " file hash keys to " . $filename
      );
    }
    else
    {
      $output->writeln("Failed writing to $filename");
    }
  }

  public function globRecursive($pattern, $flags = 0)
  {
    $files = glob($pattern, $flags);
    foreach(glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
    {
      $files = array_merge(
        $files,
        $this->globRecursive($dir . '/' . basename($pattern), $flags)
      );
    }
    return $files;
  }
}
