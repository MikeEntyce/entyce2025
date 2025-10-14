<?php
defined("ABSPATH") or die("");

use Duplicator\Controllers\ToolsPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;

?>

<?php TplMng::getInstance()->render('admin_pages/diagnostics/purge_orphans_message'); ?>
<?php TplMng::getInstance()->render('admin_pages/diagnostics/clean_tmp_cache_message'); ?>
<?php TplMng::getInstance()->render('parts/migration/migration-message'); ?>

<form id="dup-settings-form" action="<?php echo ControllersManager::getCurrentLink(); ?>" method="post">
    <?php
    include_once(DUPLICATOR____PATH . '/views/tools/diagnostics/inc.data.php');
    include_once(DUPLICATOR____PATH . '/views/tools/diagnostics/inc.settings.php');
    include_once(DUPLICATOR____PATH . '/views/tools/diagnostics/inc.validator.php');
    include_once(DUPLICATOR____PATH . '/views/tools/diagnostics/inc.phpinfo.php');
    ?>
</form>
<?php
$deleteOptConfirm               = new DUP_PRO_UI_Dialog();
$deleteOptConfirm->title        = __('Are you sure you want to delete?', 'duplicator-pro');
$deleteOptConfirm->message      = __('Delete this option value.', 'duplicator-pro');
$deleteOptConfirm->progressText = __('Removing, Please Wait...', 'duplicator-pro');
$deleteOptConfirm->jsCallback   = 'DupPro.Settings.DeleteThisOption(this)';
$deleteOptConfirm->initConfirm();

$removeCacheConfirm               = new DUP_PRO_UI_Dialog();
$removeCacheConfirm->title        = __('This process will remove all build cache files.', 'duplicator-pro');
$removeCacheConfirm->message      = __('Be sure no packages are currently building or else they will be cancelled.', 'duplicator-pro');
$removeCacheConfirm->progressText = $deleteOptConfirm->progressText;
$removeCacheConfirm->jsCallback   = 'DupPro.Tools.ClearBuildCacheRun()';
$removeCacheConfirm->initConfirm();
?>
<script>
    jQuery(document).ready(function ($) {

        DupPro.Tools.removeInstallerFiles = function () {
            window.location = <?php echo json_encode(ToolsPageController::getInstance()->getCleanFilesAcrtionUrl()); ?>;
            return false;
        };

        DupPro.Tools.ClearBuildCache = function () {
            <?php $removeCacheConfirm->showConfirm(); ?>
        };

        DupPro.Tools.ClearBuildCacheRun = function () {
            window.location = <?php echo json_encode(ToolsPageController::getInstance()->getRemoveCacheActionUrl()); ?>;
        };
    });
</script>
