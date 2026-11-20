# Agent Bridge API

Az Agent Bridge egy HTTP híd a helyben telepített `claude` CLI elé. Egyetlen
végponton keresztül küldhetsz promptot egy előre konfigurált **agent** nevében,
és visszakapod a Claude válaszát JSON formátumban. A beszélgetés állapotát
(session) az API kulcs automatikusan megőrzi, így a következő hívások
ugyanazt a beszélgetést folytatják.

## Előfeltételek

- Egy érvényes **API kulcs** (az admin felületen generálható). A kulcs egy
  agenthez van kötve, ami meghatározza a system promptot, a modellt és a
  prompt módot.
- A kulcs formátuma: `cc_` előtag + 40 karakter (pl. `cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`).
  A teljes kulcsot csak a létrehozáskor látod — tárold biztonságos helyen.

## Autentikáció

Minden kérésnél `Bearer` tokent kell küldeni az `Authorization` fejlécben:

```
Authorization: Bearer cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Hiányzó vagy érvénytelen kulcs esetén a válasz `401`:

```json
{ "message": "Invalid or missing API key." }
```

## Végpont

### `POST /api/prompt`

Elküld egy promptot a kulcshoz tartozó agentnek, és visszaadja a Claude válaszát.

#### Kérés fejlécek

| Fejléc          | Érték                |
| --------------- | -------------------- |
| `Authorization` | `Bearer <api-kulcs>` |
| `Content-Type`  | `application/json`   |
| `Accept`        | `application/json`   |

#### Kérés törzs

| Mező     | Típus    | Kötelező | Megkötés            |
| -------- | -------- | -------- | ------------------- |
| `prompt` | `string` | igen     | max. 20000 karakter |

```json
{
    "prompt": "Foglald össze egy mondatban, mi az a Laravel."
}
```

#### Sikeres válasz — `200 OK`

```json
{
    "success": true,
    "result": "A Laravel egy PHP keretrendszer webalkalmazások fejlesztéséhez.",
    "error": null,
    "agent_session": {
        "id": 12,
        "agent_id": 3,
        "workspace_id": 1,
        "session_id": "b1d2c3e4-...",
        "last_used_at": "2026-11-19T10:00:00.000000Z",
        "created_at": "2026-11-18T09:00:00.000000Z",
        "updated_at": "2026-11-19T10:00:00.000000Z"
    },
    "raw": { "...": "a claude CLI nyers JSON kimenete" }
}
```

| Mező            | Típus            | Leírás                                                              |
| --------------- | ---------------- | ------------------------------------------------------------------- |
| `success`       | `bool`           | A futás sikeres volt-e.                                             |
| `result`        | `string \| null` | Az agent válasza.                                                   |
| `error`         | `string \| null` | Hibaüzenet, ha volt hiba (`success: false` esetén).                 |
| `agent_session` | `object`         | A kulcshoz tartozó beszélgetés (session) tárolt adatai (lásd lent). |
| `raw`           | `mixed`          | A `claude` CLI teljes, nyers JSON kimenete.                         |

#### `agent_session` mezői

| Mező           | Típus            | Leírás                                                                   |
| -------------- | ---------------- | ------------------------------------------------------------------------ |
| `id`           | `int`            | A session azonosítója az adatbázisban.                                   |
| `agent_id`     | `int`            | A sessionhöz tartozó agent azonosítója.                                  |
| `workspace_id` | `int \| null`    | A sessionhöz tartozó munkaterület azonosítója (ha van).                  |
| `session_id`   | `string \| null` | A `claude` CLI beszélgetés azonosítója. A szerver automatikusan tárolja. |
| `last_used_at` | `string \| null` | Mikor használták utoljára a sessiont (ISO 8601).                         |
| `created_at`   | `string \| null` | Létrehozás ideje (ISO 8601).                                             |
| `updated_at`   | `string \| null` | Utolsó módosítás ideje (ISO 8601).                                       |

#### Hibás válaszok

| HTTP kód | Mikor                                              | Törzs                                                              |
| -------- | -------------------------------------------------- | ------------------------------------------------------------------ |
| `401`    | Hiányzó vagy érvénytelen API kulcs.                | `{ "message": "Invalid or missing API key." }`                     |
| `422`    | Validációs hiba (pl. hiányzó/túl hosszú `prompt`). | `{ "message": "...", "errors": { "prompt": ["..."] } }`            |
| `502`    | A `claude` CLI hibát adott vagy nem válaszolt.     | A sikeres válasz sémája, de `success: false` és kitöltött `error`. |

Példa `502`-re:

```json
{
    "success": false,
    "result": null,
    "error": "The claude command exited with an error.",
    "agent_session": {
        "id": 12,
        "agent_id": 3,
        "workspace_id": 1,
        "session_id": null,
        "last_used_at": null,
        "created_at": "2026-11-18T09:00:00.000000Z",
        "updated_at": "2026-11-18T09:00:00.000000Z"
    },
    "raw": null
}
```

## Beszélgetés (session) kezelés

- A **session automatikus**. Nem kell `session_id`-t küldened a kérésben.
- Az első hívás új beszélgetést indít, és a válaszban visszaadott
  `agent_session.session_id`-t a szerver elmenti az API kulcshoz.
- A következő hívások **ugyanazzal a kulccsal automatikusan folytatják** a
  korábbi beszélgetést — a Claude emlékszik a kontextusra.
- Így a kontextus megőrzéséhez nincs teendőd kliens oldalon; egy kulcs = egy
  folyamatos beszélgetés.

## Példák

### cURL

```bash
curl -X POST https://pelda.hu/api/prompt \
  -H "Authorization: Bearer cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"prompt": "Szia, ki vagy?"}'
```

### JavaScript (fetch)

```js
const res = await fetch('https://pelda.hu/api/prompt', {
    method: 'POST',
    headers: {
        Authorization: 'Bearer cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    body: JSON.stringify({ prompt: 'Szia, ki vagy?' }),
});

const data = await res.json();
console.log(data.result);
```

### PHP (Laravel HTTP kliens)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken('cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
    ->acceptJson()
    ->post('https://pelda.hu/api/prompt', [
        'prompt' => 'Szia, ki vagy?',
    ]);

$result = $response->json('result');
```

### Python (requests)

```python
import requests

res = requests.post(
    "https://pelda.hu/api/prompt",
    headers={
        "Authorization": "Bearer cc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "Accept": "application/json",
    },
    json={"prompt": "Szia, ki vagy?"},
)

print(res.json()["result"])
```

## Megjegyzések

- A `prompt` mező maximum **20000 karakter** lehet.
- A hívás **szinkron**: a válasz csak akkor érkezik meg, amikor a `claude` CLI
  végzett. A CLI timeout alapértelmezetten **120 másodperc** (`CLAUDE_CLI_TIMEOUT`).
- A system promptot, a modellt és a prompt módot az API kulcshoz rendelt
  **agent** határozza meg — ezek nem állíthatók a kérésben.
