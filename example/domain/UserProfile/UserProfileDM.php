<?php

namespace app\domain\UserProfile;

use app\dao\models\UsersDao;
use app\domain\DriverSelfArrival\DriverSelfArrivalDM;
use app\domain\UserProfile\DomainActions\RequestConfirmPhoneDAM;
use app\domain\UserProfile\DomainActions\SendSmsCodeDAM;
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

    /**
     * @var UsersDao
     */
    public $user;

    /**
     * @return array
     */
    public function actions()
    {
        return [
            /**
             * Метод запроса кода и отправки смс с кодом на номер пользователя
             * Принимает
             */
            static::METHOD_REQUEST_PHONE_CONFIRM => RequestConfirmPhoneDAM::class,

            /**
             * Метод подтверждения телефона
             * Тут мы задокументируем все особенности метода и
             * коротко опишем что делаем
             */
            static::METHOD_SUBMIT_PHONE_CONFIRM => SubmitConfirmPhoneDAM::class,

            /**
             * Проставляем отметку, что телефон подтвержден
             */
            static::METHOD_SET_PHONE_CONFIRMED => SetPhoneConfirmedDAM::class,

            /**
             * Обновление профиля
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
        $firstParent = ArrayHelper::getValue($this->crossDomainOrigin, 0);

        return ArrayHelper::getValue([
            // Используется при прибытии водителя
            DriverSelfArrivalDM::class => [
                static::METHOD_SUBMIT_PHONE_CONFIRM,
            ],

            // Для внутреннего использования
            UserProfileDM::class => [
                static::METHOD_SET_PHONE_CONFIRMED,
            ],
        ], $firstParent, []);
    }
}
