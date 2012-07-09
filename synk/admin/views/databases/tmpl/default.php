<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php JHTML::_('script', 'common.js', 'media/com_synk/js/'); ?>
<?php $state = @$this->state; ?>
<?php $form = @$this->form; ?>
<?php $items = @$this->items; ?>

<form action="<?php echo JRoute::_( @$form['action'] )?>" method="post" name="adminForm" enctype="multipart/form-data">

	<?php echo SynkGrid::pagetooltip( JRequest::getVar('view') ); ?>
	
    <table>
        <tr>
            <td align="left" width="100%">
            </td>
            <td nowrap="nowrap">
                <input id="search" name="filter" value="<?php echo @$state->filter; ?>" />
                <button onclick="this.form.submit();"><?php echo JText::_('Search'); ?></button>
                <button onclick="synkResetFormFilters(this.form);"><?php echo JText::_('Reset'); ?></button>
            </td>
        </tr>
    </table>

	<table class="adminlist" style="clear: both;">
		<thead>
            <tr>
                <th style="width: 5px;">
                	<?php echo JText::_("Num"); ?>
                </th>
                <th style="width: 20px;">
                	<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( @$items ); ?>);" />
                </th>
                <th style="width: 50px;">
                	<?php echo SynkGrid::sort( 'ID', "tbl.id", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Title', "tbl.title", @$state->direction, @$state->order );?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Database', "tbl.database", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Host', "tbl.host", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo JText::_("Information");?>
                </th>
                <th>
                	<?php echo JText::_("Synchronizations");?>
                </th>
                <th>
                	<?php echo JText::_("Published");?>
                </th>
                <th>
                	<?php echo JText::_("Verified");?>
                </th>
            </tr>
            <tr class="filter">
                <th colspan="3">
                    <?php $attribs = array('class' => 'inputbox', 'size' => '1', 'onchange' => 'document.adminForm.submit();'); ?>
                    <div class="range">
                        <div class="rangeline">
                            <span class="label"><?php echo JText::_("From"); ?>:</span> <input id="filter_id_from" name="filter_id_from" value="<?php echo @$state->filter_id_from; ?>" size="5" class="input" />
                        </div>
                        <div class="rangeline">
                            <span class="label"><?php echo JText::_("To"); ?>:</span> <input id="filter_id_to" name="filter_id_to" value="<?php echo @$state->filter_id_to; ?>" size="5" class="input" />
                        </div>
                    </div>
                </th>                
                <th style="text-align: center;">
                    <input id="filter_title" name="filter_title" value="<?php echo @$state->filter_title; ?>" size="25"/>
                </th>
                <th style="text-align: center;">
                    <input id="filter_db" name="filter_db" value="<?php echo @$state->filter_db; ?>" size="25"/>
                </th>
                <th style="text-align: center;">
                    <input id="filter_host" name="filter_host" value="<?php echo @$state->filter_host; ?>" size="25"/>
                </th>
                <th>
                </th>
                <th>
                    <?php echo SynkSelect::synchronization( @$state->filter_synchronizationid, 'filter_synchronizationid', $attribs, 'synchronizationid', true, false, 'Select Synchronization' ); ?>
                </th>
                <th>
                    <?php echo SynkSelect::booleans( @$state->filter_enabled, 'filter_enabled', $attribs, 'enabled', true, 'Enabled State' ); ?>
                </th>
                <th>
                    <?php echo SynkSelect::booleans( @$state->filter_verified, 'filter_verified', $attribs, 'verified', true, 'Verified State' ); ?>
                </th>
            </tr>
            <tr>
                <th colspan="20" style="font-weight: normal;">
                    <div style="float: right; padding: 5px;"><?php echo @$this->pagination->getResultsCounter(); ?></div>
                    <div style="float: left;"><?php echo @$this->pagination->getListFooter(); ?></div>
                </th>
            </tr>
		</thead>
        <tfoot>
            <tr>
                <td colspan="20">
                    <div style="float: right; padding: 5px;"><?php echo @$this->pagination->getResultsCounter(); ?></div>
                    <?php echo @$this->pagination->getPagesLinks(); ?>
                </td>
            </tr>
        </tfoot>
        <tbody>
		<?php $i=0; $k=0; ?>
        <?php
        if(!empty($items)){ 
        	foreach (@$items as $item) : ?>
            <tr class='row<?php echo $k; ?>'>
				<td align="center">
					<?php echo $i + 1; ?>
				</td>
				<td style="text-align: center;">
					<?php echo JHTML::_( 'grid.id', $i, $item->id ); ?>
				</td>
				<td style="text-align: center;">
					<a href="<?php echo $item->link; ?>"><?php echo $item->id; ?></a>
				</td>	
				<td style="text-align: center;">
					<a href="<?php echo $item->link; ?>"><?php echo $item->title; ?></a>
				</td>
				<td style="text-align: center;">
					<?php echo $item->database; ?>
				</td>	
				<td style="text-align: center;">
					<?php echo $item->host; ?>
				</td>	
				<td>
					<?php echo "<strong>".JText::_( 'Driver' ).":</strong> {$item->driver}<br/>"; ?>
            		<?php echo "<strong>".JText::_( 'Port' ).":</strong> {$item->port}<br/>"; ?>
					<?php echo "<strong>".JText::_( 'Prefix' ).":</strong> {$item->prefix}<br/>"; ?>
				</td>
				<td style="text-align: center;">
					<?php echo $item->synchronizations_list; ?>
				</td>
				<td style="text-align: center;">
					<?php echo JHTML::_( 'grid.published', $item, $i );?>
				</td>
				<td style="text-align: center;">
					<img src="images/<?php echo $item->img_u; ?>" border="0" alt="<?php echo $item->alt_u; ?>" title="<?php echo $item->alt_u; ?>" name="<?php echo $item->alt_u; ?>" />
					<br/>
					[<a href="<?php echo $item->link_verify; ?>"><?php echo JText::_( 'Verify Connection' ); ?></a>]
				</td>
			</tr>
				<?php
	            if (isset($item->description) && strlen($item->description) > 1) 
				{
					$text_display = "[ + ]";
					$text_hide = "[ - ]";
					$onclick = "displayDiv(\"description_{$item->id}\", \"showhidedescription_{$item->id}\", \"{$text_display}\", \"{$text_hide}\");";
					?>
			        <tr class='row<?php echo $k; ?>'>
		            	<td style="vertical-align: top; white-space:nowrap;">
							<span class='href' id='showhidedescription_<?php echo $item->id; ?>' onclick='<?php echo $onclick; ?>'><?php echo $text_display; ?></span>
		            	</td>
		            	<td colspan='10'> 
							<div id='description_<?php echo $item->id; ?>' style='display: none;'>
							<?php echo nl2br( strip_tags( stripslashes( $item->description ) ) ); ?>
							</div>
		            	</td>
			        </tr>
			    	<?php
				}
				?>				
			<?php $i=$i+1; $k = (1 - $k); ?>
			<?php endforeach; ?>
			
			<?php if (!count(@$items)) : ?>
			<tr>
				<td colspan="10" align="center">
					<?php echo JText::_('No items found'); ?>
				</td>
			</tr>
			<?php endif; 
        }?>
		</tbody>
	</table>
    
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="filter_order" value="<?php echo @$state->order; ?>" />
	<input type="hidden" name="filter_direction" value="<?php echo @$state->direction; ?>" />
	
	<?php echo $this->form['validate']; ?>
</form>
