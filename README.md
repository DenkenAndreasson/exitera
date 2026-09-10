# Exitera

Ett forum för en påhittad Tibia-server. PHP mot MariaDB, kört i Docker.
Inlämningsuppgift i Systemutveckling.

Allt renderas på servern. Det finns ingen JavaScript-fil i projektet och inget API
som webbläsaren pratar med.

## Kom igång

```bash
git clone https://github.com/DenkenAndreasson/exitera.git
cd exitera
docker compose up -d
```

Appen ligger sen på http://localhost:8080 och phpMyAdmin på http://localhost:8081
(`otforum` / `otforum`).

Man behöver ingen `.env`. Alla databasvariabler har fallback-värden i
`docker-compose.yml`. Databasen bygger sig själv från `sql/01-schema.sql` och
`sql/02-seed.sql` första gången containern startar, eftersom MariaDB kör allt som
ligger i `/docker-entrypoint-initdb.d` när volymen är tom.

### Testkonton

Lösenordet är `test1234` på alla.

| Konto | Roll |
|---|---|
| `admin@ot.local` | Sajt-admin, ingen guild |
| `leader@ot.local` | Guild leader i Red Rose |
| `general@ot.local` | General i Red Rose |
| `grunt@ot.local` | Grunt i Red Rose |
| `leader2@ot.local` | Guild leader i Black Knights |
| `sokande@ot.local` | Ingen guild, har en väntande ansökan till Red Rose |

## Vad saker heter

Uppgiften säger "grupp". Jag använder speltermer istället, så här är översättningen:

| Uppgiften | Appen |
|---|---|
| Grupp | Guild |
| Ämne / diskussion | Tråd |
| Inlägg | Post |
| Medlem | Grunt |
| Administratör | General och Guild leader |

Startsidan har två listor. **Communities** är öppna anslagstavlor (Leveling,
Questing, PvP, Bug Reports, Off-topic). Alla får läsa dem, även utloggade, och alla
inloggade får skriva. **Guilds** är slutna. Där ansöker man, blir godkänd av
guildens ledning, och först då syns innehållet.

Det är guilds som uppfyller uppgiftens gruppkrav. Communities är ett extra påhitt
för att sajten ska se levande ut, och de har inga medlemmar.

## Kraven

### Betyg G

| Krav | Var det finns |
|---|---|
| SQL-databas | MariaDB 10.6. Sju tabeller och 13 foreign keys i `sql/01-schema.sql` |
| Alla sidor i PHP | Hela `html/`. Noll JavaScript |
| Data hanteras säkert | Se avsnittet Säkerhet |
| Skapa användarkonto | `/register` |
| Kontot lagrar namn, epost, lösenordshash | `first_name`, `last_name`, `email`, `password_hash` i `users` |
| Inloggad kan skapa grupp med namn på vad som diskuteras | `/group/create`, namn och beskrivning |
| Medlem kan starta diskussion med ämne och första inlägg | `/topic/create`, ett formulär med två fält, sparas i en transaktion |
| Alla medlemmar kan svara | Svarsformuläret längst ner på `/topic/?id=N` |
| Se grupper man inte är med i och ansöka | Startsidan och `/groups` |
| Godkänna ansökningar | `/group/manage/?id=N` |

### Betyg VG

| Krav | Var det finns |
|---|---|
| Två roller per grupp, medlem eller administratör | `group_members.role`. Grunt är medlem, General och Guild leader är administratörer |
| Bara administratörer godkänner ansökningar | Handlern i `/group/manage` kräver nivå 3 eller högre |
| Administratör kan välja roll på övriga | Medlemslistan i `/group/manage/?id=N` |
| Inbjudningslänk, engångs, giltig 24 timmar | `/invite/?token=...`, skapas från Manage Guild. Eget avsnitt längre ner |

## Roller

| Nivå | Roll | Uppgiftens ord | Får göra |
|---|---|---|---|
| 1 | Noob | – | Läsa communities. Ingen rad i `group_members` |
| 2 | Grunt | Medlem | Starta trådar och svara i sin guild |
| 3 | General | Administratör | Dessutom godkänna och avslå ansökningar, ta bort trådar och inlägg |
| 4 | Guild leader | Administratör | Dessutom sätta roller, ta bort medlemmar och skapa inbjudningslänkar |
| 5 | Sajt-admin | – | Läsa och moderera överallt. Ligger på `users.is_admin` |

Uppgiften vill ha två roller, medlem och administratör. Jag har delat administratör
i två steg så att en leader kan ta hjälp av generaler utan att lämna ifrån sig
kontrollen över guilden. Grunt är medlem, resten är administratörer.

Rollen ligger på medlemskapet och inte på användaren. Två kolumner på `users` hade
glidit isär, för lämnar man en guild nollställs guild-kolumnen men rollen blir kvar,
och då finns det en General utan guild.

Sajt-admin är en helt egen axel. En sajt-admin är inte automatiskt leader någonstans,
och en Guild leader har noll makt utanför sin egen guild. Nivån slås alltid upp mot
den grupp sidan handlar om.

Communities och guilds delar tabell, och skillnaden mellan dem bor på ett enda
ställe:

```php
function can_read(array $group, ?array $user): bool
{
    if ($group['type'] === 'community') return true;   // även utloggad
    if ($user === null)                  return false;
    if ($user['is_admin'])               return true;
    return membership_in($group['id'], $user['id']) !== null;
}
```

Varje sida anropar `can_read()` eller `can_post()` överst i filen, före all HTML.

## Val jag gjort

**Bara administratörer godkänner ansökningar.** G-kravet säger att vilken medlem som
helst ska kunna godkänna, VG-kravet säger att bara administratörer ska kunna det. De
går inte ihop, så jag byggde VG-varianten. En Grunt ser inte Manage Guild.

**Man kan bara vara med i en guild åt gången.** Det är Tibia-troget och gör "min
guild" till en entydig fråga i hela gränssnittet. Guilds man inte är med i listas
alltid, men har man redan en guild är ansökningsknappen inaktiv. En ny användare,
vilket är utgångsläget för alla som testar, ser alla guilds och kan söka till vilken
som helst.

Regeln finns i PHP och inte i schemat, så den kontrolleras på fyra ställen: när man
ansöker, när man skapar en guild, inne i godkännandetransaktionen och inne i
inbjudningstransaktionen. De två sista använder `SELECT ... FOR UPDATE` så att två
samtidiga godkännanden inte kan passera varandra.

**Moderering är soft delete.** `deleted_at` sätts, raden ligger kvar och går att
återställa. Borttagna inlägg visas som `[Inlägget är borttaget]` istället för att
döljas helt, annars ser svaren under dem ut som fel.

**Guilden kan inte bli ledarlös.** Sista Guild leader går varken att degradera eller
ta bort. Utan den spärren kan ingen längre godkänna ansökningar eller sätta roller,
och det går inte att laga från gränssnittet.

**Ett avslag är slutgiltigt.** Ansökningsraden ligger kvar som `rejected` med
`handled_by` och `handled_at`, så beslutet går att läsa i efterhand. Blir man
däremot borttagen ur en guild raderas ansökningsraden, så då kan man söka igen.

## Inbjudningslänkar

En Guild leader skapar länken från Manage Guild. Token är 32 slumpade bytes från
`random_bytes()`, och `expires_at` sätts av databasen till `NOW() + INTERVAL 24 HOUR`
så att ingen PHP-tid eller tidszon är inblandad.

Engångsbruket ligger inte i vyn utan i handlern, inne i en transaktion:

```sql
UPDATE invites
SET used_at = NOW(), used_by = ?
WHERE id = ? AND used_at IS NULL AND expires_at > NOW()
```

Matchar den ingen rad rullas allt tillbaka och ingen blir medlem. Kollen på GET:en
är bara till för att visa ett vettigt felmeddelande. Klickar två personer samtidigt
ser båda knappen, men bara den enas `UPDATE` träffar en rad, och den andra får
`rowCount() === 0`. Samma villkor kollar utgångstiden en andra gång, så en länk som
hinner gå ut medan sidan står öppen går inte att lösa in.

Är man utloggad när man öppnar länken följer token med till `/register` och `/login`
som en query-parameter, och man skickas tillbaka till inbjudan direkt efteråt. Man
kan alltså skapa sitt konto via länken. Parametern valideras mot `/^[0-9a-f]{64}$/`
innan den används, så det går inte att skicka någon vidare någon annanstans med den.

Före `UPDATE`-satsen ligger en `SELECT ... FOR UPDATE` som låser användarens
eventuella guild-medlemskap, så en-guild-regeln inte går att kringgå genom att öppna
två inbjudningar samtidigt.

## Säkerhet

| | |
|---|---|
| SQL-injektion | Alla queries är prepared statements med `?`-parametrar, och `PDO::ATTR_EMULATE_PREPARES` är avstängt |
| XSS | All utskrift av databasdata går genom `htmlspecialchars()`. Inläggstext genom `nl2br(htmlspecialchars($body))`, i den ordningen |
| CSRF | En token per session, `bin2hex(random_bytes(32))`, jämförd med `hash_equals()`. Ligger i alla 15 formulär och kontrolleras i alla 9 filer som tar emot POST |
| Lösenord | `password_hash()` med bcrypt, `password_verify()` vid inloggning |
| Sessioner | `session_regenerate_id(true)` vid inloggning och registrering. Kakan är `HttpOnly` och `SameSite=Lax` och rensas vid utloggning |
| Åtkomstkontroll | Överst på varje sida och alltid även i POST-handlern. Medlemskapet hämtas från databasen varje gång, aldrig ur sessionen |
| Validering | Serversidan gör jobbet: `filter_var(..., FILTER_VALIDATE_EMAIL)`, minsta lösenordslängd och längdgränser som matchar `VARCHAR`-bredderna. `required` i HTML räknas inte som skydd |
| Felmeddelanden | `display_errors` av och `log_errors` på. Inga sökvägar eller stacktracer ut till besökaren |

En utgråad knapp är bara kosmetik och går att posta förbi med Postman eller DevTools.
Därför ligger varje behörighetsregel i handlern, och knappens utseende är bara en
spegling av samma regel.

### Kända begränsningar

- Sajten kör HTTP lokalt, så sessionskakan har inte `secure`-flaggan. Den hör hemma
  där först när sajten kör HTTPS.
- Sajt-admin har ingen egen adminpanel. Communities skapas i seed-datan.

## Postman

`postman/otforum.postman_collection.json` innehåller 21 anrop i fyra mappar.
Importera och kör mapparna i ordning, Postman sköter kakorna själv.

| Mapp | Vad den visar |
|---|---|
| 1, utan CSRF-token | Varje POST-handler i appen anropad utan token. Alla ska ge 403 |
| 2, inloggning | Hämtar en token ur formuläret och loggar in. Sätter sessionen för mapp 3 |
| 3, åtkomstkontroll | Gissade URL:er och POST med giltig token men fel roll. Alla ska ge 403 |
| 4, öppet innehåll | Kontrasten, det som ska ge 200, plus koll på att ingen hash eller stacktrace läcker |

Det är med flit som de nekade anropen är i majoritet. En 200 visar bara att sajten
fungerar, det är 403:orna som visar att den skyddar något.

## Filer

```
sql/01-schema.sql        Sju tabeller, 13 foreign keys
sql/02-seed.sql          Testkonton, communities, guilds, trådar och inlägg

html/inc/db.php          get_db(), PDO med exceptions och utan emulerade prepares
html/inc/auth.php        Sessioner, current_user(), rollnivåer, can_read(), can_post()
html/inc/csrf.php        Token, dolt fält och kontroll
html/inc/page.php        show_message(), statuskod och felsida på ett ställe
html/inc/box.php         Rutan som all layout byggs av
html/inc/header.php      Sidhuvud och navigation
html/inc/guilds.php      Grupplistor, medlemslista, knapplogik
html/inc/topics.php      Trådlista, trådsökning, inläggslista
html/inc/invites.php     Inbjudningslänkar

html/index.php           Startsida
html/register  /login  /logout
html/groups              Full grupplista
html/group               En grupp med trådlista
html/group/create  /apply  /manage
html/topic               En tråd med inlägg och svarsformulär
html/topic/create        Nytt ämne
html/invite              Inbjudningslänk
```

Miljön är PHP 8.4 med Apache och MariaDB 10.6, definierad i `Dockerfile` och
`docker-compose.yml`.
