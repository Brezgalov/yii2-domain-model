<?php

namespace app\domain\UserProfile;

use app\dao\repositories\UsersDaoRepository;
use app\traits\GetUserTrait;
use Brezgalov\DomainModel\BaseRepository;
use Brezgalov\DomainModel\Exceptions\ErrorException;
use Brezgalov\DomainModel\IDomainModelRepository;

class UserProfileDMRepository extends BaseRepository
{
    use GetUserTrait;

    /**
     * @var integer
     */
    public $id;

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

        if (empty($this->user)) {
            $this->getUser();
        }

        if (!empty($this->user)) {
            $this->id = $this->user->id;
        }
    }

    /**
     * @return array[]
     */
    public function rules()
    {
        return [
            [['id'], 'required', 'message' => 'Укажите ID пользователя для работы с профилем'],
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
        $daoRepo->id = $this->id;

        $userDao = $daoRepo->getQuery()->one();
        if (empty($userDao)) {
            ErrorException::throwAsModelError('id', 'Не удается найти профиль пользователя');
        }

        $model->user = $userDao;

        return $model;
    }
}