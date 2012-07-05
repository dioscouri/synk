<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php $form = @$this->form; ?>
<?php $row = @$this->row; ?>

<form action="<?php echo JRoute::_( @$form['action'] ) ?>" method="post" class="adminform" name="adminForm" >

	<p>
		Would display a header that includes the tool's 
		name (<?php echo @$row->name ?>)
	</p>
	
	<p>
		Then would render tool.
	</p>
	
	<input type="hidden" name="id" value="<?php echo @$row->id?>" />
	<input type="hidden" name="task" id="task" value="" />
</form>