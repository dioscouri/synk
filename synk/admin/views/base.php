<?php
/**
 * @version	1.5
 * @package	Synk
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

class SynkViewBase extends DSCViewAdmin
{
    function display($tpl=null)
    {
        JHTML::_('stylesheet', 'common.css', 'media/dioscouri/css/');
        JHTML::_('stylesheet', 'admin.css', 'media/com_synk/css/');
    
        $parentPath = JPATH_ADMINISTRATOR . '/components/com_synk/helpers';
        DSCLoader::discover('SynkHelper', $parentPath, true);
    
        $parentPath = JPATH_ADMINISTRATOR . '/components/com_synk/library';
        DSCLoader::discover('Synk', $parentPath, true);
    
        parent::display($tpl);
    }
}