# yii2-permissions

Управление пользовательскими разрешениями для Yii2

# Установка

Добавляем

```
{
	"type": "vcs",
	"url": "https://github.com/cusodede/yii2-permissions"
}
```

В секцию `repositories` файла `composer.json`, затем запускаем

```
php composer.phar require cusodede/yii2-permissions "dev-master"
```

или добавляем

```
"cusodede/yii2-permissions": "dev-master"
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

# Конфиги

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
                'controllerDirs' => [, /* Перечисление каталогов контроллеров, которые а) должны появиться в соответствующих настройках доступов; б) см. issue #1 Формат: 'путь_к_каталогу' => '@модуль_контроллера. Примеры ниже. '*/
                    /*
                     * '@app/controllers' => '', # для базового каталога контроллеров приложения формат такой
                     * '@app/modules/api/controllers' => '@api', # для каталога с контроллерами модуля
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