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
						<label for="database_title">
						<?php echo JText::_( 'Database' ); ?>: *
						</label>
					</td>
					<td>
                        <?php echo SynkSelect::database( @$row->databaseid, 'databaseid', null, 'databaseid', true, false, 'Select Database' ); ?>
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="published">
						<?php echo JText::_( 'Published' ); ?>: *
						</label>
					</td>
					<td>
						<?php echo JHTML::_('select.booleanlist', 'published', '', @$row->published ) ?>
					</td>
				</tr>
				<tr>
                	<td width="100" align="right" class="key">
                		<label for="publish_up">
                		<?php echo JText::_( 'Start Publishing' ); ?>: *
                		</label>
                	</td>
                	<td>
                    	<?php echo JHTML::calendar(@$row->publish_up, "publish_up", "publish_up", '%Y-%m-%d'); ?>
                    </td>
                </tr>                    
                <tr>
                    <td width="100" align="right" class="key">
                		<label for="publish_up">
                		<?php echo JText::_( 'Finish Publishing' ); ?>: *
                		</label>
                	</td>
                	<td>
                    	<?php echo JHTML::calendar(@$row->publish_down, "publish_down", "publish_down", '%Y-%m-%d'); ?>
                    </td>
                </tr>
                <tr>
					<td width="100" align="right" class="key">
						<label for="published">
						<?php echo JText::_( 'Use Custom Query' ); ?>:
						</label>
					</td>
					<td>
						<?php echo JHTML::_('select.booleanlist', 'use_custom', '', @$row->use_custom ) ?>
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="custom_query">
						<?php echo JText::_( 'Custom Query' ); ?>:
						</label>
					</td>
					<td>
						<textarea name="custom_query" id="custom_query"  cols='40' rows='10' style='width:500px'><?php echo stripslashes(@$row->custom_query); ?></textarea>
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="limit_hourly">
						<?php echo JText::_( 'Limit Hourly' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="limit_hourly" id="limit_hourly" size="10" maxlength="250" value="<?php echo @$row->limit_hourly; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="limit_daily">
						<?php echo JText::_( 'Limit Daily' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="limit_daily" id="limit_daily" size="10" maxlength="250" value="<?php echo @$row->limit_daily; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="limit_weekly">
						<?php echo JText::_( 'Limit Weekly' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="limit_weekly" id="limit_weekly" size="10" maxlength="250" value="<?php echo @$row->limit_weekly; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="limit_monthly">
						<?php echo JText::_( 'Limit Monthly' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="limit_monthly" id="limit_monthly" size="10" maxlength="250" value="<?php echo @$row->limit_monthly; ?>" />
					</td>
				</tr>
				<tr>
					<td width="100" align="right" class="key">
						<label for="limit_yearly">
						<?php echo JText::_( 'Limit Yearly' ); ?>:
						</label>
					</td>
					<td>
						<input type="text" name="limit_yearly" id="limit_yearly" size="10" maxlength="250" value="<?php echo @$row->limit_yearly; ?>" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="id" value="<?php echo @$row->id?>" />
			<input type="hidden" name="task" value="" />
	</fieldset>
</form>