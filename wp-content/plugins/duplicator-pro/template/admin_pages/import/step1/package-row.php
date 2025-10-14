<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

use Duplicator\Package\Import\PackageImporter;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 * @var array<string, mixed> $tplData
 */

/** @var PackageImporter $importObj  */
$importObj = $tplData['importObj'];

if ($importObj instanceof PackageImporter) {
    $name             = $importObj->getName();
    $size             = $importObj->getSize();
    $created          = $importObj->getCreated();
    $archivePath      = $importObj->getFullPath();
    $htmlDetails      = $importObj->getHtmlDetails(false);
    $installPakageUrl = $importObj->getInstallerPageLink();
    $isImportable     = $importObj->isImportable();
    $funcsEnalbed     = true;
} else {
    $name             = '';
    $size             = '';
    $created          = '';
    $archivePath      = '';
    $htmlDetails      = '';
    $installPakageUrl = '';
    $isImportable     = false;
    $funcsEnalbed     = false;
}

$idHtml                 = strlen($tplData['idRow']) ?  'id="' . esc_attr($tplData['idRow']) . '" ' : '' ;
$rowClasses             = array('dup-pro-import-package');
$installerActionClasses = array(
    'dup-pro-import-action-install',
    'button',
    'button-primary',
);
if ($isImportable) {
    $rowClasses[] = 'is-importable';
} else {
    $installerActionClasses[] = 'disabled';
}
?>
<tr <?php echo $idHtml; ?> class="<?php echo esc_attr(implode(' ', $rowClasses)) ?>" data-path="<?php echo esc_attr($archivePath); ?>" >
    <td class="name">
        <span class="text"><?php echo esc_html($name); ?></span>
        <div class="dup-pro-import-package-detail no-display" >
            <?php echo $htmlDetails; ?>
        </div>
    </td>
    <td class="size">
        <span title="<?php printf(esc_attr__('Total %d bytes', 'duplicator-pro'), esc_attr($size)); ?>" >
            <?php echo esc_html(DUP_PRO_U::byteSize($size)); ?>
        </span>
    </td>
    <td class="created">
        <?php echo esc_html($created); ?>
    </td>
    <td class="funcs">
        <div class="actions <?php echo $funcsEnalbed ? '' : 'no-display'; ?>" >
            <button type="button" class="button dup-pro-import-action-package-detail-toggle" >
                <i class="fa fa-caret-down"></i> <?php esc_html_e('Details', 'duplicator-pro'); ?>
            </button> 
            <span class="separator" ></span>
            <button type="button" class="dup-pro-import-action-remove button button-secondary" >
                <i class="fa fa-ban"></i> <?php esc_html_e('Remove', 'duplicator-pro'); ?>
            </button>
           <span class="separator" ></span>
            <button type="button" class="dup-pro-import-action-install button button-primary" 
                data-install-url="<?php echo esc_url($installPakageUrl); ?>" 
                <?php echo $isImportable ? '' : 'disabled'; ?>>
                <i class="fa fa-bolt fa-sm"></i> <?php esc_html_e('Continue', 'duplicator-pro'); ?>
            </button>
        </div>
        <div class="invalid no-display" >
            Package invalid
        </div>
        <div class="dup-pro-loader no-display" >
            <div class="dup-pro-meter-wrapper" >
                <div class="dup-pro-meter blue">
                    <span style="width: 0%"></span>
                </div>
                <span class="text">0%</span>
            </div>
            <a href="" class="dup-pro-import-action-cancel-upload button button-cancel" >
                <i class="fa fa-ban"></i> <?php esc_html_e('Cancel', 'duplicator-pro'); ?>
            </a>
        </div>
    </td>
</tr> 
