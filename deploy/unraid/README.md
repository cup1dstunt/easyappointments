# Easy!Appointments auf Unraid

Diese Anleitung installiert den Fork als Stack aus zwei Containern: der App
(Apache + PHP, fertig gebautes Image) und einer MariaDB. Alle Daten liegen
unter `/mnt/user/appdata/easyappointments`.

Die `docker-compose.yml` im Hauptordner des Repositorys ist die
Entwicklungsumgebung von Upstream (Xdebug, phpMyAdmin, LDAP, Quellcode als
Bind-Mount) und nicht für den Betrieb gedacht.

## Voraussetzungen

- Unraid 6.12 oder neuer mit dem Plugin **Docker Compose Manager**
  (Apps-Tab, nach "Compose Manager" suchen).
- Das Image `ghcr.io/cup1dstunt/easyappointments` wird bei jedem Push auf
  `main` von GitHub Actions gebaut. Nach dem ersten Build unter GitHub,
  Profil, Packages, `easyappointments`, Package settings die Sichtbarkeit auf
  **Public** stellen, sonst kann Unraid das Image nicht ohne Login laden.

## Installation

1. Docker, Compose, **Add New Stack**, Name `easyappointments`.
2. Beim Stack auf das Zahnrad, **Edit Stack**, **Compose File**: den Inhalt von
   [`docker-compose.yml`](docker-compose.yml) einfügen und speichern.
3. **Edit Stack**, **ENV File**: den Inhalt von [`.env.example`](.env.example)
   einfügen und ausfüllen:
   - `BASE_URL`: die Adresse, unter der die Seite aufgerufen wird, z. B.
     `http://192.168.1.10:8090` oder `https://termine.example.org` hinter
     einem Reverse Proxy. Ohne Schrägstrich am Ende.
   - `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ENCRYPTION_KEY`: je einen zufälligen
     Wert, z. B. mit `openssl rand -hex 32` im Unraid-Terminal.
   - `ENCRYPTION_KEY` danach nicht mehr ändern, sonst werden bestehende
     Sitzungen ungültig.
4. **Compose Up**. Beim ersten Start legt MariaDB die Datenbank an, das dauert
   etwa eine halbe Minute.
5. `BASE_URL` im Browser öffnen. Der Installationsassistent erscheint: Admin-
   Konto und Firmendaten eintragen, **Install**. Dabei werden alle Migrationen
   inklusive der Fork-Features (Buchungsschritt-Reihenfolge, Provider-E-Mail-
   Hinweis, Reply-To an Kunden) ausgeführt.

## Reverse Proxy

Hinter Nginx Proxy Manager, SWAG oder Traefik den Proxy auf
`http://<unraid-ip>:8090` zeigen lassen und `BASE_URL` auf die öffentliche
`https://`-Adresse setzen. Der Proxy muss `X-Forwarded-Proto` mitsenden (bei
den genannten ist das Standard), damit Links und Cookies `https` verwenden.

## Updates

Docker, Compose, beim Stack **Update Stack** (lädt das neueste Image) und
danach **Compose Up**. Anschließend als Admin `BASE_URL/index.php/update`
aufrufen, damit neue Datenbank-Migrationen laufen. Vorher ein Backup machen.

## Backup

Den Ordner `/mnt/user/appdata/easyappointments` sichern, z. B. mit dem Plugin
**Appdata Backup**. Für ein konsistentes Datenbank-Backup im laufenden Betrieb:

```sh
docker exec easyappointments-db sh -c 'mariadb-dump -ueasyappointments -p"$MARIADB_PASSWORD" easyappointments' > /mnt/user/backups/easyappointments.sql
```

## Image selbst bauen

Ohne GitHub-Package kann Unraid das Image auch direkt aus dem Repository
bauen: in der Compose-Datei die Zeile `image:` auskommentieren und
`build: https://github.com/cup1dstunt/easyappointments.git#main` aktivieren.
