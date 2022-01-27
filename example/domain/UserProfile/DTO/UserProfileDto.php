<?php

namespace app\domain\UserProfile\DTO;

use app\models\StevedoreUnloads;
use app\models\Users;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class UserProfileDto extends Model
{
    /**
     * @var Users
     */
    public $user;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $login;

    /**
     * @var string
     */
    public $first_name;

    /**
     * @var string
     */
    public $last_name;

    /**
     * @var string
     */
    public $phone;

    /**
     * @var string
     */
    public $phone_confirmed_mark;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $supplier_id;

    /**
     * @var null|string
     */
    public $role = null;

    /**
     * @var array
     */
    public $unloads = [];

    /**
     * UserProfileDto constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if ($this->user) {
            $this->load($this->user->toArray(), '');

            $roles = \Yii::$app->authManager->getRolesByUser($this->user->id);
            $role = array_shift($roles);
            $this->role = ArrayHelper::getValue($role, 'name');

            $this->unloads = $this->user->stevedoreUnloads;
        }
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            [['id', 'login', 'first_name', 'last_name', 'phone', 'email', 'supplier_id', 'phone_confirmed_mark'], 'safe']
        ];
    }

    /**
     * @return array
     */
    public function fields(): array
    {
        return [
            'id',
            'login',
            'first_name',
            'last_name',
            'phone',
            'phone_confirmed_mark',
            'email',
            'supplier_id',
            'role',
            'unloads',
            'default_unload' => 'defaultUnload',
        ];
    }

    /**
     * @return StevedoreUnloads|null
     */
    public function getDefaultUnload(): ?StevedoreUnloads
    {
        return array_shift($this->unloads);
    }
}