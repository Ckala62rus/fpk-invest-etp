# Локальный запуск ЭТП на ноутбуке

Инструкция запускает development-версию ЭТП на Windows 10/11 с Docker Desktop. Backend (Laravel API), PostgreSQL, Redis, MailHog, Horizon, scheduler и Reverb работают в Docker. Frontend (Vue/Vite) также запускается в Docker с hot module replacement (HMR).

> Для запуска нужны два репозитория рядом в одной папке. Каталог `deploy/` для локальной разработки не нужен: он предназначен для production-like запуска через единый Nginx gateway.

## 1. Требования

- Windows 10/11;
- Docker Desktop с включённым WSL 2 backend;
- Git for Windows;
- не заняты порты `1025`, `5173`, `6001`, `8025`, `8200`, `5432`, `6379` и, опционально, `8081`.

Проверка Docker Desktop в PowerShell:

```powershell
docker version
docker compose version
```

## 2. Клонирование исходного кода

Откройте PowerShell и создайте общую папку. Оба репозитория должны лежать рядом:

```powershell
New-Item -ItemType Directory -Path 'D:\Projects\etp' -Force
Set-Location 'D:\Projects\etp'

git clone https://github.com/Ckala62rus/fpk-invest-etp.git backend
git clone https://github.com/Ckala62rus/fpk-invest-etp-frontend.git frontend
```

Должна получиться структура:

```text
D:\Projects\etp\
├── backend\
└── frontend\
```

## 3. Настройка и запуск backend

Перейдите в backend и создайте локальные файлы окружения:

```powershell
Set-Location 'D:\Projects\etp\backend'
Copy-Item '.env.example' '.env'
Copy-Item 'src\.env.example' 'src\.env'
```

Значения по умолчанию рассчитаны на локальный ноутбук:

- API: `http://localhost:8200`;
- PostgreSQL: `localhost:5432`;
- Redis: `localhost:6379`;
- MailHog: `http://localhost:8025`;
- Reverb: `ws://localhost:6001`;
- frontend Vite: `http://localhost:5173`.

Если один из портов занят, измените его в `backend\.env`. При изменении API-порта для Docker-frontend обновите `API_UPSTREAM=http://host.docker.internal:<новый-порт>` в `frontend\.env.docker`. `VITE_API_PROXY_TARGET` в `frontend\.env` используется при запуске Vite вне Docker.

При **первом запуске** поднимите только инфраструктуру и PHP-FPM. Не запускайте сразу весь Compose: `scheduler-etp`, `horizon-etp` и `reverb-etp` выполняют `php artisan` и до установки Composer-зависимостей будут перезапускаться с ошибкой `vendor/autoload.php: No such file or directory`.

```powershell
docker compose up -d --build postgres-etp redis-etp mailhog-etp backend-etp nginx-etp
docker compose ps
```

Установите PHP-зависимости и инициализируйте базу:

```powershell
docker compose exec -T backend-etp composer install --no-interaction --no-progress --ignore-platform-reqs
docker compose exec -T backend-etp php artisan key:generate
docker compose exec -T backend-etp php artisan migrate --force
docker compose exec -T backend-etp php artisan db:seed --force
docker compose exec -T backend-etp php artisan storage:link
docker compose exec -T backend-etp php artisan config:clear
```

После успешного `composer install` запустите фоновые сервисы и убедитесь, что все контейнеры имеют статус `Up`:

```powershell
docker compose up -d
docker compose ps
```

> Временное ограничение проекта: `src/composer.json` требует PHP 8.4, а текущий Dockerfile собирается на PHP 8.3. Поэтому существующий bootstrap-скрипт также использует `--ignore-platform-reqs`. Перед новым стабильным development-окружением Dockerfile нужно обновить до PHP 8.4, а затем убрать этот временный параметр.

Проверьте API:

```powershell
Invoke-RestMethod 'http://localhost:8200/api/health'
```

Ожидаются HTTP `200` и `success: true`. Проверьте `data.database` и `data.redis`: поле `success` отражает доступность PostgreSQL, а не Redis.

### Опционально: pgAdmin

```powershell
docker compose --profile tools up -d pgadmin-etp
```

После запуска pgAdmin доступен по адресу `http://localhost:8081`.

## 4. Настройка и запуск frontend

Откройте второе окно PowerShell:

```powershell
Set-Location 'D:\Projects\etp\frontend'
Copy-Item '.env.example' '.env'
Copy-Item '.env.example.docker' '.env.docker'
```

Не изменяйте скопированные значения в `frontend\.env`, если используются стандартные порты. В частности, оставьте пустым `VITE_API_BASE_URL`, а также сохраните `VITE_REVERB_ENABLED`, `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST` и `VITE_REVERB_PORT` из примера. Для Docker-запуска Vite значение `API_UPSTREAM` берётся из `frontend\.env.docker`.

Запустите Vite с HMR в Docker:

```powershell
docker compose --env-file .env.docker --profile dev up -d
docker compose --env-file .env.docker --profile dev logs -f frontend-dev
```

После строки Vite `ready` откройте:

```text
http://localhost:5173
```

Frontend отправляет запросы на относительные `/api` и `/sanctum`; Vite внутри Docker проксирует их на `http://host.docker.internal:8200` (значение `API_UPSTREAM` по умолчанию). Это необходимо для корректной работы Sanctum cookie-аутентификации.

## 5. Демо-учётные записи

После `db:seed` доступны локальные демонстрационные пользователи. Пароль: `password`.

| Роль | ИНН |
| --- | --- |
| Главный администратор | `770000000000` |
| Администратор торгов | `770000000001` |
| Участник | `770000000002` |
| Аудитор | `770000000003` |

Не используйте эти учётные записи или пароли в публичном окружении.

## 6. Полезные адреса

| Сервис | Адрес |
| --- | --- |
| Vue SPA | `http://localhost:5173` |
| Laravel API health | `http://localhost:8200/api/health` |
| Horizon | `http://localhost:8200/horizon` |
| MailHog | `http://localhost:8025` |
| pgAdmin, если включён tools profile | `http://localhost:8081` |
| Reverb | `ws://localhost:6001` |

## 7. Типовые команды

### Логи

```powershell
Set-Location 'D:\Projects\etp\backend'
docker compose logs -f backend-etp
docker compose logs -f horizon-etp
docker compose logs -f reverb-etp

# Frontend — в отдельном окне PowerShell
Set-Location 'D:\Projects\etp\frontend'
docker compose --env-file .env.docker --profile dev logs -f frontend-dev
```

### Миграции и сиды

```powershell
Set-Location 'D:\Projects\etp\backend'
docker compose exec -T backend-etp php artisan migrate
docker compose exec -T backend-etp php artisan db:seed
```

### Тесты backend

Тесты используют отдельную базу `etp_test`. Создайте её один раз, затем запустите тесты:

```powershell
Set-Location 'D:\Projects\etp\backend'
docker compose exec -T postgres-etp sh -lc 'createdb -U etp etp_test 2>/dev/null || true'
docker compose exec -T backend-etp php artisan test
```

### Пересборка frontend

Обычно HMR применяет изменения автоматически. Для production build-проверки:

```powershell
Set-Location 'D:\Projects\etp\frontend'
docker compose --env-file .env.docker --profile dev exec -T frontend-dev npm run build
```

### Остановка сервисов

```powershell
Set-Location 'D:\Projects\etp\backend'
docker compose down

Set-Location 'D:\Projects\etp\frontend'
docker compose --env-file .env.docker --profile dev down
```

Команда `down` не удаляет данные PostgreSQL, потому что они сохраняются в `backend\docker\postgres\data`.

### Полный сброс локальной базы

> Удаляет все данные development-базы.

```powershell
Set-Location 'D:\Projects\etp\backend'
docker compose down
Remove-Item -Recurse -Force '.\docker\postgres\data'
docker compose up -d --build
docker compose exec -T backend-etp php artisan migrate --force
docker compose exec -T backend-etp php artisan db:seed --force
```

## 8. Частые проблемы

| Симптом | Проверка / решение |
| --- | --- |
| `502` или API не отвечает | `docker compose ps`, затем `docker compose logs backend-etp nginx-etp`. |
| `scheduler-etp`, `horizon-etp` или `reverb-etp` перезапускаются с `vendor/autoload.php: No such file or directory` | Установите зависимости: `docker compose exec -T backend-etp composer install --no-interaction --no-progress --ignore-platform-reqs`, затем `docker compose up -d`. На чистом checkout используйте двухэтапный первый запуск из раздела 3. |
| Frontend показывает ошибки сети | Убедитесь, что backend работает на `8200`, а `frontend\.env.docker` содержит `API_UPSTREAM=http://host.docker.internal:8200`; затем перезапустите `frontend-dev`. |
| Не приходит сессия или `401` после login | Не задавайте `VITE_API_BASE_URL`; запросы должны идти через Vite proxy на относительный `/api`. |
| WebSocket не подключается | Проверьте `docker compose ps reverb-etp`, совпадение `REVERB_APP_KEY` в `backend\src\.env` и `VITE_REVERB_APP_KEY` во `frontend\.env`, затем перезапустите frontend. |
| Порт уже используется | Измените порт в соответствующем `.env` и перезапустите Compose. |
| Нет новых файлов после pull | Выполните `docker compose up -d --build`; для frontend перезапустите `frontend-dev`. |

## 9. Обновление кода

```powershell
Set-Location 'D:\Projects\etp\backend'
git pull origin main
docker compose up -d --build
docker compose exec -T backend-etp composer install --no-interaction --no-progress --ignore-platform-reqs
docker compose exec -T backend-etp php artisan migrate --force
docker compose exec -T backend-etp php artisan config:clear

Set-Location 'D:\Projects\etp\frontend'
git pull origin master
docker compose --env-file .env.docker --profile dev up -d
```

После обновления dependency-файлов (`composer.lock`, `package-lock.json`) обязательно повторите `composer install` или перезапустите соответствующий контейнер.
