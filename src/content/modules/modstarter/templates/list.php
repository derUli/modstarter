<?php
$manager = new ModStarterProjectManager ();
$projects = $manager->getAllProjects ();
?>
<p>
	<a href="<?php echo ModuleHelper::buildActionURL("modstarter_new");?>"
		class="btn btn-default"><i class="fas fa-plus"></i> <?php translate("new");?></a>
</p>
<table class="tablesorter">
	<thead>
		<tr>
			<th><?php translate("name");?></th>
			<td></td>
		</tr>
	</thead>
	<tbody>
<?php foreach($projects as $project){?>
<tr>
			<td><a
				href="<?php echo ModuleHelper::buildMethodCallUrl("ModStarter", "edit", "name=".$project."&action=modstarter_edit");?>" title="<?php translate("edit");?>" class="btn btn-default"><?php esc($project);?></a></td>
			<td class="text-right"><a
				href="<?php echo ModuleHelper::buildMethodCallUrl("ModStarter", "edit", "name=".$project."&action=modstarter_edit");?>" title="<?php translate("edit");?>" class="btn btn-default"><i class="far fa-edit"></i></a></td>
		</tr>
<?php }?>
</tbody>
</table>