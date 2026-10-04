# Setup Instructions

คู่มือติดตั้งและรัน **Networkproject2_1** — เกม Werewolf แบบหลายผู้เล่นผ่านเว็บ สำหรับ development และการสาธิตใน LAN

## 1. Requirements

| เครื่องมือ | ข้อกำหนด |
| --- | --- |
| PHP | 8.3 ขึ้นไป ตาม `composer.json` (`^8.3`) |
| PHP extensions | เปิดใช้ PDO, `pdo_sqlite`, `sqlite3`, mbstring, OpenSSL, tokenizer, XML/DOM, ctype, fileinfo และ session; ให้ Composer ตรวจ extensions ของ dependencies อีกครั้ง |
| Composer | Composer 2 |
| Node.js | แนะนำ Node 22.18 ขึ้นไปในสาย 22 หรือ Node 24.11 ขึ้นไป ตามข้อกำหนดของ Vite Plus ใน `package-lock.json` |
| npm / Git | ใช้ npm ที่มากับ Node และติดตั้ง Git |
| Database | SQLite สำหรับขั้นตอนในคู่มือนี้ ไม่ต้องติดตั้ง database server แยก |
| Network | สำหรับ LAN ให้เครื่อง server และผู้เล่นอยู่ในเครือข่ายที่เข้าถึงกันได้ และอนุญาต TCP 8000 กับ 8080 |

ตรวจเครื่องมือก่อนเริ่ม:

```sh
php -v
php -m
composer --version
node -v
npm -v
git --version
```

เครื่อง server ต้องเขียนได้ที่ `database/`, `storage/` และ `bootstrap/cache/` และต้องมีอินเทอร์เน็ตขณะติดตั้ง dependencies/build assets โดย Vite config มีการใช้ Bunny fonts ด้วย

## 2. Clone repository และติดตั้ง dependencies

```sh
git clone https://github.com/Someder9728/Networkproject2_1.git
cd Networkproject2_1
composer install
npm install
```

Repository มี `composer.lock` และ `package-lock.json` อยู่แล้ว หากต้องการติดตั้ง npm ตาม lockfile อย่างเคร่งครัด ใช้ `npm ci` แทน `npm install` ได้ ไม่จำเป็นต้องใช้ `composer update` ในการติดตั้งครั้งแรก

## 3. สร้าง `.env` และ APP_KEY

คัดลอกไฟล์ตั้งค่า โดยเลือกคำสั่งให้ตรงกับ shell:

**macOS / Linux / Git Bash**

```sh
cp .env.example .env
```

**Windows PowerShell**

```powershell
Copy-Item .env.example .env
```

หาก Composer สร้าง `.env` ให้แล้ว ให้แก้ไฟล์เดิมแทนการคัดลอกทับ จากนั้นสร้าง application key:

```sh
php artisan key:generate
```

คำสั่งนี้เติม `APP_KEY` ลง `.env` อย่าสร้าง key ใหม่ทุกครั้งที่เปิด server เพราะจะทำให้ข้อมูลที่เข้ารหัสและ session เดิมใช้ไม่ได้ และอย่า commit `.env` ลง GitHub

## 4. ตั้งค่า SQLite และ migrate

สร้างไฟล์ SQLite จาก root ของ repository ด้วยคำสั่งที่ใช้ได้ทั้ง Windows และ Unix:

```sh
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

แก้ค่าต่อไปนี้ใน `.env` โดยแทน path ให้ตรงกับเครื่องจริง:

```dotenv
APP_NAME="Networkproject2_1"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE="C:/projects/Networkproject2_1/database/database.sqlite"

SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_PATH=/
CACHE_STORE=database
QUEUE_CONNECTION=database
```

บน macOS/Linux ใช้ absolute path เช่น `/home/yourname/Networkproject2_1/database/database.sqlite` สำหรับ Windows ใช้ `/` ใน path และใส่เครื่องหมายคำพูดถ้ามีช่องว่าง

**ต้องแก้ `DB_DATABASE=database.sqlite` ที่มากับ `.env.example`** ให้ชี้ไปยังไฟล์ที่สร้างจริง อีกทางหนึ่งคือลบบรรทัด `DB_DATABASE` ออก เพื่อให้ `config/database.php` ใช้ค่าเริ่มต้น `database_path('database.sqlite')` อย่าปล่อยค่า path เดิมที่ไม่ตรงกับไฟล์

`DB_HOST`, `DB_PORT`, `DB_USERNAME` และ `DB_PASSWORD` ไม่ใช้กับ SQLite ส่วน session และ cache แบบ database ต้องมีตารางจาก migrations ก่อนใช้งาน

```sh
php artisan config:clear
php artisan migrate
php artisan migrate:status
```

Migrations สร้างทั้งตารางพื้นฐานของ Laravel และตารางเกม ได้แก่ rooms, players, votes/actions, chat messages และ game snapshot ไม่ต้อง seed เพื่อสร้างห้องหรือเริ่มเล่นเกม `DatabaseSeeder` ที่มีอยู่สร้าง Test User สำหรับระบบบัญชีเท่านั้น

## 5. ตั้งค่า Laravel Reverb / WebSocket

`.env.example` ตั้ง `BROADCAST_CONNECTION=log` และยังไม่มีค่า Reverb ให้เปลี่ยนเป็น `reverb` และเพิ่มค่าต่อไปนี้:

```dotenv
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=networkproject-local
REVERB_APP_KEY=networkproject-local-key
REVERB_APP_SECRET=replace-with-your-generated-secret

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

สร้าง secret ของตัวเองแล้วนำผลลัพธ์ไปแทน `replace-with-your-generated-secret`:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

App ID/key ในตัวอย่างเป็นค่าตัวอย่างสำหรับ local ใช้ค่าชุดเดียวกันกับ Reverb และ Laravel ไม่ต้องสมัคร Pusher เพราะโปรเจกต์ใช้ Reverb เป็น WebSocket server แม้ browser จะใช้ `pusher-js` เป็น client

| ตัวแปร | ใช้ที่ไหน |
| --- | --- |
| `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT` | Address/port ที่ process Reverb เปิดรับ connection |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Address ที่ Laravel ใช้ส่ง broadcast ไป Reverb |
| `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` | Address ที่ browser ของผู้เล่นใช้เชื่อม WebSocket |
| `REVERB_APP_SECRET` | Secret ฝั่ง server ห้ามเพิ่มเป็นตัวแปร `VITE_*` |

สำหรับ local HTTP ใช้ `http` เพื่อให้ client เชื่อม `ws` หากใช้ HTTPS ต้องมี TLS/reverse proxy ที่รองรับ `wss` และตั้งค่าฝั่ง browser ให้ตรงกับ endpoint นั้น

ค่าด้านบนอ่านจาก `config/reverb.php`, `config/broadcasting.php` และ `resources/js/websocket.js` ระบบตรวจสิทธิ์ private room channel ผ่าน `POST /game-broadcast/auth` ด้วย session ของผู้เล่น ไม่ใช่การตั้ง `routes/channels.php` เพิ่มเอง

หลังแก้ `.env`:

```sh
php artisan config:clear
```

## 6. Build frontend

```sh
npm run build
```

Script จริงใช้ `vp build` ผ่าน Vite Plus และสร้าง assets ใน `public/build` จุดเข้า frontend ใน `vite.config.js` คือ `resources/css/app.css`, `resources/js/app.js` และ `resources/js/passkeys.js`

**ทุกครั้งที่เปลี่ยน `VITE_REVERB_*` ต้อง build ใหม่** เพราะค่าเหล่านี้ถูกฝังใน JavaScript ระหว่าง build การเปลี่ยน `.env` อย่างเดียวไม่เปลี่ยนค่าใน assets เดิม

## 7. รัน Laravel + Reverb + scheduler

เปิด terminal แยกกัน 3 หน้าต่าง แต่ละหน้าต่างอยู่ที่ root ของ repository และปล่อยทั้งสาม process ทำงานตลอดการเล่น

**Terminal 1 — Laravel HTTP server**

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — Reverb WebSocket server**

```sh
php artisan reverb:start --host=0.0.0.0 --port=8080
```

**Terminal 3 — scheduler**

```sh
php artisan schedule:work
```

เปิด [http://localhost:8000/room-test](http://localhost:8000/room-test) เพื่อสร้างหรือเข้าร่วมห้อง

Scheduler สำคัญต่อ gameplay: `routes/console.php` เรียก `game:advance-phases` ทุกวินาที พร้อม `withoutOverlapping()` เพื่อให้ server เปลี่ยน discussion/voting/night ที่หมดเวลา หากไม่รัน scheduler หน้าเว็บอาจนับเวลาหมดแต่ phase ไม่เดินต่อ

ตรวจรายการ schedule หรือสั่งตรวจ phase ที่หมดเวลาด้วยตนเองได้:

```sh
php artisan schedule:list
php artisan game:advance-phases
```

Events ของเกมปัจจุบันใช้ `ShouldBroadcastNow` จึงไม่ต้องมี queue worker เพื่อ broadcast เกม หากพัฒนาเพิ่มงาน queue ให้เปิด terminal เพิ่ม:

```sh
php artisan queue:listen --tries=1 --timeout=0
```

สำหรับแก้ frontend แบบ hot reload ใช้ `npm run dev` ใน terminal เพิ่ม `composer dev` รัน Laravel server, queue listener และ frontend dev server แต่ **ยังไม่รัน Reverb กับ scheduler** ต้องเปิดสอง process นั้นเอง

## 8. เปิดเล่นจากเครื่องอื่นใน LAN

ตัวอย่างเครื่อง server มี IPv4 เป็น `192.168.1.50` ให้แทนด้วย IP จริงจาก `ipconfig` บน Windows หรือการตั้งค่า network ของระบบ

1. ให้ทุกเครื่องอยู่ใน LAN ที่เข้าถึงกันได้ ตรวจว่า Wi-Fi ไม่มี client isolation
2. แก้ `.env` ที่เครื่อง server:

```dotenv
APP_URL=http://192.168.1.50:8000
SESSION_DOMAIN=null

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_HOST=192.168.1.50
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

3. ใช้ค่า App ID/key/secret จากขั้นตอนก่อน แล้วอัปเดต config และ assets:

```sh
php artisan config:clear
npm run build
```

4. หยุด Laravel server เดิมแล้วเปิดใหม่ให้รับ connection จาก LAN:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

5. เปิด Reverb และ scheduler ตามขั้นตอนที่ 7 หาก process เปิดอยู่ก่อนแก้ `.env` ให้หยุดแล้วเปิดใหม่
6. อนุญาต inbound TCP 8000 และ 8080 หรืออนุญาต PHP ผ่าน firewall สำหรับเครือข่าย private
7. ทุกเครื่อง รวมเครื่อง server เปิด `http://192.168.1.50:8000/room-test` แล้วใช้รหัสห้องเดียวกัน

`localhost` บนเครื่องผู้เล่นหมายถึงเครื่องผู้เล่นเอง จึงต้องใช้ IP server ใน `VITE_REVERB_HOST` ส่วน `REVERB_HOST=127.0.0.1` คงไว้ได้ เพราะ Laravel และ Reverb อยู่เครื่องเดียวกัน `0.0.0.0` เป็น address สำหรับ bind ไม่ใช่ URL ที่ผู้เล่นใช้เปิดเว็บ

สำหรับ demo ใน LAN แนะนำใช้ frontend ที่ build แล้ว ไม่ต้องเปิด Vite dev server หากเคยเปิด `npm run dev` ให้หยุดก่อน และตรวจว่าไม่มีไฟล์ `public/hot` ค้างชี้ไป dev server เดิม ตัวอย่างการลบเฉพาะไฟล์นี้บน PowerShell:

```powershell
if (Test-Path public/hot) { Remove-Item -LiteralPath public/hot }
```

หาก IP server เปลี่ยน ให้แก้ `APP_URL`/`VITE_REVERB_HOST`, build ใหม่ และเปิด process ใหม่ ใช้ URL รูปแบบเดียวกันตลอด demo เพื่อไม่ให้ session แยกระหว่าง localhost กับ LAN IP

## 9. ตรวจการทำงานและแก้ปัญหาเบื้องต้น

| อาการ | สิ่งที่ควรตรวจ |
| --- | --- |
| HTTP 500 | ดู `storage/logs/laravel.log` และ terminal ตรวจ APP_KEY, database path, migrations และสิทธิ์เขียน directories |
| SQLite file not found / no such table | สร้าง `database/database.sqlite`, แก้ `DB_DATABASE`, clear config แล้ว migrate |
| Vite manifest not found | รัน `npm run build` และตรวจ `public/build/manifest.json` |
| หน้าเปิดได้แต่ไม่ realtime | ตรวจ `BROADCAST_CONNECTION=reverb`, credentials, process Reverb และ `VITE_REVERB_*` แล้ว build ใหม่ |
| เครื่องอื่นเปิดเว็บไม่ได้ | ตรวจ server bind `0.0.0.0`, IP, firewall TCP 8000 และ network isolation |
| เปิดเว็บได้แต่ WebSocket ต่อไม่ได้ใน LAN | ตรวจ `VITE_REVERB_HOST` เป็น IP server และ firewall TCP 8080 |
| Timer หมดแต่เกมไม่เปลี่ยน phase | ตรวจ `php artisan schedule:work` และ error ของ `game:advance-phases` |
| 419 หรือ private channel 403 | ตรวจ cookies/session, URL ที่ใช้, CSRF และสมาชิกห้องปัจจุบัน ผู้ที่ออกถาวรไม่มีสิทธิ์เข้าฟังห้องเดิม |
| HTTP 429 | ระบบมี rate limit แยกสำหรับ chat read/send และ broadcast auth ให้รอสักครู่และตรวจการยิง request ซ้ำ |
| `GAME_SNAPSHOT_UNAVAILABLE` / HTTP 409 | สถานะเกมที่บันทึกไม่พร้อมใช้งาน ใช้หน้าออกจากห้องที่ระบบแสดง แล้วสร้างห้องใหม่ |

สำหรับตรวจ dependency requirements หลังติดตั้ง:

```sh
composer check-platform-reqs
```

# Project Overview

**Networkproject2_1** เป็นเกม Werewolf / social deduction แบบ multiplayer บนเว็บ ผู้เล่นสร้างห้องหรือเข้าห้องด้วยรหัส 6 ตัวอักษร เลือกความยาก easy/hard และเล่นผ่าน lobby, ช่วงอภิปราย, โหวต และกลางคืน โดย Laravel เป็นผู้ควบคุมสถานะเกมและส่งข้อมูลอัปเดตผ่าน Reverb

เกมเริ่มได้เมื่อมีผู้เล่น **4 หรือ 6 คน** และห้องรับได้สูงสุด 6 คน มีบทบาท Werewolf, Villager และ Seer โดยสุ่มบทบาทจาก server ผู้เล่นเกมใช้ชื่อและ UUID ใน session ส่วนระบบบัญชี Fortify/Livewire ที่มีใน starter kit เป็นอีกส่วนหนึ่ง ไม่จำเป็นต้องสมัครบัญชีเพื่อใช้เส้นทางเกม

# Architecture / Server Authority Flow

```text
Browser (Blade + JavaScript + Laravel Echo)
  | HTTP request + session cookie + CSRF token
  v
Routes / Controllers
  | ตรวจ input และตัวตนผู้เล่นจาก session
  v
RoomService / RoomDatabaseService / ChatService
  | ตรวจ membership, phase, role และสิทธิ์ action
  | RoomLock + database transaction
  v
GameService / GameLogic
  | สุ่มบทบาท ประมวลผลโหวต/night action และผลเกม
  v
Database: rooms.game_snapshot + players + votes_and_actions + chat_messages
  | ส่ง event หลัง commit ใน flow ที่กำหนด
  v
Reverb -> private-rooms.{CODE} -> Echo -> อัปเดต/โหลดข้อมูลจาก server

Scheduler -> game:advance-phases -> RoomService -> เปลี่ยน phase ที่หมดเวลา
```

- Browser ส่งความต้องการของผู้เล่น แต่ server ตรวจและตัดสินผลจริง รวมถึงเป้าหมาย action, ช่วงเวลาที่อนุญาตและข้อมูลที่ผู้เล่นเห็น
- `RoomService::getGameView()` เตรียมข้อมูลตามผู้เล่น รวมบทบาทและผลตรวจ Seer ที่เกี่ยวข้อง แทนการส่ง game snapshot ทั้งก้อนให้ทุกคน
- `rooms.game_snapshot` เก็บสถานะเกมร่วมกับ `game_uuid` และข้อมูลใน relational tables เพื่อให้ HTTP requests และ scheduler ใช้สถานะที่บันทึกไว้
- `RoomLock` ใช้ **file cache store โดยตรง** แม้ `CACHE_STORE=database` ช่วยประสานคำสั่งในห้องเดียวกันสำหรับการรันบนเครื่องเดียว พร้อม transactions สำหรับการบันทึกข้อมูล
- `POST /game-broadcast/auth` ตรวจ UUID ใน session และ membership ก่อนอนุญาตให้ subscribe private room channel ChatService ตรวจช่องสนทนาที่อ่าน/ส่งได้จากสถานะของผู้เล่น
- Events `room.updated`, `phase.changed` และ `chat.updated` แจ้งการเปลี่ยนแปลงให้ client เรียกข้อมูลหรืออัปเดตหน้า โดย server ยังคงเป็นแหล่งข้อมูลหลัก

การวางหลาย application servers ต้องออกแบบ shared locking/storage เพิ่ม เพราะ file lock ปัจจุบันอยู่บน filesystem ของแต่ละเครื่อง

# Features

- สร้าง/เข้าร่วมห้องด้วยรหัสห้อง 6 ตัวอักษร พร้อม lobby และรายชื่อผู้เล่น
- รองรับเกม 4 หรือ 6 คน และความยาก easy/hard
- สุ่ม Werewolf, Villager และ Seer โดยมี Seer 1 คน
- วงจร discussion, voting และ night พร้อม timer และการเปลี่ยน phase จาก server
- โหวตกลางวัน, Werewolf night action และ Seer investigation
- Random events และกติกาที่ปรับตาม difficulty
- Realtime room/phase updates และ chat ที่ตรวจสิทธิ์ตามผู้เล่น
- บันทึกสถานะเกม, votes/actions และข้อความลงฐานข้อมูล
- จัดการการออกจากเกมถาวร และหน้าแจ้งเมื่อ game snapshot ใช้งานไม่ได้
- แยก rate limit สำหรับ chat และ broadcast authentication

# Tech Stack

| ส่วน | เทคโนโลยี |
| --- | --- |
| Backend | PHP `^8.3`, Laravel `^13.17` |
| Game logic | PHP classes ใน `app/GameLogic` และ service layer |
| Views / starter kit | Blade, Livewire `^4.1`, Flux `^2.13.1`, Fortify `^1.37.2` |
| Realtime | Laravel Reverb `~1.12.0`, Laravel Echo `~2.5.0`, pusher-js `~8.6.0` |
| Frontend build | Vite `^8.0.0`, Vite Plus `0.3.0`, Laravel Vite Plugin `^3.1` |
| Styling | Tailwind CSS 4 |
| Database | SQLite สำหรับ local setup; มี connection config สำหรับฐานข้อมูลอื่น |
| Quality tooling | Pest 4, Laravel Pint, Larastan/PHPStan |
| Container deployment | Docker, Apache, Supervisor และ startup scripts ใน `docker/` |

เวอร์ชันในตารางเป็น constraints จาก manifests; เวอร์ชันที่ติดตั้งจริงดูจาก lockfiles

# Project Structure

```text
app/
├── Console/Commands/AdvanceGamePhases.php  # ตรวจ phase ที่หมดเวลา
├── Events/                               # Room/phase/chat broadcast events
├── GameLogic/                            # Engine, roles, phases, difficulty, events
├── Http/Controllers/                     # Room, game, chat, broadcast auth
├── Models/                               # Room, Player, VoteAction, User
├── Services/
│   ├── RoomService.php                   # รวม flow และสถานะเกม
│   ├── RoomDatabaseService.php           # ห้องและ membership ใน DB
│   ├── GameService.php                   # สร้าง game session/configuration
│   └── ChatService.php                   # Chat persistence และสิทธิ์ช่อง
└── Support/RoomLock.php                  # File-store locks ต่อห้อง
bootstrap/app.php                        # Routing, health endpoint, exception handling
config/                                  # Database, broadcasting, Reverb, session, cache
database/migrations/                     # Laravel tables และ schema เกม
resources/
├── css/app.css
├── js/                                  # app, websocket, chat และ passkeys
└── views/                               # Welcome, room-test, lobby, game, auth/settings
routes/
├── web.php                              # HTTP routes ของเกมและหน้าเว็บ
├── console.php                          # game:advance-phases ทุกวินาที
└── settings.php                         # Settings routes ของ starter kit
docker/                                  # Apache, Supervisor, startup script
tests/                                   # Pest tests ที่มีใน repository
Dockerfile
composer.json / composer.lock
package.json / package-lock.json
vite.config.js
.env.example
```

กติกาเกมอยู่ใน `app/GameLogic/` เช่น `DifficultyConfig.php`, `GameConfiguration.php` และ `RandomEvent.php` ปัจจุบัน `config/game.php` เป็นไฟล์ว่าง จึงไม่มี game environment variables ที่ต้องเพิ่มจากไฟล์นี้

# Demo / Usage Notes

1. เปิด Laravel, Reverb และ scheduler ตาม Setup Instructions แล้วเข้า `/room-test`
2. ผู้สร้างห้องกรอกชื่อและเลือก easy/hard แล้วแชร์รหัสห้องให้เพื่อน
3. ผู้เล่นอื่นกรอกชื่อและรหัสห้อง โดยแต่ละคนใช้คนละเครื่องหรือ browser profile/session
4. เมื่อมี 4 หรือ 6 คน ให้เริ่มเกมผ่าน lobby แล้วใช้ปุ่มในหน้าเกมเพื่อเข้าสู่ช่วงอภิปราย
5. ตรวจว่าทุกคนเห็นสถานะห้องและ phase อัปเดต โหวตในช่วง voting และทำ action ตามบทบาทในช่วง night
6. รอให้ scheduler เปลี่ยน phase และเล่นต่อจนระบบแสดงผลเกม

หลาย tab ใน browser profile เดียวกันแชร์ cookie/session จึงอาจเป็นผู้เล่นคนเดียวกัน หาก demo บนเครื่องเดียวให้ใช้ browser ต่างกันหรือ profiles แยกกัน Incognito หลายหน้าต่างของ browser เดียวกันอาจยังแชร์ session กัน

การ refresh หน้ายังใช้ session เดิม แต่การกดออกจากเกมถาวรต่างจากการปิด tab และผู้ที่ออกถาวรไม่สามารถเข้าห้องเดิมด้วย membership เดิมได้ เก็บ SQLite และ session ไว้หากต้องการคงข้อมูลเดิม และหลีกเลี่ยง `migrate:fresh` ระหว่าง demo เพราะจะลบข้อมูลทั้งหมด

Repository มี Dockerfile และ Supervisor ที่รัน Apache, Reverb และ scheduler สำหรับ deployment โดย `docker/start.sh` ทำ migration ตอนเริ่มระบบ ส่วนค่า `VITE_REVERB_*` เป็น Docker build arguments ต้องกำหนดตอน build หากนำไป deploy จริงให้ใช้ HTTPS/WSS, `APP_DEBUG=false`, คง `APP_KEY` และจัด persistent database/storage ตาม hosting ที่เลือก

สำหรับตรวจคุณภาพโค้ดใช้ script ที่มีอยู่:

```sh
composer test
```

Script นี้ clear config, ตรวจ formatting ด้วย Pint, วิเคราะห์ด้วย PHPStan แล้วรัน tests ชุด tests ที่มีอยู่ไม่ควรถูกตีความว่าเป็นการรับรอง gameplay ทุก flow ให้ทดสอบ demo 4/6 ผู้เล่นและ realtime ด้วยจริงเพิ่มเติม

---

คู่มือนี้อ้างอิงไฟล์ใน repository ที่ commit `e658e45e34f7c5d4dff476f699e5638f67d4db48` หาก dependencies หรือ config เปลี่ยน ให้ตรวจ manifests, lockfiles และ source ที่เกี่ยวข้องก่อนปรับขั้นตอน
