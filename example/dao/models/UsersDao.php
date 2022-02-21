<?php

namespace example\dao\models;

use example\behaviors\MatchUserExporterPhoneBehavior;
use example\models\Exporters;
use example\models\ExporterWhitePhones;
use example\models\LoadDays;
use example\models\Notifications;
use example\models\Permissions;
use example\models\Quotas;
use example\models\StevedoreUnloadLimits;
use example\models\StevedoreUnloads;
use example\models\Suppliers;
use example\models\TimeslotRequestAutofills;
use example\models\TimeslotRequestGroups;
use example\models\TimeslotRequests;
use example\models\Timeslots;
use example\models\Tokens;
use example\models\UsersStevedoreUnloads;
use example\models\UserTrucks;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use example\access\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rbac\ManagerInterface;
use yii\web\IdentityInterface;
use yii\base\InvalidConfigException;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $login
 * @property string|null $email
 * @property string|null $phone
 * @property string $name
 * @property string $first_name
 * @property string $last_name
 * @property string $token
 * @property string|null $password
 * @property int|null $supplier_id
 * @property string|null $deleted_at
 * @property string|null $phone_confirmed_mark
 *
 * @property ExporterWhitePhones[] $exporterWhitePhones
 * @property Exporters[] $exporters
 * @property LoadDays[] $loadDays
 * @property Notifications[] $notifications
 * @property Permissions[] $permissions
 * @property Quotas[] $quotas
 * @property StevedoreUnloadLimits[] $stevedoreUnloadLimits
 * @property TimeslotRequestGroups[] $timeslotRequestGroups
 * @property TimeslotRequests[] $timeslotRequests
 * @property Timeslots[] $timeslots
 * @property Tokens[] $tokens
 * @property UserTrucks[] $userTrucks
 * @property Suppliers $supplier
 * @property UsersStevedoreUnloads[] $usersStevedoreUnloads
 * @property StevedoreUnloads[] $stevedoreUnloads
 * @property TimeslotRequestAutofills[] $timeslotRequestAutofills
 */
class UsersDao extends ActiveRecord implements IdentityInterface
{
    const ROLE_ROOT = 'root';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_DRIVER = 'driver';
    const ROLE_PARKMAN = 'parkman';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_VIEWER = 'viewer';

    /**
     * @var string[]
     */
    public static $roles = [
        self::ROLE_ROOT,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_PARKMAN,
        self::ROLE_SUPERVISOR
    ];

    const CAN_CHECK_TIMESLOTS            = 'checkTimeslots';
    const CAN_MANAGE_TIMESLOTS           = 'manageTimeslots';
    const CAN_SEE_TIMESLOTS_PAGE         = 'seeTimeslotsPage';
    const CAN_MANAGE_BUFFER_TIMESLOTS    = 'manageBufferTimeslots';
    const CAN_CREATE_MANUAL_TIMESLOTS    = 'createManualTimeslots';
    const CAN_SUBMIT_TIMESLOT            = 'submitTimeslot';
    const CAN_DROP_TIMESLOT              = 'dropTimeslot';
    const CAN_SEE_SUPERVISOR_STATISTICS  = 'seeSupervisorStatistics';
    const CAN_DROP_TIMESLOT_REQUEST      = 'dropTimeslotRequests';
    const CAN_SEE_USERS_LIST             = 'seeUsersList';
    const CAN_MANAGE_USERS               = 'manageUsers';
    const CAN_EDIT_USER_PROFILE          = 'editUserProfile';
    const CAN_MANAGE_BUFFER_QUOTAS       = 'manageBufferQuotas';
    const CAN_SEE_QUOTAS_STATISTICS_PAGE = 'seeQuotasStatisticsPage';
    const CAN_SEE_BUFFER_TIMESLOTS_PAGE  = 'seeBufferTimeslotsPage';
    const CAN_MANAGE_EXPORTERS           = 'manageExporters';
    const CAN_MANAGE_CULTURES               = 'manageCultures';
    const CAN_MANAGE_QUOTAS                 = 'manageQuotas';
    const CAN_FORCE_UPDATE_TIMESLOTS        = 'forceUpdateTimeslots';
    const CAN_FORCE_UPDATE_EXACT_TIMESLOT   = 'forceUpdateExactTimeslot';
    const CAN_FORCE_DELETE_EXACT_TIMESLOT   = 'forceDeleteExactTimeslot';
    const CAN_FORCE_DELETE_TIMESLOTS        = 'forceDeleteTimeslots';
    const CAN_REJECT_BUFFER_TIMESLOTS       = 'canRejectBufferTimeslots';
    const CAN_SEE_ALL_TIMESLOT_REQUESTS     = 'canSeeAllTimeslotRequests';
    const CAN_CREATE_TIMESLOT_TIME_REQUESTS = 'canCreateTimeslotTimeRequests';
    const CAN_CREATE_TIMESLOT_MOVE_REQUESTS = 'canCreateTimeslotMoveRequests';
    const CAN_SET_USER_ROLES                = 'canSetUserRoles';
    const CAN_CREATE_STEVEDORE_UNLOADS      = 'canCreateStevedoreUnloads';
    const CAN_EDIT_STEVEDORE_UNLOAD         = 'canEditStevedoreUnload';
    const CAN_SEE_ONLY_OWN_TIMESLOTS        = 'canSeeOnlyOwnTimeslots';
    const CAN_SELF_ARRIVE                   = 'canSelfArrive';

    const CAN_READ_EXPORTERS_ANY_UNLOAD     = 'canReadExportersAnyUnload';
    const CAN_READ_EXPORTERS_ANY_EXPORTER   = 'canReadExportersAnyExporter';
    const CAN_CREATE_EXPORTERS              = 'canCreateExporters';
    const CAN_CREATE_EXPORTERS_ANY_UNLOAD   = 'canCreateExportersAnyUnload';
    const CAN_EDIT_EXPORTERS                = 'canEditExporters';
    const CAN_EDIT_EXPORTERS_ANY_UNLOAD     = 'canEditExportersAnyUnload';
    const CAN_DELETE_EXPORTERS              = 'canDeleteMyExporters';
    const CAN_DELETE_ANY_EXPORTERS          = 'canDeleteAnyExporters';

    const CAN_CREATE_EXPORTER_PHONES     = 'canCreateExporterPhones';
    const CAN_CREATE_EXPORTER_PHONES_ANY_UNLOAD   = 'canCreateExporterPhonesAnyUnload';
    const CAN_EDIT_EXPORTER_PHONES              = 'canEditExporterPhones';
    const CAN_EDIT_EXPORTER_PHONES_ANY_UNLOAD   = 'canEditExporterPhonesAnyUnload';
    const CAN_DELETE_EXPORTER_PHONES                = 'canDeleteMyExporterPhones';
    const CAN_DELETE_ANY_EXPORTER_PHONES     = 'canDeleteAnyExporterPhones';
    
    /**
     * Какая роль какие может задавать пользователям
     * @var string[][]
     */
    public static $rolesCanSetup = [
        self::ROLE_ROOT => [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_DRIVER,
            self::ROLE_PARKMAN,
            self::ROLE_SUPERVISOR,
            self::ROLE_VIEWER,
        ],
        self::ROLE_ADMIN => [
            self::ROLE_MANAGER,
            self::ROLE_DRIVER,
            self::ROLE_PARKMAN,
            self::ROLE_SUPERVISOR,
            self::ROLE_VIEWER,
        ],
    ];

    /**
     * @var string[]
     */
    public static $rolesCanCreate = [
        self::ROLE_MANAGER,
        self::ROLE_DRIVER,
        self::ROLE_PARKMAN,
        self::ROLE_SUPERVISOR,
    ];

    /**
     * Проверка что юзер может назначать указанную роль
     *
     * @param int $userId
     * @param string $role
     * @param ManagerInterface|null $authManager
     * @return bool
     * @throws \Exception
     */
    public static function userCanSetupRole(int $userId, string $role, ManagerInterface $authManager = null): bool
    {
        if (empty($authManager)) {
            $authManager = \Yii::$app->authManager;
        }

        $myRoles = $authManager->getRolesByUser($userId);
        $setRole = null;

        foreach ($myRoles as $myRole) {
            $rolesSetupAllowed = ArrayHelper::getValue(UsersDao::$rolesCanSetup, $myRole->name, []);

            if (in_array($role, $rolesSetupAllowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $ruleId
     * @param int $userId
     * @param array $args
     * @return bool
     * @throws InvalidConfigException
     */
    public static function checkRightsById($ruleId, int $userId, array $args = []): bool
    {
        if (!Yii::$app->has('access')) {
            throw new InvalidConfigException('ACCESS component is not included');
        }

        /* @var $access AccessControl */
        $access = Yii::$app->get('access');

        return $access->checkRightsById($ruleId, $userId, $args);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    /**
     * Finds an identity by the given ID.
     *
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        /** @var UsersDao $user */
        $user = self::find()
            ->innerJoinWith('tokens tokens')
            ->andWhere(['tokens.token' => $token])
            ->one();

        if (!empty($user)) {
            return $user;
        }

        return $token ? self::findOne(['token' => $token]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['login'], 'required'],
            [['supplier_id'], 'integer'],
            [['login', 'email', 'name', 'phone', 'first_name', 'last_name', 'token', 'password'], 'string', 'max' => 255],
            [['login', 'token'], 'unique'],
            [['supplier_id'], 'exist', 'skipOnError' => true, 'targetClass' => Suppliers::class, 'targetAttribute' => ['supplier_id' => 'id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            MatchUserExporterPhoneBehavior::class,
        ]);
    }

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['password'],
            $fields['token']
        );

        return $fields;
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string current user auth key
     */
    public function getAuthKey()
    {
        return $this->token;
    }

    /**
     * @param string $authKey
     * @return bool if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @param string $pass
     * @return bool
     */
    public function validatePassword($pass)
    {
        return Yii::$app->security->validatePassword($pass, $this->password);
    }

    /**
     * @param string $pass
     */
    public function setPassword($pass)
    {
        $this->password = Yii::$app->security->generatePasswordHash($pass);
    }

    /**
     * @param string $ruleId
     * @param array $args
     * @return bool
     * @throws InvalidConfigException
     */
    public function checkRights($ruleId, array $args = [])
    {
        return static::checkRightsById($ruleId, $this->id, $args);
    }

    /**
     * Проверка что юзер может назначать указанную роль
     *
     * @param string $role
     * @param ManagerInterface|null $authManager
     * @return bool
     * @throws \Exception
     */
    public function canSetupRole(string $role, ManagerInterface $authManager = null)
    {
        return static::userCanSetupRole($this->id, $role, $authManager);
    }

    /**
     * Gets query for [[LoadDays]].
     *
     * @return ActiveQuery
     */
    public function getLoadDays()
    {
        return $this->hasMany(LoadDays::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[Quotas]].
     *
     * @return ActiveQuery
     */
    public function getQuotas()
    {
        return $this->hasMany(Quotas::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[StevedoreUnloadLimits]].
     *
     * @return ActiveQuery
     */
    public function getStevedoreUnloadLimits()
    {
        return $this->hasMany(StevedoreUnloadLimits::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[TimeslotRequestGroups]].
     *
     * @return ActiveQuery
     */
    public function getTimeslotRequestGroups()
    {
        return $this->hasMany(TimeslotRequestGroups::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[TimeslotRequests]].
     *
     * @return ActiveQuery
     */
    public function getTimeslotRequests()
    {
        return $this->hasMany(TimeslotRequests::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[Timeslots]].
     *
     * @return ActiveQuery
     */
    public function getTimeslots()
    {
        return $this->hasMany(Timeslots::class, ['user_created_id' => 'id']);
    }

    /**
     * Gets query for [[Tokens]].
     *
     * @return ActiveQuery
     */
    public function getTokens()
    {
        return $this->hasMany(Tokens::class, ['user_id' => 'id']);
    }
    
    /**
     * Gets query for [[UserTrucks]].
     *
     * @return ActiveQuery
     */
    public function getUserTrucks()
    {
        return $this->hasMany(UserTrucks::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUserStevedoreUnloads(): ActiveQuery
    {
        return $this->hasMany(UsersStevedoreUnloads::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getStevedoreUnloads(): ActiveQuery
    {
        return $this->hasMany(StevedoreUnloads::class, ['id' => 'unload_id'])->via('userStevedoreUnloads');
    }

    /**
     * Gets query for [[TimeslotRequestAutofills]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTimeslotRequestAutofills()
    {
        return $this->hasMany(TimeslotRequestAutofills::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[ExporterWhitePhones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExporterWhitePhones()
    {
        return $this->hasMany(ExporterWhitePhones::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Exporters]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExporters()
    {
        return $this->hasMany(Exporters::class, ['id' => 'exporter_id'])->viaTable('exporter_white_phones', ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Notifications]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotifications()
    {
        return $this->hasMany(Notifications::class, ['target_user_id' => 'id']);
    }

    /**
     * Gets query for [[Permissions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPermissions()
    {
        return $this->hasMany(Permissions::class, ['user_id' => 'id']);
    }
}
