<?php

namespace app\domain\UserProfile\DomainActions;

use app\dao\repositories\SmsCodesDaoRepository;
use app\domain\UserProfile\UserProfileDM;
use app\helpers\PhoneHelper;
use Brezgalov\DomainModel\BaseDomainActionModel;
use Brezgalov\DomainModel\Exceptions\ErrorException;
use Brezgalov\DomainModel\IDomainModel;

class SubmitConfirmPhoneDAM extends BaseDomainActionModel
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
     * @var string
     */
    public $code;

    /**
     * @var SmsCodesDaoRepository
     */
    public $smsCodesRepo;

    /**
     * SubmitPhoneDAM constructor.
     * @param IDomainModel $model
     * @param array $config
     */
    public function __construct(IDomainModel $model, $config = [])
    {
        parent::__construct($model, $config);

        if (empty($this->smsCodesRepo)) {
            $this->smsCodesRepo = new SmsCodesDaoRepository();
        }
    }

    /**
     * @return array[]
     */
    public function rules()
    {
        return [
            [['code', 'phone'], 'required'],
        ];
    }

    /**
     * @return $this|mixed
     * @throws ErrorException
     */
    public function run()
    {
        if (!$this->validate()) {
            $this->model->addErrors($this->getErrors());
            return false;
        }

        $phone = PhoneHelper::clearPhone($this->phone);

        if ($this->model->user->phone !== $phone) {
            $this->model->addError('phone', "Телефон \"{$this->phone}\" не привязан к вашему профилю");
            return false;
        }

        $smsCodesRepo = clone $this->smsCodesRepo;
        $smsCodesRepo->code = $this->code;
        $smsCodesRepo->for_phone = $phone;

        $isValidCode = $smsCodesRepo->getQuery()->exists();

        if (!$isValidCode || !$phone) {
            $this->model->addError('code', 'Неверный код');
            return false;
        }

        $callResult = $this->model->crossDomainCall(
            $this->model,
            UserProfileDM::METHOD_SET_PHONE_CONFIRMED
        );

        if (!$callResult->result) {
            /** @var UserProfileDM $calledModel */
            $calledModel = $callResult->model;

            $this->model->addErrors($calledModel->getErrors());
            return false;
        }

        return true;
    }
}