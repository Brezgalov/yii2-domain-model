<?php

namespace example\domain\UserProfile;

use example\dao\repositories\UsersDaoRepository;
use Brezgalov\DomainModel\BaseRepository;
use Brezgalov\DomainModel\Exceptions\ErrorException;

class UserProfileDMByPhoneRepository extends BaseRepository
{
    /**
     * @var integer
     */
    public $phone;

    /**
     * @var UsersDaoRepository
     */
    public $usersDaoRepo;

    /**
     * UserProfileDMRepository constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->usersDaoRepo)) {
            $this->usersDaoRepo = new UsersDaoRepository();
        }
    }

    /**
     * @return array[]
     */
    public function rules()
    {
        return [
            [['phone'], 'required', 'message' => 'Укажите телефон пользователя для работы с профилем'],
        ];
    }

    /**
     * @return UserProfileDM
     * @throws ErrorException
     */
    public function loadDomainModel()
    {
        $model = new UserProfileDM();

        $daoRepo = clone $this->usersDaoRepo;
        $daoRepo->phone = $this->phone;

        $userDao = $daoRepo->getQuery()->one();
        if (empty($userDao)) {
            ErrorException::throwAsModelError('phone', 'Не удается найти профиль пользователя по указанному телефону');
        }

        $model->user = $userDao;

        return $model;
    }
}