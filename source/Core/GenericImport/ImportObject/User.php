<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Generic_Import\Import_Object;

use Exception;
/**
 * Import object for Users.
 */
class User extends \Oxid_Esales\Eshop\Core\Generic_Import\Import_Object\Import_Object
{
    /** @var string Database table name. */
    protected $table_name = 'oxuser';
    /** @var string Shop object name. */
    protected $shop_object_name = 'oxuser';
    /**
     * Imports user. Returns import status.
     *
     * @param array $data db row array
     *
     * @throws Exception If user exists with provided OXID, throw an exception.
     *
     * @return string $oxid on success, bool FALSE on failure
     */
    public function import($data)
    {
        if (isset($data['OXUSERNAME'])) {
            $id = $data['OXID'];
            $user_name = $data['OXUSERNAME'];
            $user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class, 'core');
            $user->oxuser__oxusername = new \Oxid_Esales\Eshop\Core\Field($user_name, \Oxid_Esales\Eshop\Core\Field::T_RAW);
            if ($user->exists($id) && $id != $user->get_id()) {
                throw new Exception("USER {$user_name} already exists!");
            }
        }
        return parent::import($data);
    }
}