<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php JHTML::_('script', 'common.js', 'media/com_synk/js/'); ?>
<?php $state = @$this->state; ?>
<?php $form = @$this->form; ?>
<?php $items = @$this->items; ?>
<?php 
$stats = @$this->statistics;
$logged_events = @$this->logged_events;
?>

<form action="<?php echo JRoute::_( @$form['action'] )?>" method="post" name="adminForm" enctype="multipart/form-data">

    <?php echo SynkGrid::pagetooltip( JRequest::getVar('view') ); ?>

	<table style="width: 100%;">
	<tr>
		<td style="width: 70%; max-width: 70%; vertical-align: top; padding: 0px 5px 0px 5px;">
		
		<p>
		<?php echo JText::_( "Selected Range for Statistics" ); ?>:
        <?php $attribs = array('class' => 'inputbox', 'size' => '1', 'onchange' => 'document.adminForm.submit();'); ?>
        <?php echo SynkSelect::range( @$state->stats_interval, 'stats_interval', $attribs ); ?>
        </p>

            <?php
            jimport('joomla.html.pane');
            $tabs = JPane::getInstance( 'tabs' );

            echo $tabs->startPane("tabone");
            echo $tabs->startPanel( JText::_( 'Synchronization Logs' ), "logs" );

                echo "<h2>".@$this->stats_left->title."</h2>";
                echo @$this->stats_left->image;

            echo $tabs->endPanel();
            echo $tabs->endPane();
            ?>
            
		<?php
		$modules = JModuleHelper::getModules("synk_dashboard_main");
		$document	= &JFactory::getDocument();
		$renderer	= $document->loadRenderer('module');
		$attribs 	= array();
		$attribs['style'] = 'xhtml';
		foreach ( @$modules as $mod ) 
		{
			echo $renderer->render($mod, $attribs);
		}
		?>
		</td>
		<td style="vertical-align: top; width: 30%; min-width: 30%; padding: 0px 5px 0px 10px;">
		
	        <table class="adminlist" style="margin-bottom: 5px;">
	        <thead>
	            <tr>
	                <th colspan="3"><?php echo JText::_( "Summary Statistics" ); ?></th>
	            </tr>
	            <tr>
	                <th><?php echo JText::_('Event ID'); ?></th>
	                <th><?php echo JText::_('Event Name'); ?></th>
	                <th><?php echo JText::_('Times Triggered'); ?></th>
	            </tr>
	        </thead>
	        <tbody>
	        	<?php foreach(@$logged_events as $event){ ?>
	        	<tr>
					<td align="center"><?php echo $event->eventid; ?></td>
					<td align="center"><?php echo $event->title ? $event->title : JText::_('Deleted'); ?></td>
					<td align="center"><?php echo $event->cnt; ?></td>
				</tr>	
	        	<?php } ?>
	        	
	        	<?php if (empty($logged_events)) { ?>
                <tr>
                    <td colspan="3"><?php echo JText::_( "No Results for Selected Range" ); ?></td>
                </tr>   
                <?php } ?>	        	
	        </tbody>
	        </table>
		
			<?php
			$modules = JModuleHelper::getModules("synk_dashboard_right");
			$document	= &JFactory::getDocument();
			$renderer	= $document->loadRenderer('module');
			$attribs 	= array();
			$attribs['style'] = 'xhtml';
			foreach ( @$modules as $mod ) 
			{
				echo $renderer->render($mod, $attribs);
			}
			?>
		</td>
	</tr>
    </table>
    
    <?php echo $this->form['validate']; ?>
</form>