# Easy!Appointments auf einem beliebigen Docker-System

Diese Anleitung startet den Fork als Stack aus zwei Containern (App mit Apache +
PHP und MariaDB) auf jedem System mit Docker und Docker Compose v2 (Linux,
macOS, Windows mit Docker Desktop/WSL, NAS, VPS). Die Daten liegen in benannten
Docker-Volumes (`storage` und `db`), es sind keine festen Pfade nötig.

Für Unraid gibt es eine eigene Variante: [`deploy/unraid`](../unraid/README.md).
Die `docker-compose.yml` im Hauptordner des Repositorys ist die
Entwicklungsumgebung von Upstream und nicht für den Betrieb gedacht.

## Häufiger Fehler: `invalid reference format`

```text
$ docker pull https://github.com/cup1dstunt/easyappointments
invalid reference format
```

`docker pull` erwartet den Namen eines **Images**, keine Repository-URL. Es gibt
zwei richtige Wege:

```sh
# Fertiges Image laden (kein Quellcode nötig)
docker pull ghcr.io/cup1dstunt/easyappointments:latest

# oder das Repository holen (dafür ist git da)
git clone https://github.com/cup1dstunt/easyappointments.git
```

Für den Betrieb reicht der zweite Weg mit Compose (unten): er holt die
Konfiguration per `git clone` und das Image automatisch beim Start.

## Installation

```sh
git clone https://github.com/cup1dstunt/easyappointments.git
cd easyappointments/deploy/docker
cp .env.example .env
```

In `.env` ausfüllen:

- `BASE_URL`: Adresse, unter der die Seite aufgerufen wird, z. B.
  `http://192.168.1.20:8090` oder `https://termine.example.org` hinter einem
  Reverse Proxy. Ohne Schrägstrich am Ende.
- `APP_PORT`: Port auf dem Host (Standard `8090`).
- `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ENCRYPTION_KEY`: je ein zufälliger Wert,
  z. B. mit `openssl rand -hex 32`. `ENCRYPTION_KEY` danach nicht mehr ändern.

Starten:

```sh
docker compose up -d
```

Beim ersten Start legt MariaDB die Datenbank an (etwa eine halbe Minute). Danach
`BASE_URL` im Browser öffnen: der Installationsassistent erscheint. Admin-Konto
und Firmendaten eintragen, **Install**. Dabei laufen alle Migrationen inklusive
der Fork-Features.

Status und Logs: `docker compose ps`, `docker compose logs -f app`.

## E-Mail-Versand

Nach der Installation als Admin unter **Einstellungen > E-Mail (SMTP)** den
Mailserver eintragen und mit **Test-E-Mail senden** prüfen. Das Image hat keinen
eigenen Mailserver.

## Reverse Proxy

Den Proxy auf `http://<host>:<APP_PORT>` zeigen lassen und `BASE_URL` auf die
öffentliche `https://`-Adresse setzen. Der Proxy muss `X-Forwarded-Proto`
mitsenden (bei Nginx Proxy Manager, Traefik, Caddy Standard bzw. leicht
einzustellen).

## Updates

```sh
cd easyappointments/deploy/docker
git pull                       # neue Compose-Datei, falls geändert
docker compose pull            # neues Image laden
docker compose up -d           # Container neu erstellen
```

Danach als Admin `BASE_URL/index.php/update` aufrufen (Migrationen für bereits
installierte Datenbanken laufen beim Containerstart zusätzlich automatisch).
Vorher ein Backup machen.

## Backup

```sh
docker compose exec db sh -c 'mariadb-dump -ueasyappointments -p"$MARIADB_PASSWORD" easyappointments' > backup.sql
docker run --rm -v easyappointments_storage:/s -v "$PWD":/b alpine tar czf /b/storage.tgz -C /s .
```

Zurückspielen: `backup.sql` per `mariadb` in den `db`-Container einlesen und
`storage.tgz` in das Volume `easyappointments_storage` entpacken.

## Image selbst bauen

Falls das Image von GHCR nicht erreichbar ist oder eigene Änderungen laufen
sollen, direkt aus dem Checkout bauen:

```sh
docker compose up -d --build
```

Das baut das Image aus dem Hauptordner des Repositorys (`Dockerfile`) und startet
es. Der erste Build dauert einige Minuten.

## Image von GHCR braucht Login?

Das Image ist öffentlich und braucht keinen Login. Falls `docker pull` trotzdem
`unauthorized` meldet, ist das Package nicht öffentlich: dann mit
`docker login ghcr.io` und einem GitHub-Token (`read:packages`) anmelden oder
lokal bauen (siehe oben).
