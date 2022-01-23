## Для чего этот репозиторй?

### Лирика

Этот репозиторий содержит классы и интерфейсы, каркас (framework) для структурирования кода приложения. 
Он вдохновлен подходом Domain Driven Development.

Основная цель этого репозитория - помочь разработчикам писать код так, чтобы с годами 
их приложение так же легко поддерживалось и масштабировалось, как и на старте. 
А новичкам было очевидно для чего нужна конкретная часть кода и в какой фиче она используется.

тут написать про ddd, старт проекта и рефактор

Я постарался подойти к доменной модели так, чтобы она была в принципе и 
чтобы работа с ней не занимала много времени и не тормозила разработку.

К сути!

## Как это работает?

### Модель

Чтобы было проще начнем знакомство на примере. 
Допустим мы хотим реализовать подтверждение телефона пользователя. 
Мы знаем что у пользователя есть телефон и должна быть отметка о том, что телефон подтвержден.

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
        public $phone;
    
        /**
         * @var string
         */
        public $phone_confirmed_mark;
    }

На практике, прописывать перенос полей из БД в модель и обратно - довольно затратная по человеко-часам операция. 
Следование DDD в этом аспекте увеличит срок разработки и бизнес будет недоволен.
Я предлагаю пойти на компромис и оборачивать один или несколько DAO в модели, если это возможно.

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

### Репозиторий (чтение)

Мы будем подтверждать телефон уже существующего пользователя. 
Нам понадобится способ наполнить модель данными. 
Создадим для этого класс-репозиторий. 

    class UserProfileDMRepository extends BasicRepository implements IDomainModelRepository
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

Теперь мы хотим реализовать подтверждение телефона. Можно просто:

    class UserProfileDM extends BasicDomainModel
    {
        /**
        * @var UsersDao
        */
        public $user;
    
        /**
         * @return UsersDao
         */
        public function submitPhone()
        {
            // Сюда получим входные данные для операции от контроллера
            $inputParams = $this->input;
    
            // ... - логика подтверждения телефона
    
            return $this->user;
        }
    }

Но тогда наша модель **Профиль** становится крупнее с каждым новым действием,
это моет привести к тому, что она со временем будет перегружена кодом, 
а сами методы модели начнут проникать в друг друга через прямые вызовы или 
*protected* методы.

Давайте попробуем сделать процесс подтверждения телефона более самостоятельным и изолированным.

Для этого опишем его отдельно:

    class SubmitPhoneDAM extends BasicDomainActionModel
    {
        public function run()
        {
            $inputParams = $this->input;
    
            // ...
    
            return $this->user;
        }
    }

Теперь подключим процесс в модель

    class UserProfileDM extends BasicDomainModel
    {
        const METHOD_SUBMIT_PHONE = 'submitPhone';
    
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
                 * Метод подтверждения телефона
                 * Тут мы задокументируем все особенности метода и
                 * коротко опишем что делаем
                 */
                static::METHOD_SUBMIT_PHONE => SubmitPhoneDAM::class,
            ];
        }
    }

Попробуем немного углубить логику, чтобы лучше понять возможности 
**BasicDomainActionModel** и предполагаемый способ взаимодействия.

    class SubmitPhoneDAM extends BasicDomainActionModel
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
        public $submit_code;
    
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
                [['phone', 'submit_code'], 'required'],
            ];
        }
    
        /**
         * @return $this|mixed
         * @throws ErrorException
         */
        public function run()
        {
            $this->load($this->input, '');
    
            if (!$this->validate()) {
                // Способ передачи ошибок может быть любым.
    
                // Можно выбросить exception
                ErrorException::throw('Validation error', 422);
    
                // Можно вернуть саму модель действия с ошибками внутри
                return $this;
    
                // В любом случае обработка и форматирование ответа зависят от вас
                // и происходят уже после выполнения логики
            }
    
            if ($this->model->user->phone != PhoneHelper::clearPhone($this->phone)) {
                ErrorException::throwAsModelError('phone', 'Телефон указанный для подтверждения не соответствует номеру из профиля');
            }
    
            $smsRepo = clone $this->smsCodesRepo;
            $smsRepo->code = $this->submit_code;
            $smsRepo->for_phone = PhoneHelper::clearPhone($this->phone);
    
            if (!$smsRepo->getQuery()->exists()) {
                ErrorException::throwAsModelError('submit_code', 'Sms-код устарел или указан неверно');
            }
    
            $this->model->user->phone_confirmed_mark = date('Y-m-d H:i:s');
    
            /**
             * Использование save в модели так же нарушет DDD
             * Это компромисс для упрощения работы. Подробнее про это в UnitOfWork
             */
            if (!$this->model->user->save()) {
                ErrorException::throwAsModelError('phone', 'Произошли ошибки при сохранении');
            }
    
            return $this->user;
        }
    }

> **BasicDomainActionModel** может обращаться к **DomainModel** через защищенное поле **$model**

> **BasicDomainActionModel** это **\yii\base\Model**, поэтому вы можете использовать встроенные механизмы **load** и **validate**

> В нарушение DDD я предлагаю сохранять изменения в DAO непосредственно в методе **run**. 
> Это сократит сложность кода и понизит порог вхождения. 
> Целостность данных мы сохраним с помощью миграции. См. подробнее в **UnitOfWork** 

### Сервис (Подключение к контроллеру)

Задача сервисов по DDD, как я ее вижу - передача входных данных в модели и ответа модели обратно.
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

> Если вдруг по какой-то причине наша модель не имеет в себе данных 
> или мы осознанно превращаем модель в фасад группирующий методы - использование 
> репозитория будет не оправданным усложнением. В таких случаях модель 
> можно передать напрямую через поле **ActionAdapterService::model**

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
еще добавлю несколько стандартных классов для форматирования.

## Продвинутые практики

### Отложенные события

### Кросс-доменное взаимодействие

