<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Package;

use DUP_PRO_Package;
use Duplicator\Installer\Models\MigrateData;
use Duplicator\Package\Recovery\RecoveryPackage;

class PackageUtils
{
    /**
     * Update CREATED AFTER INSTALL FLAGS
     *
     * @param MigrateData $migrationData migration data
     *
     * @return void
     */
    public static function updateCreatedAfterInstallFlags(MigrateData $migrationData)
    {
        if ($migrationData->restoreBackupMode == false) {
            return;
        }

        // Refresh recovery package set beforw backup
        $ids = DUP_PRO_Package::dbSelect('FIND_IN_SET(\'' . DUP_PRO_Package::FLAG_DISASTER_SET . '\', `flags`)', 0, 0, '', 'ids');
        if (count($ids)) {
            RecoveryPackage::setRecoveablePackage($ids[0]);
        }

        // Update all backups with created after restore flag or created after install time
        DUP_PRO_Package::dbSelectCallback(
            function (DUP_PRO_Package $package) {
                $package->updateMigrateAfterInstallFlag();
                $package->save();
            },
            'FIND_IN_SET(\'' . DUP_PRO_Package::FLAG_CREATED_AFTER_RESTORE . '\', `flags`) OR 
            (
                `id` > ' .  $migrationData->packageId . ' AND
                `created` < \'' . esc_sql($migrationData->installTime) . '\'
            )'
        );
    }
}
