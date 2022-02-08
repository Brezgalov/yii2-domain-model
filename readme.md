## Для чего этот репозиторй?

### Лирика

Этот репозиторий содержит классы и интерфейсы, каркас (framework) для структурирования кода приложения. 
Он вдохновлен подходом Domain Driven Development. 

Зачем вообще стремиться к доменно-ориентированности? 

Можно начать проект вообще без всякой архитектуры,
быстро поднять его и если он взлетит - переписывать после, но такое переписывание потребует много сил.

Можно сразу начать проект на каноничном DDD, что увеличит сложность старта и порог входа, а дальше -
как пойдет, если проект не взлетит - мы старались зря.

Я предлагаю этот пакет, как что-то среднее. Он позволяет иметь не такой высокий порог входа и
меньший уровень сложности на старте, чем каноничный DDD. Но при этом переписать на каноничный
DDD после работы с таким проектом - будет значительно проще.

К сути!

## Как это работает?

### Модель

Чтобы было проще начнем знакомство на примере. 
Допустим мы хотим реализовать сохранение отметки о подтверждении телефона пользователя. 

Следуя DDD мы должны были бы создать модель **Пользователь** такого вида:

    class UserDM extends BasicDomainModel
    {
        /**
         * @var integer
         */
        public $id;
    
        /**
         * @var string
         */
        public $phone_confirmed_mark;
    }

На практике, прописывать перенос полей из БД в модель и обратно - довольно затратная по человеко-часам операция. 
Следование DDD в этом аспекте увеличит срок разработки и бизнес будет недоволен.
Я предлагаю пойти на компромисс и оборачивать один или несколько DAO в модели, если это возможно.

>*Поля модели оставляю публичными, опять же для упрощения. 
Я полагаю, что программисты не будут использовать эти поля "не правильно", потому что такой код
будет отсеиваться на код-ревью, а сами сотрудники - обучаться.*

    class UserProfileDM extends BasicDomainModel
    {
        /**
        * @var UsersDao
        */
        public $user;
    }

### Модель-Инвариант

Инвариант в объектно-ориентированном программировании — выражение, определяющее непротиворечивое внутреннее состояние объекта.
Иными словами, - инвариант всегда сохраняет свое состояние валидным.
Именно такой, должна быть модель, по DDD.

Наша модель, при получении ее из репозитория, должна всегда быть валидна. 
Для того чтобы в этом убедиться я добавил в интерфейс доменных моделей метод **isValid()**.

Пример реализации метода:

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->user && $this->user->validate();
    }

> Этот метод вызывается в базовом сервисе после загрузки модели.
> При получении отрицательного ответа от метода - выбрасывается исключение

    class ActionAdapterService extends Action
    {
        ...

        public function run()
        {
            ...

            try {
                $resultFormatter = $this->getFormatter();
                $model = $this->getDomainModel();
    
                if (!$model->isValid()) {
                    throw new InvalidConfigException("Model loaded in failed state");
                }

            ...
        }
    }


### Репозиторий (чтение)

Мы будем подтверждать телефон уже существующего пользователя. 
Нам понадобится способ наполнить модель данными. 
Создадим для этого класс-репозиторий. 

    class UserProfileDMRepository extends BasicRepository
    {
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
        }
    
        /**
         * Входные параметры запроса попадут в load через метод registerInput базового класса
         * @return array[]
         */
        public function rules()
        {
            return [
                [['id'], 'required', 'message' => 'Укажите ID пользователя для отображения профиля'],
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

>**DAO** - Data Access Object. Как правило, в Yii в роли объекта доступа к данным выступает **ActiveRecord**

> **UsersDaoRepository** реализует **IDaoRepository** и служит для поиска DAO по каким-либо параметрам, в нашем случае - по id

### Логика

Теперь мы хотим реализовать сохранение отметки о подтверждении телефона. Можно просто:

    class UserProfileDM extends BasicDomainModel
    {
        /**
        * @var UsersDao
        */
        public $user;
    
        /**
         * @return UsersDao
         */
        public function setPhoneConfirmed()
        {
            $this->user->phone_confirmed_mark = true;

            if (!$this->user->save()) {
                // обработка ошибки
            }
    
            return $this->user;
        }
    }

> В нарушение DDD я сохранил изменения в DAO непосредственно в методе **run**.
> Это сократит сложность кода и понизит порог вхождения.
> Целостность данных мы сохраним с помощью миграции. См. подробнее в секции **UnitOfWork**

Но теперь наша модель **Профиль** будет становится крупнее с каждым новым действием,
это моет привести к тому, что она со временем будет перегружена кодом, 
а сами методы модели начнут проникать в друг друга через прямые вызовы или 
*protected* методы.

Давайте попробуем сделать процесс подтверждения телефона более самостоятельным и изолированным.

Для этого опишем его отдельно:

    class SetPhoneConfirmedDAM extends BasicDomainActionModel
    {
        public function run()
        {
    
            // ...
    
            return $this->user;
        }
    }

Теперь подключим процесс в модель

    class UserProfileDM extends BasicDomainModel
    {
        const METHOD_SET_PHONE_CONFIRMED = 'setPhoneConfirmed';
    
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
                 * Тут мы задокументируем все особенности метода и
                 * коротко опишем что делаем
                 */
                static::METHOD_SET_PHONE_CONFIRMED => SetPhoneConfirmedDAM::class,
            ];
        }
    }

Попробуем немного углубить логику, чтобы лучше понять возможности 
**BasicDomainActionModel** и предполагаемый способ взаимодействия.

    class SetPhoneConfirmedDAM extends BasicDomainActionModel
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

> **BasicDomainActionModel** может обращаться к **DomainModel** через защищенное поле **$model**

> **BasicDomainActionModel** это **\yii\base\Model**, поэтому вы можете использовать встроенные механизмы **load** и **validate**

> Здесь я уже реализовал сохранение пользователя через **отложенное событие**. Дело в том, что я планирую использовать
> этот метод внутри других методов модели **UserProfileDM**. Поэтому я не хочу чтобы модель юзера сохранялась несколько раз подряд.
> Событие с ключем позволит обновить модель до актуального состояния за один вызов **save()**.

### Сервис (Подключение к контроллеру)

Задача сервисов по DDD - передача входных данных в модели и ответа модели обратно.
Если представить, что один запрос к модели = одно действие (метод), то сервис можно стандартизировать
и привести к общему виду. 

**ActionAdapterService** - это попытка использовать единый сервис для подключения всех моделей.

    class UserProfileController extends Controller
    {
        public function actions()
        {
            return [
                /**
                * Описываем для чего этот метод и как он должен
                * интегрироваться с клиентской частью приложения
                *
                * Добавляем так же ссылку на метод, чтобы потом было проще его найти
                * @see SubmitPhoneDAM::run()
                */
                'submit-profile' => [
                    'class' => ActionAdapterService::class,
                    'repository' => UserProfileDMRepository::class,
                    'modelActionName' => UserProfileDM::METHOD_SUBMIT_PHONE,
                ]
            ];
        }
    }

> **ActionAdapterService** по умолчанию использует **ActionAdapterMutexBehavior**.
> Это поведение заворачивает Action в Mutex для каждого отдельного клиента. Это
> необходимо на случай, если клиент отправит одновременно несколько запросов, которые
> начнут обрабатываться параллельно. Хранилище данных не всегда будет актуальным при 
> выполнении таких запросов, поэтому я решил упорядочить их выполнение. Такое поведение 
> можно отключить, передав **\'behaviors\' => []**

> **ActionAdapterService** так же содержит классы, отвечающие за сохранение изменений 
> (**UnitOfWork**) и форматирование ответа (**ModelResultFormatter**). Подробнее о них далее.

### Unit of Work

**UnitOfWork** должен отвечать за сохранение изменений. В моем варианте реализации этого
класса используются миграции. Благодаря им мы можем использовать **ActiveRecord::save()**
прямо в методе модели, при этом имея возможность откатить изменения в случае ошибки.

Вот пример кода из **ActionAdapterService**, который работает с **UnitOfWork**:

    $unitOfWork = $this->getUnitOfWork();
    $model->linkUnitOfWork($unitOfWork);

    try {
        $result = call_user_func([$model, $this->modelActionName]);
        $model->getUnitOfWork()->flush();
    } catch (\Exception $ex) {
        $result = $ex;
        $model->getUnitOfWork()->die();
    }

> Метод **die()** отвечает за откат изменений

> Метод **flush()** отвечает за применение изменений

> **$model::linkUnitOfWork()** дает доступ модели к **UnitOfWork**. Это позволяет
> передавать его ниже по архитектуре, если это требуется, а так же использовать его
> для регистрации отложенных событий.

### Форматирование ответа

Формат ответа вещь довольно индивидуальная для каждого проекта. Где-то будет необходимо
возвращать результат **View::render**, где-то будет использоваться API и формат ответа 
будет совершенно другим. 

Я не хочу навязывать конкретный способ форматирования ответа,
поэтому конечную реализацию оставляю пользователям пакета. Возможно со временем я 
добавлю несколько стандартных классов для форматирования.

Вы можете не пользоваться форматированием вообще, написать общий форматировщик на весь проект,
отдельные форматировщики для отдельных кейсов. Используйте поле **ActionAdapterService::resultFormatter**
и интерфейс **IResultFormatter** для реализации конкретных классов.

## Продвинутые практики

### Передача модели в сервис напрямую

Если вдруг по какой-то причине наша модель не имеет в себе данных
или мы осознанно превращаем модель в фасад группирующий методы - использование
репозитория будет не оправданным усложнением. В таких случаях модель
можно передать напрямую через поле **ActionAdapterService::model**

Сервис проверяет, может ли ваша модель быть передана напрямую

    public function getDomainModel()
    {
        $input = $this->getInput();

        if ($this->model) {
            $model = $this->model instanceof IDomainModel ? $this->model : \Yii::createObject($this->model);

            if (!$model->canInitWithoutRepo()) {
                throw new InvalidCallException('Model ' . get_class($model) . ' can not be loaded without Repo');
            }
        }

        ...
    }

По-умолчанию такое запрещено в модели **BaseDomainModel**. Для того чтобы открыть эту 
функцию необходимо определить метод **IDomainModel::canInitWithoutRepo** и вернуть **true**

Очень редко нужно обратиться к своим методам в модели которая уже получена, через репозиторий. 
Используем вот такой "хак":

    $callResult = $this->model->crossDomainCall(
        $this->model->getNoRepoClone(),
        MyDomainModel::MY_METHOD,
        []
    );

### Отложенные события

Часто возникает ситуация, что необходимо в ходе работы приложения выполнить необратимое действие.
Например, при подтверждении регистрации, - отправить письмо или смс. Что делать, если мы уже произвели
такое действие и уже после получили ошибку в доменном процессе? 

Для решения этой проблемы используются отложенные события. Я предлагаю регистрировать их с 
помощью **UnitOfWork::delayEvent**. Этот метод будет доступен как внутри доменной модели, так и 
внутри доменных процессов, через обращение к ней.

Чуть выше мы уже использовали вызов регистрации отложенного события. 
Вот так выглядит реализация события сохранения пользователя:

    class StoreModelEvent extends Model implements IEvent
    {
        /**
        * @var UserProfileDM
        */
        protected $model;
    
        /**
         * StoreModelEvent constructor.
         * @param UserProfileDM $model
         * @param array $config
         */
        public function __construct(UserProfileDM $model, $config = [])
        {
            $this->model = $model;
    
            parent::__construct($config);
        }
    
        /**
         * @return bool|void
         * @throws Exception
         */
        public function run()
        {
            if (!$this->model->user->save()) {
                throw new Exception('Не удается сохранить модель пользователя');
            }
        }
    }

>События реализуют метод **run()**, через интерфейс **IEvent**.  

>Событие будет вызвано в пределах основной миграции **UnitOfWork**, поэтому в случае чего
>исключение отменит все изменения других событий меняющих бд.

>Здесь мы можем вызывать не только сохранение, но и отправку смс, почты и т.п.

### Кросс-доменное взаимодействие

Мы реализовали сохранение отметки о том, что телефон пользователя подтвержден. 
Теперь нас просят реализовать простановку этой отметки в случае получения смс-кода подтверждения.

Попробуем реализовать такой процесс

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

> На входе процесс получает от контроллера номер телефона и код смс

> После выполнения всех проверок мы проставляем отметку о подтверждении через **$this->model->crossDomainCall()** 

Минуточку. У нас ведь реализован класс для процесса простановки отметки. 
Почему бы нам инстанцировать его, передав внутрь текущую модель профиля и не
вызвать напрямую? 
Зачем нужен странный и не понятный нам метод для вызова этого процесса?

Если бы начнем инстанцировать процессы внутри процессов напрямую, то спустя
некоторое время вернувшись в код мы не сможем точно сказать по нашей модели, какие
процессы в ней внутренние, какие наружные. Единственный способ разобраться в таком случае -
читать непосредственно все реализации процессов.

>Более того, процесс из одной модели может быть использован в другой, тогда найти все связи 
будет гораздо труднее.

Я предлагаю договориться между собой и запретить такие действия. 
Вместо это, я предлагаю использовать функцию **BasicDomainModel::crossDomainCall**. 
Она принимает модель (или репозиторий), название метода который необходимо использовать и входные параметры. 

Для того, чтобы метод стал доступен для вызова через эту функцию, 
необходимо явно указать его в списке, получаемом через **BasicDomainModel::crossDomainActionsAllowed**.

При вызове этого метода в модели, которая совершает кросс-доменное действие, в конец массива **BasicDomainModel::$crossDomainOrigin**
проставляется отметка о модели совершившей вызов. см **registerCrossDomainOrigin**
Это позволяет вам проверить в методе **BasicDomainModel::crossDomainActionsAllowed()**, 
из какой модели вызывается процесс и в какая была последовательность обращений к моделям.

    /**
    * @return array|mixed
    * @throws \Exception
    */
    public function crossDomainActionsAllowed()
    {
        $domainOrigins = $this->crossDomainOrigin;
        $lastParent = array_pop($domainOrigins);

        return ArrayHelper::getValue([
            // Методы доступные только внутри другой модели
            SomeOtherDM::class => [
                UserProfileDM::METHOD_SUBMIT_PHONE_CONFIRM,
            ],
        
            // Методы доступные себе внутри себя
            UserProfileDM::class => [
                UserProfileDM::METHOD_SET_PHONE_CONFIRMED,
            ],
        ], $lastParent, []);
    }

> Таким образом в **crossDomainActionsAllowed** мы увидим подробное описание того какие методы и откуда можно вызывать

Так же модели получают общий **UnitOfWork**. Это позволяет иметь единое хранилище для
отложенных событий между всеми моделями. см **linkUnitOfWork**

Метод возвращает не только результат процесса, но и модель, которая его совершала.
см **CrossDomainCallDto**

    public function crossDomainCall($modelConfig, string $methodName, array $input = [])
    {
        $model = null;

        if (is_array($modelConfig) || is_string($modelConfig)) {
            $modelConfig = \Yii::createObject($modelConfig);
        }

        if ($modelConfig instanceof IDomainModelRepository) {
            /**
             * Если репозиторий передан на прямую - кросс-доменный вызов не должна вносить в него артефакты
             * Если нет - проще сделать лишний clone, чем плодить if'ы
             */
            $modelConfig = clone $modelConfig;

            $modelConfig->registerInput($input);
            $modelConfig = $modelConfig->getDomainModel();
        }

        if (!($modelConfig instanceof IDomainModel)) {
            CrossDomainException::throwException(static::class, null, "Only Models and Repos can be accessed in cross-domain way");
        }

        /**
         * Если модель передана на прямую - кросс-доменный вызов не должна вносить в нее артефакты
         * Если нет - проще сделать лишний clone, чем плодить if'ы
         */
        $modelConfig = clone $modelConfig;
        $modelConfig->registerCrossDomainOrigin(static::class);

        if (!in_array($methodName, $modelConfig->crossDomainActionsAllowed())) {
            CrossDomainException::throwException(static::class, get_class($modelConfig), "Method {$methodName} is not allowed for cross-domain access");
        }

        /**
         * pass UnitOfWork by ref, so events storage and transaction stays "singltoned"
         */
        if ($this->unitOfWork) {
            $modelConfig->linkUnitOfWork($this->unitOfWork);
        }

        $modelConfig->registerInput($input);

        $result = call_user_func([$modelConfig, $methodName]);

        return new CrossDomainCallDto([
            'model' => $modelConfig,
            'result' => $result,
        ]);
    }

> Я приложил часть проекта, на котором внедрялся такой подход, в папке **example**. 
> Там вы сможете более подробно рассмотреть примеры кода модели **UserProfileDM**