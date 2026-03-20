<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Collects System information.
 * Admin Menu: Service -> System Requirements -> Main.
 */
class System_Requirements_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $o_sys_req = ox_new(\Oxid_Esales\Eshop\Core\System_Requirements::class);
        $this->_a_view_data['aInfo'] = $o_sys_req->get_system_info();
        $this->_a_view_data['aCollations'] = $o_sys_req->check_collation();
        return 'sysreq_main';
    }
    /**
     * Returns module state
     *
     * @param int $iModuleState state integer value
     *
     * @return string
     */
    public function get_module_class($i_module_state)
    {
        return match ($i_module_state) {
            2 => 'pass',
            1 => 'pmin',
            -1 => 'null',
            default => 'fail',
        };
    }
    /**
     * Returns hint URL
     *
     * @param string $sIdent Module ident
     *
     * @return string
     */
    public function get_req_info_url($s_ident)
    {
        $o_sys_req = ox_new(\Oxid_Esales\Eshop\Core\System_Requirements::class);
        return $o_sys_req->get_req_info_url($s_ident);
    }
}