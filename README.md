База данных тут: olvia.studio-205.ru/images2/olvia.rar
Настройки в \config\local.example, его нужно переименовать в local.php
После деплоя необходимо развернуть зависимости из composer-а

Все, что касается фреймворка находится в модуле Common. Все очень сырое, и пока скорее эксперимент, чем какой то фреймворк
Основная цель разработки - быстрое создание админок, упор сделан на возможность кодогенерации.

Пример развернутого приложения тут: http://olvia.studio-205.ru/
Админка: http://olvia.studio-205.ru/private

Основные функции
----------------
 
1) Логика вывода списков с фильтрами, пагинацией и сортировкой.

2) Логика форм (CRUD) и сами формы с возможность расширения "элементной базы".

3) Обертка Zend\DB c многоязычностью, историей изменений и обсуждениями

4) Работа с картинками, ресайз на лету 

5) Работа с js и css, библиотека зависимостей (пока захардкоженая), объединение и минификация файлов

6) Мобильная и десктопная версии, основанные на разных шаблонах. 

7) Авторизация пользователей основаная на ролях

ТОDO
----

1) Рефакторинг, создание нормального подключаемого пакета, тестирование

2) Поддержка списков с категориями

3) Группы элементов и фильтры в формах, валидация на стороне клиента 

4) Кодогенератор с интерфесом в виде Eclipse-плагина

5) Подключаемые Библиотеки JS и CSS, возможно интеграция с bower
 

Классы
------

*ContextSwitcher* - переключение мобильной и десктопной версии (мобильные виды см. папку \module\Application\view_mobile )

*ControllerAnnotationListener* - подключение аннотаций Roles и Layout контроллеров

*CRUDController* - реализация CRUD для админок, сделаны списки итомов с фильтрами и пагинацией, календарь итемов, редактирование итема, сохранение истории

*CRUDCalendarModel, CRUDEditModel, CRUDListModel.php* - интерфейсы поставщиков данных для CRUDController

*FormErrors* - контейнер для ошибок в контроллере

*Identity* - Текущий пользователь с его ролями

*ImageService* - Обработка картинок

*VideoService* - для видео (не готово)

*Mailer* - Рассыльщик почты с шаблонизатором и вложениями 

*NavigationFactory* - Фабрика Zend для навигации в админке 

*SiteController* - базовый класс для контроллеров. Реализована: авторизация на основе ролей с редиректами, и управление шаблонами (layouys)

*Annotations\Layout* - аннотация контроллера для задания шаблона страниц

*Annotations\Roles* - аннотация контроллера для задания допустимых ролей пользователя

*TraitInitializer* - инициализатор для разных фич из пакета Traits

*Utils* - всякое

*View* - класс для обеспечения автокмплита хелперов в видах  

*ControllerPlugin\SendFlashMessage* - моя реализация флеш-сообщений

*пакет Db* - обертка для Zend\Dd. Реализованы:

- многоязычные сущности Multilingual

- история изменений Historical (настройки для каждой сущности в файле config/entity.fields.global.php)

- комментарии Discussion

- добавлены удобные функции fetchPairs, fetchOne, fetchGroups

- многое другое, все реализовано на ассоциативных массивах без паттерна ActiveRecord 
 
*пакет Form* моя реализация форм, тесно связан с CRUDController.

расширение основано на трейтах, см. например /module/Admin/src/Forms/MasterPriceElements.php

*ViewHelper\Assets* - управление подключенными js и css файлами. В продакшн режиме объединяет и минифицирует файлы.

*ViewHelper\Image* - вывод изображения с ресайзом и кэшированием

*ViewHelper\Sidebar* - задать шаблон сайдбара в непосредственно основном шаблоне

*ViewHelper\Sorter.php* - столбцы с сортировкой в списках, связано с CRUDController

*ViewHelper\Html* - всякое

Кодогенератор
-------------
 
*папка proto-gen* - кодогенератор. Велосипед и собраный на коленке.

proto-gen/protos шаблоны для кодонгенератора

proto-gen/Entity - модель сущности

остальные файлы - точки входа для КГ с примерами данных. При запуске происходит перезапись существующего кода с бэкапом в папку build

так же генерируеться DDL для MySQL и код для настроек истории
