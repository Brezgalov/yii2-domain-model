<?php

namespace app\domain\UserProfile\Formatters;

use app\domain\UserProfile\DTO\UserProfileDto;
use app\domain\UserProfile\UserProfileDM;
use Brezgalov\DomainModel\IDomainModel;
use Brezgalov\DomainModel\ResultFormatters\ModelResultFormatter;

class AllFineUserProfileFormatter extends ModelResultFormatter
{
    /**
     * @param IDomainModel $model
     * @param mixed $result
     * @return UserProfileDto|array[]|object|\yii\base\Model|\yii\web\Response|null
     * @throws \Exception
     */
    public function format($model, $result)
    {
        /** @var UserProfileDM $model */

        if ($result === true) {
            return new UserProfileDto(['user' => $model->user]);
        }

        return parent::format($model, $result);
    }
}