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
						<?php echo JText::_( 'Title' ); ?>: *
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
						<label for="host">
						<?php echo JText::_( 'Host' ); ?>: *
						</label>
					</td>
					<td>
						<input type="text" name="host" id="host" size="48" maxlength="250" value="<?php echo @$row->host; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="user">
						<?php echo JText::_( 'User' ); ?>: *
						</label>
					</td>
					<td>
						<input type="text" name="user" id="user" size="48" maxlength="250" value="<?php echo @$row->user; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="password">
						<?php echo JText::_( 'Password' ); ?>: *
						</label>
					</td>
					<td>
						<input type="password" name="password" id="password" size="48" maxlength="250" value="<?php echo @$row->password; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="database">
						<?php echo JText::_( 'Database' ); ?>: *
						</label>
					</td>
					<td>
						<input type="text" name="database" id="database" size="48" maxlength="250" value="<?php echo @$row->database; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="prefix">
						<?php echo JText::_( 'Prefix' ); ?>: *
						</label>
					</td>
					<td>
						<input type="text" name="prefix" id="prefix" size="48" maxlength="250" value="<?php echo @$row->prefix; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="driver">
						<?php echo JText::_( 'Driver' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="driver" id="driver" size="48" maxlength="250" value="<?php echo @$row->driver; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="port">
						<?php echo JText::_( 'Port' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="port" id="port" size="48" maxlength="250" value="<?php echo @$row->port; ?>" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="id" value="<?php echo @$row->id?>" />
			<input type="hidden" name="task" value="" />
	
	<br />
	<?php echo JText::_('* Indicates a Required Field'); ?>
	</fieldset>
</form>

