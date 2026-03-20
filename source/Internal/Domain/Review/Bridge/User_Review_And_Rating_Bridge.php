<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Domain\Review\Bridge;

use Doctrine\Common\Collections\Array_Collection;
use Oxid_Esales\Eshop\Application\Model\Article;
use Oxid_Esales\Eshop\Application\Model\Recommendation_List;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Exception\Review_And_Rating_Object_Type_Exception;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\Service\User_Review_And_Rating_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Domain\Review\View_Data_Object\Review_And_Rating;
class User_Review_And_Rating_Bridge implements User_Review_And_Rating_Bridge_Interface
{
    public function __construct(private readonly User_Review_And_Rating_Service_Interface $user_review_and_rating_service)
    {
    }
    /**
     * Get number of reviews by given user.
     *
     * @param string $userId
     *
     * @return int
     */
    public function get_review_and_rating_list_count($user_id)
    {
        return $this->user_review_and_rating_service->get_review_and_rating_list_count($user_id);
    }
    /**
     * Returns Collection of User Ratings and Reviews.
     *
     * @param string $userId
     *
     * @return array
     */
    public function get_review_and_rating_list($user_id)
    {
        $review_and_rating_list = $this->user_review_and_rating_service->get_review_and_rating_list($user_id);
        $this->prepare_rating_and_review_properties_data($review_and_rating_list);
        return $review_and_rating_list->to_array();
    }
    /**
     * Prepare RatingAndReview properties data.
     *
     * @param ArrayCollection $reviewAndRatingList
     */
    private function prepare_rating_and_review_properties_data($review_and_rating_list): void
    {
        foreach ($review_and_rating_list as $review_and_rating) {
            $this->set_object_title_to_review_and_rating($review_and_rating);
            $this->format_review_text($review_and_rating);
            $this->format_review_and_rating_date($review_and_rating);
        }
    }
    /**
     * Formats Review text.
     */
    private function format_review_text(Review_And_Rating $review_and_rating): void
    {
        $prepared_text = htmlspecialchars($review_and_rating->get_review_text());
        $review_and_rating->set_review_text($prepared_text);
    }
    /**
     * Formats ReviewAndRating date.
     */
    private function format_review_and_rating_date(Review_And_Rating $review_and_rating): void
    {
        $formatted_date = Registry::get_utils_date()->format_db_date($review_and_rating->get_created_at());
        $review_and_rating->set_created_at($formatted_date);
    }
    /**
     * Sets object title to ReviewAndRating.
     */
    private function set_object_title_to_review_and_rating(Review_And_Rating $review_and_rating): void
    {
        $title = $this->get_object_title($review_and_rating->get_object_type(), $review_and_rating->get_object_id());
        $review_and_rating->set_object_title($title);
    }
    /**
     * Returns object title.
     *
     * @param string $type
     * @param string $objectId
     *
     * @return string
     */
    private function get_object_title($type, $object_id)
    {
        $object_model = $this->get_object_model($type);
        $object_model->load($object_id);
        $field_name = $this->get_object_title_field_name($type);
        $field = $object_model->{$field_name};
        return $field ? $field->value : '';
    }
    /**
     * Returns object model.
     *
     * @param string $type
     *
     * @throws ReviewAndRatingObjectTypeException
     */
    private function get_object_model($type): Article|Recommendation_List
    {
        if ($type === 'oxarticle') {
            $model = ox_new(Article::class);
        }
        if ($type === 'oxrecommlist') {
            $model = ox_new(Recommendation_List::class);
        }
        if (!isset($model)) {
            throw new Review_And_Rating_Object_Type_Exception();
        }
        return $model;
    }
    /**
     * Returns field name of the object title.
     *
     * @param string $type
     *
     * @throws ReviewAndRatingObjectTypeException
     */
    private function get_object_title_field_name($type): string
    {
        if ($type === 'oxarticle') {
            $field_name = 'oxarticles__oxtitle';
        }
        if ($type === 'oxrecommlist') {
            $field_name = 'oxrecommlists__oxtitle';
        }
        if (!isset($field_name)) {
            throw new Review_And_Rating_Object_Type_Exception();
        }
        return $field_name;
    }
}