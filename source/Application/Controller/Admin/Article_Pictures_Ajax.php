<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Extension_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Size_Too_Large_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\File_Size_Too_Small_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Media_Validation_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Base_Type_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Guess_Mismatch_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Mime_Type_Guess_Failed_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Media\Validator\Exception\Upload_Invalid_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Data_Object\Product_Media_Role;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service\Product_Media_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Product\Media\Service\Product_Media_Upload_Processor_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Symfony\Component\Http_Foundation\File_Bag;
use Symfony\Component\Http_Foundation\Input_Bag;
use Symfony\Component\Http_Foundation\Json_Response;
use Symfony\Component\Http_Foundation\Request;
class Article_Pictures_Ajax extends List_Component_Ajax
{
    private readonly Product_Media_Upload_Processor_Interface $product_media_upload_processor;
    private readonly Product_Media_Service_Interface $product_media_service;
    private readonly Input_Bag $request_data;
    private readonly File_Bag $request_files;
    public function __construct()
    {
        $this->product_media_upload_processor = Container_Facade::get(Product_Media_Upload_Processor_Interface::class);
        $this->product_media_service = Container_Facade::get(Product_Media_Service_Interface::class);
        $this->request_data = Container_Facade::get(Request::class)->request;
        $this->request_files = Container_Facade::get(Request::class)->files;
    }
    public function add_media(): void
    {
        $errors = $this->process_uploaded_files();
        if ($errors !== []) {
            $this->send_errors_response($errors);
        }
    }
    public function replace_media(): void
    {
        $errors = $this->process_uploaded_files();
        if ($errors !== []) {
            $this->send_errors_response($errors);
            return;
        }
        $this->remove_role_from_media();
    }
    public function remove_media(): void
    {
        $this->remove_role_from_media();
    }
    public function toggle_media_active_state(): void
    {
        $product_media = $this->product_media_service->get($this->get_product_media_id());
        if ($product_media->is_active()) {
            $this->product_media_service->deactivate($product_media);
        } else {
            $this->product_media_service->activate($product_media);
        }
    }
    public function sort_media(): void
    {
        $this->product_media_service->sort(json_decode($this->request_data->get_string('sorting'), true, 512, JSON_THROW_ON_ERROR));
    }
    private function remove_role_from_media(): void
    {
        $product_media = $this->product_media_service->get($this->get_product_media_id());
        $product_media->get_role_set()->remove_role(Product_Media_Role::from($this->request_data->get_string('role')));
        if ($product_media->get_role_set()->get_roles()->is_empty()) {
            $this->product_media_service->remove($product_media->get_id());
        } else {
            $this->product_media_service->update($product_media);
        }
    }
    private function process_uploaded_files(): array
    {
        $errors = [];
        $product_id = Id::from_string($this->request_data->get_string('productId'));
        $role = Product_Media_Role::from($this->request_data->get_string('role'));
        foreach ($this->request_files->get('uploadedFiles') as $uploaded_file) {
            try {
                $product_media = $this->product_media_upload_processor->process($product_id, $uploaded_file);
            } catch (Media_Validation_Exception $e) {
                $errors[] = $this->format_error_with_filename($e, $uploaded_file->get_client_original_name());
                continue;
            }
            $product_media->get_role_set()->add_role($role);
            $this->product_media_service->add($product_media);
        }
        return $errors;
    }
    private function get_product_media_id(): Id
    {
        return Id::from_string($this->request_data->get_string('productMediaId'));
    }
    private function map_exception_to_translation(\Throwable $e): array
    {
        return match (true) {
            $e instanceof File_Size_Too_Small_Exception => ['ERR_MEDIA_SIZE_TOO_SMALL', [$e->get_actual_formatted(), $e->get_min_formatted()]],
            $e instanceof File_Size_Too_Large_Exception => ['ERR_MEDIA_SIZE_TOO_LARGE', [$e->get_actual_formatted(), $e->get_max_formatted()]],
            $e instanceof Mime_Base_Type_Mismatch_Exception => ['ERR_MEDIA_MIME_BASETYPE_MISMATCH', [$e->get_guessed_mime(), $e->get_required_base_prefix()]],
            $e instanceof Mime_Guess_Mismatch_Exception => ['ERR_MEDIA_MIME_GUESS_MISMATCH', [$e->get_guessed_mime(), $e->get_client_mime()]],
            $e instanceof Mime_Type_Guess_Failed_Exception => ['ERR_MEDIA_MIME_GUESS_FAILED', []],
            $e instanceof File_Extension_Mismatch_Exception => ['ERR_MEDIA_EXTENSION_MISMATCH', [$e->get_client_extension(), \implode(', ', $e->get_valid_extensions())]],
            $e instanceof Upload_Invalid_Exception => (function () use ($e): array {
                $key = 'EXCEPTION_FILEUPLOADERROR_' . $e->get_error_code();
                $values = [];
                if ($e->get_error_code() === \UPLOAD_ERR_INI_SIZE) {
                    $values[] = (string) \ini_get('upload_max_filesize');
                }
                return [$key, $values];
            })(),
        };
    }
    private function format_error_with_filename(\Throwable $e, string $filename): string
    {
        [$key, $values] = $this->map_exception_to_translation($e);
        $error_message = \sprintf(Registry::get_lang()->translate_string($key), ...$values);
        return \sprintf('%s: %s', $filename, $error_message);
    }
    private function send_errors_response(array $errors): void
    {
        (new Json_Response(['errors' => $errors]))->send();
    }
}