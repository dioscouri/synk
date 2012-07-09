<?php
/**
 * @version	1.5
 * @package	Synk
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/ 
defined( '_JEXEC' ) or die( 'Restricted access' );

$installer = new SynkInstaller();
if (!$installer->getLibrary())
{
    // fail with a message saying the extension cannot be used without the DSC library.  Please download it from here and install it
    // or (preferably) install it by clicking on a link
    $app = JFactory::getApplication();
    foreach ($installer->getErrors() as $message)
    {
        $app->enqueueMessage($message, 'error');
    }
    $app->enqueueMessage('This extension REQUIRES the Dioscouri Library, which we were not able to find nor install for you.  This extension will NOT work without the library.  Please download the library from the following URL, install it, then attempt to install your extension again. <a href="http://updates.dioscouri.com/library/downloads/latest.zip">http://updates.dioscouri.com/library/downloads/latest.zip</a>', 'error' );
    $app->enqueueMessage('This installation did not complete correctly.  The extension will NOT work.  Please contact support.', 'error');
    $app->redirect('index.php?option=com_installer&view=install');
    return false;

}
else
{
    if (JFile::exists(JPATH_SITE.'/libraries/dioscouri/component/install.php'))
    {
        $thisextension = strtolower( "com_synk" );
        $thisextensionname = substr ( $thisextension, 4 );
        include JPATH_SITE . '/libraries/dioscouri/component/install.php';
    }
    else
    {
        // fail with a message about this being an incomplete installation, that extension WILL NOT WORK
        $app = JFactory::getApplication();
        $app->enqueueMessage('This installation did not complete correctly.  The extension will NOT work.  Please contact support.', 'error');
        $app->redirect('index.php?option=com_installer&view=install');
        return false;
    }
}

class SynkInstaller extends JObject 
{
    public $lib_url = 'http://updates.dioscouri.com/library/downloads/latest.zip';
    public $plugin_url = 'http://updates.dioscouri.com/plg_system_dioscouri/downloads/latest.zip';
    public $plugin_url_j15 = 'http://updates.dioscouri.com/plg_system_dioscouri/downloads/j15/latest.zip';
    public $min_php_required = '5.3.0';
    
    /**
     * Checks the minimum required php version
     * @return boolean
     */
    protected function checkPHPVersion() 
    {
        if (version_compare(PHP_VERSION, $this->min_php_required) >= 0) {
            return true;
        }
        
        return false;
    }

    
    /**
     * Load the library -- installing it if necessary
     * 
     * @return boolean result of install & load
     */
    public function getLibrary()
    {
        if (!$this->checkPHPVersion()) {
            $this->setError( "You do not meet the minimum system requirements.  You must have at least PHP version: " . $this->min_php_required . " but you are using " . PHP_VERSION );
            return false;
        }
        
        jimport('joomla.filesystem.file');
        if (!class_exists('DSC')) {
            if (!JFile::exists(JPATH_SITE.'/libraries/dioscouri/dioscouri.php')) 
            {
                JModel::addIncludePath( JPATH_ADMINISTRATOR . '/components/com_installer/models' );
                if ($this->install('library')) 
                {
                    // if j15, move files
                    if(!version_compare(JVERSION,'1.6.0','ge')) {
                        // Joomla! 1.5 code here
                        if (JFile::exists(JPATH_SITE.'/plugins/system/dioscouri/dioscouri.php')) {
                            $this->manuallyInstallLibrary();
                        }
                    } 
                        else 
                    {
                        if (!$this->install('plugin')) 
                        {
                            $this->setError( "Could not install Dioscouri System Plugin" );
                        } 
                    }
                    
                    if (!$this->enablePlugin())
                    {
                        $this->setError( "Could not enable the Dioscouri System Plugin" );
                    }
                    
                    if (JFile::exists(JPATH_SITE.'/libraries/dioscouri/dioscouri.php')) 
                    {
                        require_once JPATH_SITE.'/libraries/dioscouri/dioscouri.php';
                        if (!DSC::loadLibrary()) {
                            $this->setError( "Could not load Dioscouri Library after installing it" );
                            return false;
                        }
                        return true;
                    }
                }
                    else 
                {
                    $this->setError( "Could not install Dioscouri Library" );
                    return false;
                }
            }
            else
            {
                require_once JPATH_SITE.'/libraries/dioscouri/dioscouri.php';
                if (!DSC::loadLibrary()) {
                    $this->setError( "Could not load Dioscouri Library" );
                    return false;
                }
                return true;
            }
        }
        
        return true;
    }
    
    /**
    * Install the library package
    *
    * @return	boolean result of install
    */
    function install( $type='library' )
    {
        jimport('joomla.installer.installer');
        jimport('joomla.installer.helper');

        $app = JFactory::getApplication();
        $package = $this->getPackageFromUrl($type);
    
        // Was the package unpacked?
        if (!$package) {
            $this->setError( JText::_('Could not find unpacked installation package') );
            return false;
        }
    
        // Get an installer instance
        $installer = new JInstaller();
    
        // Install the package
        if (!$installer->install($package['dir'])) {
            // There was an error installing the package
            $this->setError( 'There was an error installing the package' );
            $result = false;
        } else {
            // Package installed sucessfully
            $result = true;
        }
        
        // Cleanup the install files
        if (!is_file($package['packagefile'])) {
            $config = JFactory::getConfig();
            if(version_compare(JVERSION,'1.6.0','ge')) {
                // Joomla! 1.6+ code here
                $tmp_dest	= $config->get('tmp_path');
            } else {
                // Joomla! 1.5 code here
                $tmp_dest 	= $config->getValue('config.tmp_path');
            }
            $package['packagefile'] = $tmp_dest . '/' . $package['packagefile'];
        }
    
        JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
    
        return $result;
    }
    
    /**
    * Get the package from the updates server
    *
    * @return	Package details or false on failure
    */
    protected function getPackageFromUrl( $type='library' )
    {
        jimport('joomla.installer.helper');
    
        // Get a database connector
        $db = JFactory::getDbo();
    
        // Get the URL of the package to install
        if(version_compare(JVERSION,'1.6.0','ge')) {
            // Joomla! 1.6+ code here
            switch($type) {
                case "plugin":
                    $url = $this->plugin_url;
                    break;
                case "library":
                default:
                    $url = $this->lib_url;
                    break;
            }
            
        } else {
            // Joomla! 1.5 code here
            $url = $this->plugin_url_j15;
        }
    
        // Download the package at the URL given
        $p_file = JInstallerHelper::downloadPackage($url);
    
        // Was the package downloaded?
        if (!$p_file) {
            $this->setError( JText::_('Could not download library installation package') );
            return false;
        }
    
        $config		= JFactory::getConfig();
        if(version_compare(JVERSION,'1.6.0','ge')) {
            // Joomla! 1.6+ code here
            $tmp_dest	= $config->get('tmp_path');
        } else {
            // Joomla! 1.5 code here
            $tmp_dest 	= $config->getValue('config.tmp_path');
        }
            
        // Unpack the downloaded package file
        $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file);
    
        return $package;
    }
    
    /**
     * Install the library files manually (only for J1.5) 
     * @return boolean
     */
    protected function manuallyInstallLibrary()
    {
        jimport('joomla.filesystem.file');
        
        $return = false;
    
        if (!JFile::exists(JPATH_SITE.'/plugins/system/dioscouri/dioscouri.php')) {
            return $return;
        }
    
        jimport('joomla.filesystem.folder');
    
        $src = DS . 'plugins' . DS . 'system' . DS . 'dioscouri' . DS;
        $dest = DS . 'libraries' . DS . 'dioscouri' . DS;
        $src_folders = JFolder::folders(JPATH_SITE.'/plugins/system/dioscouri', '.', true, true);
        if (!empty($src_folders)) {
            foreach ($src_folders as $src_folder) {
                $src_folder = str_replace(JPATH_SITE, '', $src_folder);
                $dest_folder = str_replace( $src, '', $src_folder);
                if (!JFolder::exists(JPATH_SITE.$dest.$dest_folder)) {
                    JFolder::create(JPATH_SITE.$dest.$dest_folder);
                }
            }
        }
    
        // move files from plugins to libraries
        $src = DS . 'plugins' . DS . 'system' . DS . 'dioscouri' . DS;
        $dest = DS . 'libraries' . DS . 'dioscouri' . DS;
        $src_files = JFolder::files(JPATH_SITE.'/plugins/system/dioscouri', '.', true, true);
        if (!empty($src_files)) {
            foreach ($src_files as $src_file) {
                $src_filename = str_replace(JPATH_SITE, '', $src_file);
                $dest_filename = str_replace( $src, '', $src_filename);
                JFile::move(JPATH_SITE.$src_filename, JPATH_SITE.$dest.$dest_filename);
            }
    
            JFolder::delete(JPATH_SITE.'/plugins/system/dioscouri');
        }
    
        // move the media files from libraries to media
        $src = DS . 'libraries' . DS . 'dioscouri' . DS . 'media' . DS;
        $dest = DS . 'media' . DS . 'dioscouri' . DS;
        $src_files = JFolder::files(JPATH_SITE.'/libraries/dioscouri/media', '.', true, true);
        if (!empty($src_files)) {
            foreach ($src_files as $src_file) {
                $src_filename = str_replace(JPATH_SITE, '', $src_file);
                $dest_filename = str_replace( $src, '', $src_filename);
                JFile::move(JPATH_SITE.$src_filename, JPATH_SITE.$dest.$dest_filename);
            }
            JFolder::delete(JPATH_SITE.'/libraries/dioscouri/media');
        }
    
        // move the lang files from libraries to language
        $src_files = JFolder::files(JPATH_SITE.'/libraries/dioscouri/language', '.', true, true);
        $src = DS . 'libraries' . DS . 'dioscouri' . DS . 'language' . DS;
        $dest = DS . 'language' . DS;
        if (!empty($src_files)) {
            foreach ($src_files as $src_file) {
                $src_filename = str_replace(JPATH_SITE, '', $src_file);
                $dest_filename = str_replace( $src, '', $src_filename);
                JFile::move(JPATH_SITE.$src_filename, JPATH_SITE.$dest.$dest_filename);
            }
            JFolder::delete(JPATH_SITE.'/libraries/dioscouri/language');
        }
    
        if (JFile::exists(JPATH_SITE.'/libraries/dioscouri/dioscouri.php')) {
            $return = true;
        }
    
        return $return;
    }
    
    /**
     * Enables the system plugin after installation
     * 
     * @return boolean
     */
    protected function enablePlugin()
    {
        if(version_compare(JVERSION,'1.6.0','ge')) {
            // Joomla! 1.6+ code here
            $query	= "UPDATE #__extensions SET `enabled` = '1' WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'dioscouri';";
        } else {
            // Joomla! 1.5 code here
            $query	= "UPDATE #__plugins SET `published` = '1' WHERE `folder` = 'system' AND `element` = 'dioscouri';";
        }
        
        $db = JFactory::getDBO();
        $db->setQuery( $query );
        if (!$db->query())
        {
            return false;
        }
        
        return true;
    }
}
?>
