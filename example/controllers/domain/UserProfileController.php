<?php

namespace app\controllers\domain;

use app\controllers\BaseActiveController;
use app\domain\UserProfile\DomainActions\RequestConfirmPhoneDAM;
use app\domain\UserProfile\Formatters\AllFineUserProfileFormatter;
use app\domain\UserProfile\UserProfileDM;
use app\domain\UserProfile\UserProfileDMRepository;
use Brezgalov\DomainModel\Services\ActionAdapterService;
use yii\rest\OptionsAction;

class UserProfileController extends BaseActiveController
{
    public function actions()
    {
        $userProfileRepo = UserProfileDMRepository::class;

        return [
            /**
             * Заглушка для фронта
             */
            'options' => OptionsAction::class,

            /**
             * Запрос на получение кода по смс
             * @see RequestConfirmPhoneDAM::run()
             */
            'request-phone-confirm' => [
                'class' => ActionAdapterService::class,
                'repository' => $userProfileRepo,
                'actionName' => UserProfileDM::METHOD_REQUEST_PHONE_CONFIRM,
                'formatter' => AllFineUserProfileFormatter::class,
            ],

            /**
             * Принимаем и проверяем код отправленный из request-phone-confirm
             * @see SubmitConfirmPhoneDAM::run()
             */
            'submit-phone-confirm' => [
                'class' => ActionAdapterService::class,
                'repository' => $userProfileRepo,
                'actionName' => UserProfileDM::METHOD_SUBMIT_PHONE_CONFIRM,
                'formatter' => AllFineUserProfileFormatter::class,
            ],
        ];
    }
}