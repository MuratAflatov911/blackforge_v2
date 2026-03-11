## BLACKFORGE (учебный проект)

Премиальный интернет‑магазин автомобильных дисков на **PHP + MySQL (XAMPP)**.

### Быстрый старт (XAMPP)

- **1) Скопируй проект** в `C:\xampp\htdocs\blackforge`
- **2) Импортируй базу** в phpMyAdmin:
  - открой phpMyAdmin → Import
  - выбери файл `database/blackforge.sql`
- **3) Настрой доступ к БД** в `app/includes/config.php` (host/user/password при необходимости)
- **4) Открой сайт**: `http://localhost/blackforge/public/`

### Админка

- Админка: `http://localhost/blackforge/public/admin/`
- **Тестовый логин/пароль админа (учебный режим)**:
  - Логин (email): `admin@blackforge.local`
  - Пароль: `Admin123!`
  - Чтобы создать этого админа автоматически, один раз открой: `http://localhost/blackforge/public/seed_admin.php`
  - После входа **удали** файл `public/seed_admin.php`

- Альтернатива: после регистрации можно выдать админа через SQL:

```sql
UPDATE users SET role='admin' WHERE email='you@example.com';
```

### Что уже реализовано

- **Каталог**: фильтры + сортировка + пагинация (`public/index.php`)
- **Карточка товара**: галерея (без обрезки), добавление в корзину, избранное (`public/product.php`)
- **Корзина**: количество/удаление/итог/промокод/оформление заказа (`public/cart.php`)
- **Избранное**: только для авторизованных (`public/favorites.php`)
- **Регистрация/авторизация**: email+ФИО+дата рождения (14+)+капча (`public/register.php`, `public/login.php`)
- **Админка**: товары CRUD + изображения (файл/URL), заказы/статусы, пользователи/роли (`public/admin/*`)

