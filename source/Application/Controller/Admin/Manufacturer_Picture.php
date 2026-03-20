<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller;
use Oxid_Esales\Eshop\Application\Model\Manufacturer;
use Oxid_Esales\Eshop\Core\Exception\Exception_To_Display;
use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin manufacturer picture screen.
 * Handle manufacturer picture actions.
 */
class Manufacturer_Picture extends Admin_Details_Controller
{
    public function render(): string
    {
        parent::render();
        $this->_a_view_data['edit'] = $manufacturer = ox_new(Manufacturer::class);
        $oxid = $this->get_edit_object_id();
        if (isset($oxid) && $oxid != '-1') {
            $manufacturer->load($oxid);
        }
        return 'manufacturer_picture';
    }
    public function save(): void
    {
        if (Registry::get_config()->is_demo_shop()) {
            $this->show_error('MANUFACTURER_PICTURES_UPLOAD_IS_DISABLED');
            return;
        }
        if (!$this->validate_request_images()) {
            Registry::get_utils_view()->add_error_to_display('ERROR_MESSAGE_WRONG_IMAGE_FILE_TYPE');
            return;
        }
        parent::save();
        $manufacturer = ox_new(Manufacturer::class);
        if ($manufacturer->load($this->get_edit_object_id())) {
            $this->fetch_changes($manufacturer);
            $manufacturer->assign(Registry::get_request()->get_request_escaped_parameter('editval'));
            $manufacturer = Registry::get_utils_file()->process_files($manufacturer);
            $this->check_new_images_count();
            $manufacturer->save();
            $this->set_edit_object_id($manufacturer->get_id());
        }
    }
    public function delete_picture(): void
    {
        if (Registry::get_config()->is_demo_shop()) {
            $this->show_error('MANUFACTURER_PICTURES_UPLOAD_IS_DISABLED');
            return;
        }
        $picture_field_name = Registry::get_request()->get_request_escaped_parameter('masterPictureField');
        if (empty($picture_field_name)) {
            return;
        }
        $manufacturer = ox_new(Manufacturer::class);
        $manufacturer->load($this->get_edit_object_id());
        $picture_key = 'oxmanufacturers__' . $picture_field_name;
        $picture_type = $manufacturer->get_image_type($picture_field_name);
        if ($picture_type !== false) {
            $manufacturer->delete_picture($manufacturer->{$picture_key}->value, $picture_type, $picture_field_name);
            $manufacturer->{$picture_key} = new Field();
            $manufacturer->save();
        }
    }
    private function fetch_changes(Manufacturer $manufacturer): array
    {
        $changes = [];
        foreach (Registry::get_request()->get_request_escaped_parameter('editval') as $field_name => $value) {
            if ($manufacturer->{$field_name}->value !== $value) {
                $changes[] = $manufacturer->{$field_name}->value;
            }
        }
        return $changes;
    }
    private function check_new_images_count(): void
    {
        if (Registry::get_utils_file()->get_new_files_counter() == 0) {
            $this->show_error('NO_PICTURES_CHANGES');
        }
    }
    private function show_error(string $message, bool $is_bl_full = false): void
    {
        $exception = ox_new(Exception_To_Display::class);
        $exception->set_message($message);
        Registry::get_utils_view()->add_error_to_display($exception, $is_bl_full);
    }
}