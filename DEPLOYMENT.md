# TW4 Deployment Guide

TW4 is a good fit for a small VPS running Docker Compose. That keeps the app close to its current Docker-based development setup and preserves the persistent state it needs for scoring.

## Recommended Hosting Shape

Use a VPS such as DigitalOcean, Hetzner, or AWS Lightsail.

Minimum practical stack:

1. Ubuntu 24.04 LTS
2. Docker Engine and Docker Compose plugin
3. TW4 app container built from [Dockerfile](Dockerfile)
4. MySQL 8.0 container
5. Caddy or Nginx as the HTTPS reverse proxy
6. Persistent volumes for MySQL data and PHP sessions

## Free Hosting Recommendation

If you want a free host that still fits TW4 well, use **Oracle Cloud Always Free**.

Why it fits:

1. It gives you a real VM, so you can run Docker Compose exactly the way this app expects.
2. You can keep persistent MySQL and session storage on disk.
3. You can expose a normal HTTPS URL for your tester without reworking the app.

What to use there:

1. One Ubuntu VM instance.
2. Docker Engine and Docker Compose plugin.
3. The production stack in [docker-compose.prod.yml](docker-compose.prod.yml).
4. [Caddyfile](Caddyfile) for HTTPS termination.

Oracle Cloud is the best free option here because TW4 is stateful and container-friendly, but not a good fit for shared free PHP hosting.

## Oracle Cloud Quick Start

Use this path if you want the free option with the least friction:

1. Create an Oracle Cloud Always Free Ubuntu VM.
2. Open inbound ports `22`, `80`, and `443` in the Oracle security list.
3. SSH into the VM and install Docker plus the Compose plugin.
4. Clone the TW4 repository onto the VM.
5. Copy `.env.example` to `.env`.
6. Set `DB_PASSWORD`, `CADDY_DOMAIN`, and `CADDY_EMAIL` in `.env`.
7. Run `./scripts/bootstrap-production.sh`.
8. Start the production stack with `docker compose -f docker-compose.prod.yml up -d --build`.
9. Visit the HTTPS domain and verify the scorer menu loads.
10. Run a smoke test as scorer and admin before sharing the URL.

## Oracle Setup Checklist

Use this while you are actually creating the Oracle instance:

1. Sign in to Oracle Cloud and open the Always Free VM creation page.
2. Choose Ubuntu 24.04 LTS for the image.
3. Pick the smallest Always Free shape available.
4. Create or select an SSH key pair and save the private key safely.
5. Finish VM creation and wait for the instance to reach `RUNNING`.
6. Add inbound rules for `22`, `80`, and `443`.
7. SSH into the VM using the public IP.
8. Install Docker, Docker Compose, Git, and any basic admin tools you prefer.
9. Clone this TW4 repository onto the VM.
10. Copy `.env.example` to `.env` and set `DB_PASSWORD`, `CADDY_DOMAIN`, and `CADDY_EMAIL`.
11. Run `./scripts/bootstrap-production.sh`.
12. Start the stack with `docker compose -f docker-compose.prod.yml up -d --build`.
13. Wait for Caddy to obtain TLS certificates.
14. Open the HTTPS URL in a browser and confirm the scorer menu loads.
15. Log in as scorer and admin and run a quick smoke test.

## Oracle First Login Commands

After SSHing into the VM, these are the first commands to run:

```bash
sudo apt update
sudo apt install -y git docker.io docker-compose-plugin
sudo usermod -aG docker "$USER"
newgrp docker
git clone https://github.com/nedbollard/tw4-golf-management.git TW4
cd TW4
cp .env.example .env
```

Then edit `.env` with your real `DB_PASSWORD`, `CADDY_DOMAIN`, and `CADDY_EMAIL` before running the bootstrap script.

## Why This Fits TW4

TW4 currently expects:

1. PHP 8.3 with Apache
2. MySQL for application data
3. Environment variables for DB connection settings
4. Persistent PHP sessions
5. A writable filesystem for logs and any future uploads

The current Docker setup already models most of this, so the smallest deployment risk is to keep the same architecture in production.

## Recommended Deployment Steps

1. Provision the VPS.
2. Point a domain or subdomain such as `tw4.yourdomain.com` at the server.
3. Install Docker and Compose.
4. Copy the repository to the server.
5. Create a production `.env` with real values for `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `DEBUG=false`.
6. Set `CADDY_DOMAIN` and `CADDY_EMAIL` in `.env`.
7. Bring up the production stack with `docker compose -f docker-compose.prod.yml up -d --build`.
8. Let Caddy issue TLS certificates and serve the public domain.
9. Keep MySQL private to the Docker network and do not expose it publicly.
10. Do not publish phpMyAdmin unless you deliberately add it behind a private network or IP restriction.

## TW4 Bootstrap Checklist

Fresh production setup needs a deliberate database bootstrap. TW4 is not fully “migrations only” end-to-end yet.

1. Create the base database/schema.
2. Create the live database/schema used by round scoring.
3. Seed the live round row shape expected by the app.
4. Confirm the scorer menu can see a not_started round state.
5. Verify the finish-round flow resets workflow to not_started and roster status back to active.

Important note: the live scoring path relies on a permanent live round record. That means the production database must be prepared carefully before handing the app to a tester.

## Tester Launch Checklist

Before you share the public URL:

1. Log in as scorer and admin.
2. Start a round.
3. Enter at least four cards.
4. Present results.
5. Finish the round.
6. Confirm the scorer menu shows the expected flash messages and status changes.
7. Confirm the next round can be started without clearing live cards manually.

## Production Commands

Typical launch sequence:

```bash
cp .env.example .env
# edit .env with real DB_PASSWORD, CADDY_DOMAIN, and CADDY_EMAIL
./scripts/bootstrap-production.sh
docker compose -f docker-compose.prod.yml up -d --build
```

To follow the logs:

```bash
docker compose -f docker-compose.prod.yml logs -f
```

The bootstrap script will start the database container, create the `TW4_base`
and `TW4_live` databases, and replay the repository migrations in order.

## Server Install Checklist

If you are setting this up on a fresh VPS, follow this sequence:

1. Install Docker and the Compose plugin.
2. Copy the TW4 repository onto the server.
3. Create `.env` from `.env.example`.
4. Set `DB_PASSWORD`, `CADDY_DOMAIN`, and `CADDY_EMAIL` in `.env`.
5. Run `./scripts/bootstrap-production.sh`.
6. Start the stack with `docker compose -f docker-compose.prod.yml up -d --build`.
7. Open the site in a browser and confirm the scorer menu loads.
8. Log in as scorer and admin and run a short smoke test.
9. Share the HTTPS URL with your tester.

If anything fails during step 5 or 6, check the logs with:

```bash
docker compose -f docker-compose.prod.yml logs -f
```

## What Not To Use

1. Shared hosting, because it is usually awkward for Docker, persistent sessions, and database bootstrap.
2. Serverless-style hosting, because TW4 is stateful and database-driven.
3. Container PaaS without a persistence plan, unless you are ready to redesign session and filesystem storage.

## Suggested Next Step

If you want the fastest route to a remote tester, choose a VPS + Docker Compose + HTTPS proxy and keep the deployment close to the current repository structure.

If you want the free route, choose Oracle Cloud Always Free and follow the same stack, bootstrap, and tester checklist.