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
                	<?php echo SynkGrid::sort( 'Synchronization', "tbl.synchronizationid", @$state->direction, @$state->order ); ?>
                	+
                	<?php echo SynkGrid::sort( 'Event', "tbl.eventid", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Database', "tbl.databaseid", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'User', "tbl.userid", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Article', "tbl.contentid", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Date', "tbl.datetime", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                	<?php echo SynkGrid::sort( 'Success', "tbl.success", @$state->direction, @$state->order ); ?>
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
	                <?php echo SynkSelect::synchronization( @$state->filter_synchronizationid, 'filter_synchronizationid', $attribs, 'synchronizationid', true, false, 'Select Synchronization' ); ?>
	                <?php echo SynkSelect::event( @$state->filter_eventid, 'filter_eventid', $attribs, 'eventid', true, false, 'Select Event' ); ?>
                </th>
                <th style="text-align: center;">
                    <?php echo SynkSelect::database( @$state->filter_databaseid, 'filter_databaseid', $attribs, 'databaseid', true, false, 'Select Database' ); ?>
                </th>
                <th style="text-align: center;">
                    <input id="filter_user" name="filter_user" value="<?php echo @$state->filter_user; ?>" size="25"/>
                </th>
                <th>
                    <input id="filter_article" name="filter_article" value="<?php echo @$state->filter_article; ?>" size="25"/>
                </th>
                <th>
                    <div class="range">
                        <div class="rangeline">
                            <span class="label"><?php echo JText::_("From"); ?>:</span>
                            <?php echo JHTML::calendar( @$state->filter_date_from, "filter_date_from", "filter_date_from", '%Y-%m-%d %H:%M:%S' ); ?>
                        </div>
                        <div class="rangeline">
                            <span class="label"><?php echo JText::_("To"); ?>:</span>
                            <?php echo JHTML::calendar( @$state->filter_date_to, "filter_date_to", "filter_date_to", '%Y-%m-%d %H:%M:%S' ); ?>
                        </div>
                    </div>
                </th>
                <th>
                    <?php echo SynkSelect::booleans( @$state->filter_success, 'filter_success', $attribs, 'success', true, 'Success State', 'Succeeded', 'Failed' ); ?>
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
					<?php echo $item->id; ?>
				</td>	
				<td style="text-align: center;">
					<?php 
					echo $item->synk_title;
					if ($item->eventid)
					{
						echo "<br/>&nbsp;&nbsp;&bull;&nbsp;&nbsp;";
						echo $item->event_title ? $item->event_title." [{$item->eventid}]" : JText::_( 'Event Not Found' )." [{$item->eventid}]";	
					}
					?>
				</td>
				<td style="text-align: center;">
					<?php echo $item->database_title; ?>
				</td>	
				<td style="text-align: center;">
					<?php if ($item->userid)
					{
						echo $item->user_username ? $item->user_username." [{$item->userid}]" : JText::_( 'User Record Not Found' )." [{$item->userid}]";	
					} 
					?>
				</td>	
				<td style="text-align: center;">
					<?php if ($item->contentid)
					{
						echo $item->content_title ? $item->content_title." [{$item->contentid}]" : JText::_( 'Article Not Found' )." [{$item->contentid}]";	
					} ?>
				</td>
				<td style="text-align: center;">
					<?php echo $item->datetime; ?>
				</td>
				<td class="noborder" style="text-align: center;">
					<?php
		        	$img_u 	= $item->success ? 'tick.png' : 'publish_x.png';
					$alt_u 	= $item->success ? JText::_( 'Success' ) : JText::_( 'Failed' );
					?>
					<img src="images/<?php echo $img_u; ?>" border="0" alt="<?php echo $alt_u; ?>" title="<?php echo $alt_u; ?>" name="<?php echo $alt_u; ?>" />
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
        }
        ?>
		</tbody>
	</table>
    
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="filter_order" value="<?php echo @$state->order; ?>" />
	<input type="hidden" name="filter_direction" value="<?php echo @$state->direction; ?>" />
	
	<?php echo $this->form['validate']; ?>
</form>