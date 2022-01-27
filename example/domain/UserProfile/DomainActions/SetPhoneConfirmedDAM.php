<?php

namespace app\domain\UserProfile\DomainActions;

use app\domain\UserProfile\Events\StoreModelEvent;
use app\domain\UserProfile\UserProfileDM;
use Brezgalov\DomainModel\BaseDomainActionModel;

/**
 * Class SetPhoneConfirmedDAM
 *
 * Ставим юзеру отметку про то, что его телефон подтвержден
 *
 * @package app\domain\UserProfile\DomainActions
 */
class SetPhoneConfirmedDAM extends BaseDomainActionModel
{
    /**
     * @var UserProfileDM
     */
    protected $model;

    /**
     * @return bool
     */
    public function run()
    {
        if (empty($this->model->user->phone)) {
            $this->model->addError('phone', 'Необходимо указать телефон в профиле, прежде чем его подтверждать');
            return false;
        }

        if ($this->model->user->phone_confirmed_mark) {
            $this->model->addError('phone', 'Ваш номер телефона уже подтвержден');
            return false;
        }

        $this->model->user->phone_confirmed_mark = date('Y-m-d H:i:s');

        $this->model->delayEventByKey(new StoreModelEvent($this->model), UserProfileDM::EVENT_STORE_MODEL);

        return true;
    }
}