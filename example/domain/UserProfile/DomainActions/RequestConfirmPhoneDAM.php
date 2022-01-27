<?php

namespace app\domain\UserProfile\DomainActions;

use app\domain\Notifications\NotificationsDM;
use app\dao\repositories\SmsCodesDaoRepository;
use app\dao\repositories\UsersDaoRepository;
use app\domain\UserProfile\UserProfileDM;
use app\forms\UserSmsCodeSenderService;
use app\helpers\PhoneHelper;
use Brezgalov\DomainModel\BaseDomainActionModel;
use Brezgalov\DomainModel\IDomainModel;
use yii\base\InvalidConfigException;

class RequestConfirmPhoneDAM extends BaseDomainActionModel
{
    /**
     * @var UserProfileDM
     */
    protected $model;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var UserSmsCodeSenderService
     */
    public $userSmsCodeSenderService;

    /**
     * @var SmsCodesDaoRepository
     */
    public $smsCodesRepo;

    /**
     * @var UsersDaoRepository
     */
    public $usersRepo;

    /**
     * RequestConfirmPhoneDAM constructor.
     * @param IDomainModel $model
     * @param array $config
     */
    public function __construct(IDomainModel $model, $config = [])
    {
        parent::__construct($model, $config);

        if (!$this->smsCodesRepo) {
            $this->smsCodesRepo = new SmsCodesDaoRepository();
        }

        if ($this->usersRepo) {
            $this->usersRepo = new UsersDaoRepository();
        }

        if (empty($this->userSmsCodeSenderService)) {
            $this->userSmsCodeSenderService = new UserSmsCodeSenderService();
        }
    }

    /**
     * @return array[]
     */
    public function rules()
    {
        return [
            [['phone'], 'string'],
        ];
    }

    public function run()
    {
        $phoneInput = $this->phone ? PhoneHelper::clearPhone($this->phone) : null;

        if ($phoneInput) {
            $alreadyConfirmed =
                !empty($this->model->user->phone_confirmed_mark) &&
                $phoneInput == $this->model->user->phone;

            if ($alreadyConfirmed) {
                $this->model->addError('phone', 'Данный номер телефона уже подтвержден');
                return false;
            }

            $callResult = $this->model->crossDomainCall(
                $this->model,
                UserProfileDM::METHOD_UPDATE_PROFILE,
                ['phone' => $phoneInput]
            );

            if (!$callResult->result) {
                return false;
            }
        }

        $callResult = $this->model->crossDomainCall(
            NotificationsDM::class,
            NotificationsDM::METHOD_SEND_SMS_CODE,
            ['phone' => $this->model->user->phone]
        );

        if ($callResult->result === false) {
            /** @var NotificationsDM $notificationsDM */
            $notificationsDM = $callResult->model;

            $this->model->addErrors($notificationsDM->getErrors());
            return false;
        }

        return true;
    }
}