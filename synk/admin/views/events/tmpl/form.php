<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php $form = @$this->form; ?>
<?php $row = @$this->row;?>

<form action="<?php echo JRoute::_( @$form['action'] ) ?>" method="post" class="adminform" name="adminForm" >

	<fieldset>
		<legend><?php echo JText::_('Form'); ?></legend>
			<table class="admintable">
				<tr>
					<td width="100" align="right" class="key">
						<label for="title">
						<?php echo JText::_( 'Title' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="title" id="title" size="48" maxlength="250" value="<?php echo @$row->title; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="description">
						<?php echo JText::_( 'Description' ); ?>:
						</label>
					</td>
					<td>
						<textarea name="description" id="description"  cols='40' rows='10' style='width:500px'><?php echo @$row->description; ?></textarea>
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="published">
						<?php echo JText::_( 'Published' ); ?>:
						</label>
					</td>
					<td>
						<?php echo JHTML::_('select.booleanlist', 'published', '', @$row->published ) ?>
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="type">
						<?php echo JText::_( 'Type' ); ?>:
						</label>
					</td>
					<td>
                        <?php echo SynkSelect::typetype( @$row->type, 'type' ); ?>			
					</td>
				</tr>
				
				
			</table>
			<input type="hidden" name="id" value="<?php echo @$row->id?>" />
			<input type="hidden" name="task" value="" />
	</fieldset>
</form>