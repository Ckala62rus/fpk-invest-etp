# Демо-данные для локального тестирования ЭТП

Как поднять стек и заполнить БД тестовыми пользователями, каталогом, ТЗП (торгово-закупочными процедурами) и страницами CMS.

| Сервис | URL |
|--------|-----|
| API | http://localhost:8200 |
| MailHog | http://localhost:8025 |
| SPA (отдельный compose) | http://localhost:5173 |

---

## Быстрый старт (рекомендуется)

Из каталога `backend/`:

```powershell
powershell -File .\docker\scripts\dev-up.ps1
```

Скрипт:

1. создаёт `.env` / `src/.env` из example при отсутствии;
2. поднимает Docker Compose (postgres, redis, nginx, app, horizon, reverb, mailhog, scheduler);
3. делает `composer install`, `migrate`, **`db:seed`**, `storage:link`;
4. создаёт БД `etp_test` для PHPUnit;
5. проверяет `GET /api/health`.

После этого демо-данные уже в БД.

---

## Что создаёт `db:seed`

Порядок в `DatabaseSeeder`:

| Seeder | Назначение |
|--------|------------|
| `RolesAndPermissionsSeeder` | роли RBAC (role-based access control) |
| `SettingsSeeder` | системные настройки |
| `NotificationTemplateSeeder` | шаблоны email |
| `SuperAdminSeeder` | главный админ |
| `DemoUsersSeeder` | trade_admin, participant, auditor |
| `DemoCatalogSeeder` | группы компаний, категории, заказчики |
| `DemoProceduresSeeder` | ТЗП с номерами `DEMO-*`, лоты, КП, кастомные поля |
| `DemoCmsSeeder` | CMS: about / rules / contacts |

Сиды **идемпотентны**: повторный `db:seed` обновляет те же записи (ИНН, slug, номера `DEMO-*`), не плодит дубликаты.

---

## Учётные записи (пароль по умолчанию `password`)

Переопределяются в `src/.env` (см. `src/.env.example`).

| Роль | ИНН (логин) | Email по умолчанию | Куда в SPA |
|------|-------------|--------------------|------------|
| `super_admin` | `770000000000` | super_admin@example.com | `/admin` |
| `trade_admin` | `770000000001` | trade_admin@example.com | `/admin` |
| `participant` | `770000000002` | participant@example.com | `/cabinet` |
| `auditor` | `770000000003` | auditor@example.com | `/admin` (аудит) |

Переменные: `SUPER_ADMIN_*`, `DEMO_USERS_PASSWORD`, `TRADE_ADMIN_*`, `PARTICIPANT_*`, `AUDITOR_*`.

---

## Демо-ТЗП (после сида)

| Номер | Смысл |
|-------|--------|
| `DEMO-RFP-DRAFT` | черновик (на витрине гостю не виден) |
| `DEMO-RFP-OPEN` | открытый запрос КП + кастомные поля участника |
| `DEMO-AUCTION-1` | аукцион для проверки ставок / Reverb |
| `DEMO-RFP-CLOSED` | закрытая процедура |
| `DEMO-RFP-DONE` | завершённая |

Каталог: группы «ФПК «Инвест» (демо)», категории СМР/ИТ, компании с демо-ИНН.  
CMS slug: `about`, `rules`, `contacts`.

---

## Если стек уже запущен

Только миграции и сиды (контейнер `backend-etp`):

```powershell
docker exec -e XDEBUG_MODE=off backend-etp php artisan migrate --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --force
```

Только демо (порядок важен: пользователи → каталог → процедуры → CMS):

```powershell
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --class=DemoUsersSeeder --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --class=DemoCatalogSeeder --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --class=DemoProceduresSeeder --force
docker exec -e XDEBUG_MODE=off backend-etp php artisan db:seed --class=DemoCmsSeeder --force
```

`DemoCmsSeeder` ждёт `super_admin` из `SuperAdminSeeder`.

---

## Чистая БД «с нуля»

```powershell
docker exec -e XDEBUG_MODE=off backend-etp php artisan migrate:fresh --seed --force
```

**Внимание:** удалит все данные в БД приложения. Не использовать на проде.

---

## Проверка

```powershell
Invoke-RestMethod http://localhost:8200/api/health
Invoke-RestMethod http://localhost:8200/api/procedures
```

Письма регистрации и уведомлений — в MailHog.

Пошаговый сценарий SPA (два браузера, аукцион, аудит): в монорепо  
`documentation/agent-notes/13-portal-user-guide.md` (не в этом git-репозитории).
