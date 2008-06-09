<?php

/*
 * This file is part of the symfony package.
 * (c) 2008 Bertrand Zuchuat <bertrand.zuchuat@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Freeze symfony project with all libraries
 * Copy to another location
 * Unfreeze the original project
 * http://pastie.textmate.org/211515
 *
 * @package    symfony
 * @subpackage task
 * @author     Bertrand Zuchuat <bertrand.zuchuat@gmail.com>
 * @version    SVN: $Id$ $
 */
class sfProjectExportTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->aliases = array('export');
    $this->namespace = 'project';
    $this->name = 'export';
    $this->briefDescription = 'Freeze, copy to another location and unfreeze';

    $this->detailedDescription = <<<EOF
EOF;

    $this->addArguments(array(
      new sfCommandArgument('symfony_data_dir', sfCommandArgument::REQUIRED, 'The symfony data directory'),
      new sfCommandArgument('path', sfCommandArgument::REQUIRED, 'The path to export your project'),
      new sfCommandArgument('options', sfCommandArgument::OPTIONAL, 'options'),
    ));
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $path = $arguments['path'];

    $options = array(
                  'cache' => array('delete' => true, 'param' => 'sf_cache_dir', 'path' => ''),
                  'uploads' => array('delete' => true, 'param' => 'sf_upload_dir', 'path' => ''),
                  'fixtures' => array('delete' => true, 'param' => 'sf_data_dir', 'path' => '/fixtures'),
                  'doc' => array('delete' => true, 'param' => 'sf_doc_dir', 'path' => ''),
                  'test' => array('delete' => true, 'param' => 'sf_test_dir', 'path' => ''),
                  );
    
    $opts = isset($arguments['options']) ? explode('+', $arguments['options']): array();
    
    if (count($opts) > 0)
    {
       foreach ($opts AS $key)
       {
           $options[$key]['delete'] = false;
       }
    }

    // Freeze
    $freeze = new sfProjectFreezeTask($this->dispatcher, $this->formatter);
    $freeze->run(array('symfony_data_dir' => $arguments['symfony_data_dir']));
    
    if(!is_dir($path))
    {
      $this->getFilesystem()->mkdirs($path);
    }
    
    $sf_root_dir = sfConfig::get('sf_root_dir');
    
    $dirFinder = sfFinder::type('any')->discard('.DS_Store', '.sf', '*_dev.php', '*.php.bak', '*.log')->relative();
    
    $this->getFilesystem()->mirror($sf_root_dir, $path, $dirFinder, array('override' => true));
    
    $finder = sfFinder::type('any');
    
    foreach($options AS $key => $value)
    {
      if($value['delete'])
      {
        $param = str_replace($sf_root_dir, '', sfConfig::get($value['param']));
        $dir_path = $path . $param . $value['path'];
        if(is_dir($dir_path))
        {
          $this->getFilesystem()->remove($finder->in($dir_path));
        }
      }
    }

    $plugins = str_replace($sf_root_dir, '', sfConfig::get('sf_plugins_dir'));
    
    $this->getFilesystem()->remove($finder->in($path.$plugins.'/sfProjectExportPlugin'));
    $this->getFilesystem()->remove($path.$plugins.'/sfProjectExportPlugin');
    
    // Unfreeze
    $freeze = new sfProjectUnfreezeTask($this->dispatcher, $this->formatter);
    $freeze->run();
    
  }
}