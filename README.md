# yii2-permissions

Управление пользовательскими разрешениями для Yii2

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/cusodede/yii2-permissions/CI%20with%20PostgreSQL)

# Мысли по ходу дела

* В БД - конфигурируемые данные, в конфигах - статические.
* Можно подключать статику полномочий из любых конфигов. Коллекции подключать нельзя.
* Опционально, можно перегонять конфиги в БД.
* Не нужно писать в каждом контроллере правила

# Идея подхода, формальная модель, отличия от RBAC

Базовая идея в том, чтобы раз и навсегда переложить управление доступами с плеч разработчика на менеджера. Разработчик не должен создавать
доступы, описывать их, и он должен быть избавлен от необходимости доступы назначать. Менеджер же, с одной стороны, должен обладать полным набором
удобного инструментария, помогающего ему ориентироваться в управлении, с другой - не иметь возможности сделать ничего «лишнего».

Из этого родились следующие принципы:

* **Запрещено всё, что не разрешено.** Полномочия всегда только разрешают, и никогда не запрещают доступ.
* **Полномочия атомарны.** Одно полномочие - одно явное разрешение.
* **Полномочия группируются.** Из разрешений можно собрать коллекцию разрешений, суммирующую все вошедшие в неё доступы.
* **Коллекции разрешений могут включать другие коллекции**. Коллекция, включающая в себя другие коллекции, обладает множеством всех разрешений из этих коллекций.
* **Субъекту можно назначать и коллекции и разрешения.** В этом случае субъект будет обладать множеством всех разрешений из назначенных коллекций и из непосредственных разрешений.
* **Полномочия, коллекции и назначения - данные, а не код.** Данными может управлять внешний пользователь, кодом может управлять только разработчик.

В отличии от классической RBAC-модели, решение опирается на сущность **полномочия**, а не на сущность **роли**: в RBAC проверяется, какие
у пользователя **роли**, и уже из ролей агрегируются доступы, в yii2-permissions полномочия агрегируются сразу.

|                                   | yii2-permissions                                             | RBAC (реализация yii/rbac)            |
|-----------------------------------|--------------------------------------------------------------|---------------------------------------|
| Базовая сущность                  | Полномочие (атомарное право доступа).                        | Роль (совокупность прав доступа).     |
| Субъект полномочия                | Человек или автоматизированный агент.                        | Человек или автоматизированный агент. |
| Объединение полномочий            | Коллекция: совокупность полномочий и коллекций (рекурсивно). | Совокупность полномочий.              |
| Назначение полномочий субъекту    | Коллекции и полномочия в любых комбинациях.                  | Только роли.                          |
| Прямое назначение полномочий      | Разрешено.                                                   | Запрещено.                            |
| Конфликты полномочий              | Отсутствуют принципиально.                                   | Решаются нормативными ограничениями.  |
| Отношения доступов                | Иерархические.                                               | Иерархические.                        |
| Хранение доступов                 | БД и код (частично).                                         | БД и код.                             |
| Добавление и управление доступами | Админка, автогенераторы, файлы конфигурации.                 | Код, миграции.                        |
| Доступы по умолчанию              | Есть.                                                        | Есть.                                 |
| Интерфейс управления              | Админка в приложении, может быть доступна пользователям.     | Нет.                                  |

# Какие задачи решаются и каким образом?

## Управление полномочиями

В модуле предусмотрен интерфейс управления, которым можно пользоваться сразу после установки: `permissions/permissions`
предоставляет таблицу управления атомарными доступами, `permissions/permissions-collections` позволяет группировать доступы в коллекции.
`permissions/default` открывает доступ к генераторам полномочий (см. далее).

## Контроль доступов к Controller/Action/Verb

Поскольку наиболее часто решаемая через доступы задача - контроль разрешений адресов приложения, этот функционал встроен
непосредственно в модуль. Каждое полномочие может быть прямо в админке привязано к контроллеру, к действию контроллера (опционально) и
(также опционально) к методу запроса к действию, после чего им можно пользоваться для включения доступа к указанному адресу.

Чтобы контроль доступов начал работать, в соответствующем контроллере нужно просто подключить фильтр `cusodede\permissions\filters\PermissionFilter::class`:

```php
/**
 * @inheritDoc
 */
public function behaviors():array {
    return [
        'access' => [
            'class' => cusodede\permissions\filters\PermissionFilter::class
        ]
    ];
}
```

## Генераторы полномочий

**init-controllers-permissions**

Чтобы не создавать вручную доступы к каждому контроллеру, проще всего воспользоваться генератором полномочий. Он может быть вызван в админке
по адресу `permissions/default/init-controllers-permissions` или выполнением консольной команды `yii permissions/init-controllers-permissions`.

Этот генератор создаст полномочия доступа для каждого действия каждого контроллера приложения (в т.ч., для контроллеров модулей), и
объединит эти полномочия в коллекции, по одной коллекции на контроллер.

**init-config-permissions**

Несмотря на то, что управление доступами вынесено на сторону приложения, остаётся и возможность добавления доступов через конфигурацию
модуля (описание формата см. в разделе [Конфигурация модуля](Конфигурация модуля)). Запуск генератора **init-config-permissions** создаст в БД
набор правил из такой конфигурации, вызвать его можно, обратившись на адрес `permissions/default/init-config-permissions` или выполнив
консольную команду `yii permissions/init-config-permissions`

## Прямой контроль доступов

## Контроль области видимости запросов

## Проверка доступности адресов

# Краткий обзор методов модуля

## `cusodede\permissions\traits\UsersPermissionsTrait`: методы проверки доступов пользователя.

Трейт должен быть подключён в классе пользователя.

* `UsersPermissionsTrait::hasPermission()`: проверяет, имеет ли пользователь право или набор прав с указанной логикой
  проверки.
* `UsersPermissionsTrait::allPermissions()`: возвращает все доступы пользователя с сортировкой по приоритету.
* `UsersPermissionsTrait::grantPermission()`: добавляет пользователю доступ по имени или идентификатору, или напрямую.
* `UsersPermissionsTrait::revokePermission()`: отзывает у пользователя доступ по имени или идентификатору, или напрямую.
* `UsersPermissionsTrait::grantCollection()`: добавляет пользователю коллекцию доступов по имени или идентификатору, или
  напрямую.
* `UsersPermissionsTrait::revokeCollection()`: отзывает у пользователя коллекцию доступов по имени или идентификатору,
  или напрямую.
* `UsersPermissionsTrait::hasActionPermission()`
* `UsersPermissionsTrait::hasControllerPermission()`
* `UsersPermissionsTrait::hasUrlPermission()`

# Установка

Выполните

```
php composer.phar require cusodede/yii2-permissions "^1.0.0"
```

или добавьте

```
"cusodede/yii2-permissions": "^1.0.0"
```

в секцию `require`.

# Миграции

Модуль хранит данные в таблицах, которые будут созданы командой

```
php yii migrate/up --migrationPath=@vendor/cusodede/yii2-permissions/migrations
```

список названий таблиц, создаваемых миграцией, можно посмотреть в
файле `migrations/m000000_000000_sys_permissions.php`. Само собой, эту миграцию нужно выполнять только для
новых проектов, для текущих, если эти таблицы были созданы ранее, миграцию применять не нужно.

# Конфигурация модуля

Вот вам пример конфига по умолчанию с описаниями параметров:

```php
return [
    // ...
    'modules' => [
        'permissions' => [
            'class' => cusodede\permissions\PermissionsModule::class,
            'params' => [
                'viewPath' => [
                    'permissions' => '@vendor/cusodede/yii2-permissions/src/views/permissions', /* путь к кастомным шаблонам для управления доступами */
                    'permissions-collections' => '@vendor/cusodede/yii2-permissions/src/views/permissions-collections' /* путь к кастомным шаблонам для управления коллекциями доступов */
                ],
                'userIdentityClass' => Yii::$app->user->identityClass, /* Имя класса (либо замыкание, это имя возвращающее), определяющего identity пользователя. */
                'userCurrentIdentity' => Yii::$app->user->identity, /* Экземпляр класса, идентифицирующий сущность текущего пользователя */
                'controllerDirs' => [, /* Перечисление каталогов контроллеров, которые а) должны появиться в соответствующих настройках доступов; б) см. issue #1 Формат: 'путь_к_каталогу' => 'модуль_контроллера. Примеры ниже. '*/
                    /*
                     * '@app/controllers' => null, # для контроллеров, загружаемых приложением, модуль не указывается
                     * '@app/modules/api/controllers' => 'api', # для каталога с контроллерами модуля указываем id модуля
                     * '@vendor/cusodede/yii2-permissions/src/controllers' => '@permissions', # если id модуля указан через @, то модуль не будет загружаться при инициализации контроллеров (для получения списка действий)
                    */
                ],
                'grantAll' => [1],/* id пользователей, которые будут получать все привилегии */
                'grant' => [/* перечисление прямых назначений привилегий в формате user_id => [список получаемых привилегий]. Пример ниже. */
                    /*
                     * 1 => ['login_as_another_user', 'some_other_permission']
                     */
                ],
                'permissions' => [/* Список именованных привилегий, создаваемых командой init-config-permissions. Примеры ниже. Привилегии контроллер-экшен-etc в этой конфигурации не поддерживаются. */ 
                    /*
                     * 'system' => [
                     *      'comment' => 'Разрешение на доступ к системным параметрам',
                     * ],
                     * 'login_as_another_user' => [
                     *      'comment' => 'Разрешение авторизоваться под другим пользователем',
                     * ]
                     */
                ]
            ]
        ] 
    ]
    // ...
]
```

# Команды модуля

TBD

# Использование (модели, трейты, etc)

TBD

# Запуск локальных тестов

Скопируйте `tests/.env.example` в `tests/.env`, и измените конфигурацию соответственно вашему локальному окружению.
Затем выполните
команду `php vendor/bin/codecept run`.
