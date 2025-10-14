<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Ajax;

use DUP_PRO_Handler;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\CapMng;
use Duplicator\Libs\Snap\SnapJson;
use Duplicator\Models\BrandEntity;
use Exception;

class ServicesBrand extends AbstractAjaxService
{
    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init()
    {
        if (!License::can(License::CAPABILITY_PRO_BASE)) {
            return;
        }

        $this->addAjaxCall('wp_ajax_duplicator_pro_brand_delete', 'brandDelete');
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_brand_delete
     *
     * @return never
     */
    public function brandDelete()
    {
        DUP_PRO_Handler::init_error_handler();
        check_ajax_referer('duplicator_pro_brand_delete', 'nonce');

        $json      = array(
            'success' => false,
            'message' => '',
        );
        $isValid   = true;
        $inputData = filter_input_array(INPUT_POST, array(
            'brand_ids' => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => array('default' => false),
            ),
        ));
        $brandIDs  = $inputData['brand_ids'];
        $delCount  = 0;

        if (empty($brandIDs) || in_array(false, $brandIDs)) {
            $isValid = false;
        }

        try {
            CapMng::can(CapMng::CAP_CREATE);
            if (!$isValid) {
                throw new Exception(__('Invalid Request.', 'duplicator-pro'));
            }

            foreach ($brandIDs as $id) {
                $brand = BrandEntity::deleteById($id);
                if ($brand) {
                    $delCount++;
                }
            }

            $json['success'] = true;
            $json['ids']     = $brandIDs;
            $json['removed'] = $delCount;
        } catch (Exception $e) {
            $json['message'] = $e->getMessage();
        }

        die(SnapJson::jsonEncode($json));
    }
}
