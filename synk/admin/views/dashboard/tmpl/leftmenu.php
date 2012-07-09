<?php
/**
 * @version	1.5
 * @package	Synk
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
defined('_JEXEC') or die('Restricted access');
JHTML::_('stylesheet', 'leftmenu_admin.css', 'media/com_synk/css/');
?>

<div id="<?php echo $this->name; ?>" class="leftmenu">
    <div class="title"><?php echo JText::_( $this->name ); ?></div>
    
    <div class="menuitems">
    <?php 
    foreach ($this->items as $item) {
        
        if ($this->hide) {
            
            if ($item[2] == 1) {
            ?>  <span class="nolink active"><?php echo $item[0]; ?></span> <?php
            } else {
            ?>  <span class="nolink"><?php echo $item[0]; ?></span> <?php    
            }
            
        } else {
            
            if ($item[2] == 1) {
            ?> <a class="active" href="<?php echo $item[1]; ?>"><?php echo $item[0]; ?></a> <?php
            } else {
            ?> <a href="<?php echo $item[1]; ?>"><?php echo $item[0]; ?></a> <?php   
            }        
        }
        
    }
    ?>
    </div>
</div>