<?php
/* This file is released under the CeCILL-B V1 licence.*/

/**
 * View to see the statements.
 */
class DataMultiView extends AbstractView {
	/**
	 * Display the button to add a statement.
	 */
	public static function showAddButton() {
		$url = CNavigation::generateUrlToApp('DataMulti','form');
		echo '<div class="well">';
		self::showButton($url, 'primary', _('New multiple statement'), 'plus');
		echo '</div>';
	}

	/**
	 * Displays form to create a statement.
	 */
	public static function showAddForm($values, $mode = 'add') {

		$label_name = _('Name');
		$label_desc = _('Description');
		$url_submit = CNavigation::generateUrlToApp('DataMulti', 'form');
		$text_submit = _('Create the multiple statement');
		$hname = htmlspecialchars($values['name']);
		$hdesc = htmlspecialchars($values['desc']);
		$statements = DataMod::getStatementsWithId();

		$statements_name = [];

		$autofocus_name = $mode === 'add' ? 'autofocus' : '';

		CHead::addJS('jquery.tablesorter.min');
		CHead::addJS('TableFilter');

		echo <<<HTML
<form action="$url_submit" name="data_multi_add_form" method="post" id="data_multi_add_form" class="form-horizontal well">
<fieldset>
	<table class="table table-striped table-bordered table-condensed sorted_table data_list">
		<thead><tr>
			<th class="header purple">&#x2611;</th>
			<th class="header yellow">Name</th>
			<th class="header green">Description</th>
			<th class="header blue">Type</th>
		</tr></thead>
		<tbody>
HTML;
		foreach ($statements as $statement) {
			$hsname = htmlspecialchars($statement['name']);
			$hdescr = htmlspecialchars($statement['description']);
			$hmodname = htmlspecialchars($statement['modname']);
			$hid = htmlspecialchars($statement['id']);
			if((isset($_REQUEST['name']) && $statement['name'] == $_REQUEST['name']) ||
				in_array($statement['id'], $values['releve'])
				)
			{
				$checked = 'checked';
				array_push($statements_name, $statement['name']);
			}
			else $checked = '';
			echo <<<HTML
		<tr class="$checked">
			<td><input type="checkbox" name="releve[]" value="$hid" $checked/></td>
			<td class="name">$hsname</td>
			<td>$hdescr</td>
			<td>$hmodname</td>
		</tr>
HTML;
		}
		echo<<<HTML
	</table>
</fieldset>
<fieldset>
	<div class="control-group">
	   <label for ="input_name" class="control-label">$label_name</label>
	   <div class="controls">
			<input name="name" id="input_name" value="$hname" type="text" $autofocus_name required />
		</div>
	</div>
	<div class="control-group">
	   <label for ="input_desc" class="control-label">$label_desc</label>
	   <div class="controls">
			<textarea name="desc" id="input_desc">$hdescr</textarea>
		</div>
	</div>
</fieldset>
<fieldset>
	<div class="actions">
HTML;
		$url_back = CNavigation::generateUrlToApp('Data');
		self::showButton($url_back, 'info', _('Return to the list'), 'back');

		if ($mode === 'edit')
		{
			array_push($statements_name, $values['name']);
			$statements =
			$url_view =	CNavigation::generateUrlToApp('').'#f'.JsURL::stringify(
				['h' => [['LineChart_m' => $statements_name]]]
				);
			$url_del =	CNavigation::generateMergedUrl('DataMulti', 'remove');
		        $url_ext = CNavigation::generateUrlToApp('DataSample', 'viewSelectMulti', array('name' => $values['name']));
			self::showButton($url_view, 'success', _('View the statement'), 'magnify');
			self::showButton($url_ext, 'success', _('Extraction'), 'magnify');
			self::showButton($url_del, 'danger', _('Delete this statement'), 'del');
			$text_submit = _('Save the changes');
			echo '<input type="hidden" name="form_mode" value="edit" />';
			$icon_submit = 'pencil';
			$fade = 'fade';
		}
		else
		{
			$url_stat = CNavigation::generateUrlToApp('Data', 'form');
			self::showButton($url_stat, 'info', _('Create new statement'), 'plus');
			$text_submit = _('Create the multiple statement');
			$icon_submit = 'plus';
			$fade = '';
		}

		// Intval for prevent html injections…
		$old_id = intval($values['old_id']);
		echo <<<HTML

		<input type="hidden" name="mode" value="$mode" />
		<input type="hidden" name="old_id" value="$old_id" />
		<button type="submit" class="btn btn-large btn-primary $fade">
			<span class="icon_button ${icon_submit}_text">$text_submit</span>
		</button>

	</div>
</fieldset>
</form>


HTML;

}



/**
 * Displays a list of statements.
 * @param $statements Array of statements to show.
 */
public static function showStatementsList($statements) {
		if ($statements) {
			CHead::addJS('jquery.tablesorter.min');
			CHead::addJS('TableFilter');
			echo <<<HTML
<table class="table table-striped table-bordered data_list">
	<thead><tr>
		<th class="header yellow">Name</th>
		<th class="header green">Description</th>
		<th class="header blue">Type</th>
	</tr></thead>
	<tbody>
HTML;
			foreach ($statements as $statement) {
				$url = CNavigation::generateUrlToApp('DataMulti', 'view', ['name' => $statement['name']]);
				echo "\t<tr><td><a href=\"$url\">", htmlspecialchars($statement['name']),
				"</a></td><td><a href=\"$url\">", htmlspecialchars($statement['description']),
				"</a></td><td><a href=\"$url\">", htmlspecialchars($statement['modname']), "</a></td></tr>\n";
			}

			echo "</tbody></table>";
		} else
			echo '<div class="alert alert-block alert-warning">',
				_('There are no multiple statements at the moment.'),'</div>';
	}

	/**
	 * Shows the form to remove a statement.
	 * @param $desc The statement's description.
	 * @param $url_confirm The url to confirm the removal of the statement.
	 * @param $url_back The url to not remove the statement and go back.
	 */
	public static function showRemoveForm($desc, $url_confirm, $url_back) {
		$hmsg = _('Do you really want to delete this statement?');
		echo <<<HTML
		<div class="alert alert-block alert-warning">
						   <p>$hmsg</p>
		</div>
		<div class="well">
HTML;
		self::showButton($url_back, 'info', _('Cancel'), 'back');
		self::showButton($url_confirm, 'danger float_right', _('Delete'), 'del');
		echo '</div>';
	}
}
?>
