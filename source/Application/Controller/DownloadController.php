<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Article file download page.
 */
class Download_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Prevents from loading any component as this controller
     * only returns file content if token is valid
     */
    public function init(): void
    {
        // empty for performance reasons
    }
    /**
     * Checks if given token is valid, formats HTTP headers,
     * and outputs file to buffer.
     *
     * If token is not valid, redirects to start page.
     */
    public function render(): void
    {
        $s_file_order_id = Registry::get_request()->get_request_escaped_parameter('sorderfileid');
        if ($s_file_order_id) {
            $o_article_file = ox_new(\Oxid_Esales\Eshop\Application\Model\File::class);
            try {
                /** @var \OxidEsales\Eshop\Application\Model\OrderFile $oOrderFile */
                $o_order_file = ox_new(\Oxid_Esales\Eshop\Application\Model\Order_File::class);
                if ($o_order_file->load($s_file_order_id)) {
                    $s_file_id = $o_order_file->get_file_id();
                    $bl_loaded_and_exists = $o_article_file->load($s_file_id) && $o_article_file->exist();
                    if ($s_file_id && $bl_loaded_and_exists && $o_order_file->process_order_file()) {
                        $o_article_file->download();
                    } else {
                        $s_error = 'ERROR_MESSAGE_FILE_DOESNOT_EXIST';
                    }
                }
            } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception) {
                $s_error = 'ERROR_MESSAGE_FILE_DOWNLOAD_FAILED';
            }
        } else {
            $s_error = 'ERROR_MESSAGE_WRONG_DOWNLOAD_LINK';
        }
        if ($s_error) {
            $o_ex = new \Oxid_Esales\Eshop\Core\Exception\Exception_To_Display();
            $o_ex->set_message($s_error);
            Registry::get_utils_view()->add_error_to_display($o_ex, false);
            Registry::get_utils()->redirect(Registry::get_config()->get_shop_url() . 'index.php?cl=account_downloads');
        }
    }
}