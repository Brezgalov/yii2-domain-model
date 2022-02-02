<?php

namespace app\domain\UserProfile;

use app\dao\models\UsersDao;
use app\domain\DriverSelfArrival\DriverSelfArrivalDM;
use app\domain\UserProfile\DomainActions\RequestConfirmPhoneDAM;
use app\domain\UserProfile\DomainActions\SetPhoneConfirmedDAM;
use app\domain\UserProfile\DomainActions\SubmitConfirmPhoneDAM;
use app\domain\UserProfile\DomainActions\UpdateProfileDAM;
use Brezgalov\DomainModel\BaseDomainModel;
use yii\helpers\ArrayHelper;

class UserProfileDM extends BaseDomainModel
{
    const METHOD_REQUEST_PHONE_CONFIRM = 'requestPhoneConfirm';
    const METHOD_SUBMIT_PHONE_CONFIRM = 'submitPhoneConfirm';
    const METHOD_SET_PHONE_CONFIRMED = 'setPhoneConfirmed';
    const METHOD_UPDATE_PROFILE = 'updateProfile';

    const EVENT_STORE_MODEL = 'storeModel';

    const ERROR_ALREADY_CONFIRMED = 'Данный номер телефона уже подтвержден';

    /**
     * @var UsersDao
     */
    public $user;

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->user && $this->user->validate();
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            /**
             * Метод запроса кода и отправки смс с кодом на номер пользователя
             */
            static::METHOD_REQUEST_PHONE_CONFIRM => RequestConfirmPhoneDAM::class,

            /**
             * Метод подтверждения телефона, принимает код из смс и номер пользователя,
             * который подтверждается
             */
            static::METHOD_SUBMIT_PHONE_CONFIRM => SubmitConfirmPhoneDAM::class,

            /**
             * Проставляем отметку, что телефон подтвержден. Утилитарная штука, обновляет профиль
             */
            static::METHOD_SET_PHONE_CONFIRMED => SetPhoneConfirmedDAM::class,

            /**
             * Обновление профиля по полям
             * Изменение телефона - сбрасывает отметку, что номер подтвержден
             */
            static::METHOD_UPDATE_PROFILE => UpdateProfileDAM::class,
        ];
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function crossDomainActionsAllowed()
    {
        $domainOrigins = $this->crossDomainOrigin;
        $lastParent = array_pop($domainOrigins);

        return ArrayHelper::getValue([
            // Используется при прибытии водителя
            DriverSelfArrivalDM::class => [
                static::METHOD_SUBMIT_PHONE_CONFIRM,
            ],

            // Для внутреннего использования
            UserProfileDM::class => [
                UserProfileDM::METHOD_SET_PHONE_CONFIRMED,
            ],
        ], $lastParent, []);
    }
}
