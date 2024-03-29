<?php

use App\Helpers\ModuleHelper;
use App\Models\Content\Language;
use App\Storages\ViewBag;

$sources = [
    '',
    'pkgsrc',
    'extend',
    'local',
    'core'
];
// TODO: Update list of all hooks
$hooksFile = \App\Utils\Path::resolve('ULICMS_ROOT/lib/events.json');
$hooks = json_decode(file_get_contents($hooksFile));

$model = ViewBag::get('model') ?: new ModStarterProjectViewModel();

$action = $model->edit ? 'update' : 'create';
$headline = $model->edit ? 'edit_module' : 'create_module';

$languages = Language::getAllLanguages();
?>
<h1><?php translate($headline);?></h1>
<?php echo ModuleHelper::buildMethodCallForm('ModStarter', $action);?>
<p>
	<a href="<?php echo ModuleHelper::buildAdminURL('modstarter');?>"
		class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> <?php translate('cancel');?></a>
</p>
<p>
	<strong><?php translate('module_folder');?>*</strong> <br /> <input
		type="text" name="module_folder" maxlength="32"
		value="<?php esc($model->module_folder);?>"
		<?php if($model->edit) {
		    echo 'readonly';
		}?> required>
</p>
<p>
	<strong><?php translate('source');?></strong><br /> <select
		name="source">
		<?php foreach($sources as $source) {?>
		<option value="<?php esc($source);?>"
			<?php if($source == $model->source) echo 'selected';?>><?php esc($source);?></option>
		<?php }?></select>
</p>
<p>
	<strong><?php translate('version');?>*</strong> <br /> <input
		type="text" name="version" maxlength="10"
		value="<?php esc($model->version);?>" required>
</p>
<p>
	<input type="checkbox" name="embeddable" id="embeddable" value="1"
		<?php if($model->embeddable) {
		    echo 'checked';
		}?>> <label
		for="embeddable"><?php translate('embeddable');?></label>
</p>
<p>
	<input type="checkbox" name="shy" id="shy" value="1"
		<?php if($model->shy) {
		    echo 'checked';
		}?>> <label for="shy"> <?php translate('shy');?></label>
</p>
<p>
	<strong><?php translate('main_class');?>*</strong><br /> <input
		type="text" name="main_class"
		<?php if($model->edit) {
		    echo 'readonly';
		}?>
		value="<?php esc($model->main_class);?>" required>
</p>
<p>
	<input type="checkbox" name="create_post_install_script"
		id="create_post_install_script"
		<?php if($model->edit && $model->create_post_install_script) {
		    echo 'disabled';
		}?>
		value="1"
		<?php if($model->create_post_install_script) {
		    echo 'checked';
		}?>> <label
		for="create_post_install_script"><?php translate('create_post_install_script');?></label>
</p>
<p>
	<strong><?php translate('hooks');?></strong> <br /> <select
		name="hooks[]" multiple <?php if($model->edit) {
		    echo 'disabled';
		}?>>
<?php

foreach ($hooks as $hook) {
    ?>
	<option value="<?php esc(ModuleHelper::underscoreToCamel($hook));?>"
			<?php if(in_array($hook, $model->hooks));?>><?php esc($hook);?></option>
<?php }?>
</select>
</p>
<p>
	<strong><?php translate('languages');?></strong><br /> <select
		name="languages[]" multiple>
<?php

foreach ($languages as $language) {
    ?>
<option value="<?php esc($language->getLanguageCode());?>"
			<?php if(in_array($language->getLanguageCode(), $model->languages)) echo 'selected';?>><?php esc($language->getName());?></option>
<?php }?>
</select>
</p>
<p>
	<button type="submit" class="btn btn-success"><i class="far fa-save"></i> <?php translate('save');?></button>
</p>
</form>
