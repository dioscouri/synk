<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php JHTML::_('script', 'synk.js', 'media/com_synk/js/'); ?>
<?php $state = @$this->state; ?>
<?php $form = @$this->form; ?>
<?php $items = @$this->items; ?>
<?php $row = @$this->row;?>

<h3><?php echo JText::_( "Select Events for" ); ?>: <?php echo $row->title; ?></h3>

<div class="note" style="width: 95%; text-align: center; margin-left: auto; margin-right: auto;">
	<?php echo JText::_( "For Checked Items" ); ?>:
	<button onclick="document.getElementById('task').value='selected_switch'; document.adminForm.submit();"> <?php echo JText::_( "Change Status" ); ?></button>
</div>

<form action="<?php echo JRoute::_( @$form['action'] )?>" method="post" name="adminForm" enctype="multipart/form-data">

    <table>
        <tr>
            <td align="left" width="100%">
                <input id="search" name="filter" value="<?php echo @$state->filter; ?>" />
                <button onclick="this.form.submit();"><?php echo JText::_('Search'); ?></button>
                <button onclick="synkResetFormFilters(this.form);"><?php echo JText::_('Reset'); ?></button>
            </td>
            <td nowrap="nowrap">
                <?php $attribs = array('class' => 'inputbox', 'size' => '1', 'onchange' => 'document.adminForm.submit();'); ?>
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
                <th style="text-align: left;">
                	<?php echo SynkGrid::sort( 'Title', "tbl.title", @$state->direction, @$state->order ); ?>
                </th>
                <th style="width: 100px;">
    	            <?php echo SynkGrid::sort( 'Description', "tbl.description", @$state->direction, @$state->order ); ?>
                </th>
                <th>
                    <?php echo JText::_( 'Parameters' ); ?>
                    <img onmouseover="this.style.cursor='pointer';" onclick="document.adminForm.toggle.checked=true; checkAll(<?php echo count( @$items ); ?>); submitform('saveParameters'); " src="../administrator/images/filesave.png" border="0" alt="<?php echo JText::_( 'Save' ); ?>" title="<?php echo JText::_( 'Save' ); ?>" name="<?php echo JText::_( 'Save' ); ?>" />
                </th>
                <th>
	                <?php echo JText::_( 'Status' ); ?>
                </th>
            </tr>
		</thead>
        <tbody>
		<?php $i=0; $k=0; ?>
        <?php foreach (@$items as $item) : ?>
            <tr class='row<?php echo $k; ?>'>
				<td align="center">
					<?php echo $i + 1; ?>
				</td>
				<td style="text-align: center;">
					<?php echo SynkGrid::checkedout( $item, $i, 'id' ); ?>
				</td>
				<td style="text-align: center;">
					<?php echo $item->id; ?>
				</td>	
				<td style="text-align: left;">
					<?php echo JText::_( $item->title ); ?>
				</td>
				<td style="text-align: center;">
					<?php echo $item->description; ?>
				</td>
				<td>
                    <?php 
                        switch ($item->title) 
                        {
                            case "hourly":
                        	case "HOURLY":
                              echo JHTML::_('select.integerlist', '0', '59', '1', 'parameter['.$item->id.']', 'class="inputbox"', @$item->parameter );
                              break;
                            case "daily":
                            case "DAILY":
                              echo JHTML::_('select.integerlist', '0', '23', '1', 'parameter['.$item->id.']', 'class="inputbox"', @$item->parameter );
                              break;
                            case "weekly":
                            case "WEEKLY":
                              //echo JHTML::_('select.genericlist', $this->weekdayArray, 'parameter['.$item->id.']', "size='1' ", "value", "text", @$item->parameter );
                              echo SynkSelect::weekday(@$item->parameter, 'parameter['.$item->id.']');
                              break;
                            case "monthly":
                            case "MONTHLY":
                              echo JHTML::_('select.integerlist', '1', '28', '1', 'parameter['.$item->id.']', 'class="inputbox"', @$item->parameter );
                              break;
                            default:
                              break;                        
                        }
                    ?>
				</td>
				<td style="text-align: center;">
					<?php $table = JTable::getInstance('SynchronizationEvents', 'Table'); ?>
					<?php $table->load( $row->id, $item->id ); ?>
					<?php echo SynkGrid::enable(isset($table->synchronizationid), $i, 'selected_'); ?>
				</td>
			</tr>
			<?php $i=$i+1; $k = (1 - $k); ?>
			<?php endforeach; ?>
			
			<?php if (!count(@$items)) : ?>
			<tr>
				<td colspan="10" align="center">
					<?php echo JText::_('No items found'); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="20">
					<?php echo @$this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
	</table>

	<input type="hidden" name="task" id="task" value="selectevents" />
	<input type="hidden" name="boxchecked" value="" />
	<input type="hidden" name="filter_order" value="<?php echo @$state->order; ?>" />
	<input type="hidden" name="filter_direction" value="<?php echo @$state->direction; ?>" />
	
	<?php echo $this->form['validate']; ?>
</form>