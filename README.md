# MTS

## Структура проекта

- *config/* - папка для конфигураций проекта
- *lib/* - папка с функциями системы
- *logs/* - папка для логгирования отложенных операций системы (а не просто логов!)
- *public/* - папка со страницами и статическим контентом
- *sql/* - наборы запросов для создания схемы данных проекта
- *system/* - консольные команды
- *templates/* - шаблоны проекта

## TODO

- XA transactions или аналог
- Кэширование загружаемых данных о шардах
- Профиль пользователя со вкладками "Заказы пользователя", "Выполненные пользователем заказы"
- Подумать о работе с репликами при чтении данных
