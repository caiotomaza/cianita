#!/usr/bin/env python3
"""Smoke de laboratório Compose com contas e notas fictícias.

Exige CIANITA_SMOKE_LAB=1; não executar sobre volumes com dados importantes.
Não constitui teste de backup, restauração, E2E de navegador ou release.
"""

import http.cookiejar
import json
import os
import pty
import select
import secrets
import subprocess
import time
from html.parser import HTMLParser
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import HTTPCookieProcessor, Request, build_opener


class TokenParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.token = ""

    def handle_starttag(self, tag, attrs):
        attributes = dict(attrs)
        if tag == "input" and attributes.get("name") == "_token":
            self.token = attributes.get("value", "")
        if tag == "meta" and attributes.get("name") == "csrf-token":
            self.token = attributes.get("content", "")


def assert_status(actual, expected, label):
    if actual != expected:
        raise RuntimeError(f"{label}: HTTP {actual}; esperado {expected}")


def command(*args):
    subprocess.run(args, check=True, stdout=subprocess.DEVNULL,
                   stderr=subprocess.DEVNULL, timeout=60)


def provision(email, name, password):
    """Usa pseudoterminal para o CLI não receber a senha em argv ou logs."""
    pid, master = pty.fork()
    if pid == 0:
        os.execvp("docker", ["docker", "compose", "exec", "app",
                             "php", "artisan", "cianita:user:create", email])
    prompts = [
        ("Nome", name),
        ("Senha (minimo 12 caracteres)", password),
        ("Confirme a senha", password),
    ]
    sent, output = 0, ""
    deadline = time.monotonic() + 45
    try:
        while time.monotonic() < deadline:
            ready, _, _ = select.select([master], [], [], 0.5)
            if ready:
                try:
                    chunk = os.read(master, 4096)
                except OSError:
                    break
                if not chunk:
                    break
                output += chunk.decode("utf-8", errors="replace")
                if sent < len(prompts) and prompts[sent][0] in output:
                    os.write(master, (prompts[sent][1] + "\n").encode())
                    sent += 1
                    output = ""
            exited, status = os.waitpid(pid, os.WNOHANG)
            if exited == pid:
                if os.waitstatus_to_exitcode(status) != 0 or sent != 3:
                    raise RuntimeError("Falha ao provisionar usuário fictício")
                return
        exited, status = os.waitpid(pid, os.WNOHANG)
        if exited != pid:
            os.kill(pid, 15)
            os.waitpid(pid, 0)
            raise RuntimeError("Timeout do CLI de provisionamento")
        if os.waitstatus_to_exitcode(status) != 0 or sent != 3:
            raise RuntimeError("Falha ao provisionar usuário fictício")
    finally:
        os.close(master)


class Session:
    def __init__(self, base):
        self.base = base
        self.opener = build_opener(HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = ""

    def request(self, method, path, payload=None, csrf=True, form=False):
        headers = {"Accept": "text/html" if form else "application/json"}
        if csrf and self.token:
            headers["X-CSRF-TOKEN"] = self.token
        data = None
        if payload is not None:
            if form:
                data = urlencode(payload).encode()
                headers["Content-Type"] = "application/x-www-form-urlencoded"
            else:
                data = json.dumps(payload).encode()
                headers["Content-Type"] = "application/json"
        request = Request(self.base + path, data=data, headers=headers, method=method)
        try:
            response = self.opener.open(request, timeout=10)
        except HTTPError as error:
            response = error
        with response:
            body = response.read().decode("utf-8", errors="replace")
            return response.status, body, response.geturl()

    def login(self, email, password):
        status, html, _ = self.request("GET", "/login", form=True)
        assert_status(status, 200, "GET /login")
        parser = TokenParser()
        parser.feed(html)
        if not parser.token:
            raise RuntimeError("Formulário sem token CSRF")
        self.token = parser.token
        status, _, _ = self.request("POST", "/login",
                                    {"email": email, "password": password},
                                    csrf=False, form=True)
        assert_status(status, 419, "Login sem CSRF")
        status, html, url = self.request("POST", "/login",
                                         {"_token": self.token, "email": email, "password": password},
                                         form=True)
        assert_status(status, 200, "Login autenticado")
        if url not in (self.base + "/", self.base.replace("127.0.0.1", "localhost") + "/"):
            raise RuntimeError("Login não retornou à aplicação local")
        parser = TokenParser()
        parser.feed(html)
        if not parser.token:
            raise RuntimeError("Página autenticada sem token CSRF")
        self.token = parser.token
        return self

    def api(self, method, path, payload=None, expected=200, csrf=True):
        status, body, _ = self.request(method, path, payload, csrf=csrf)
        assert_status(status, expected, f"{method} {path}")
        if status >= 400:
            return None
        return json.loads(body)["data"] if body else None


def await_http(base):
    deadline = time.monotonic() + 80
    while time.monotonic() < deadline:
        try:
            status, _, _ = Session(base).request("GET", "/up")
            if status == 200:
                return
        except (URLError, OSError):
            pass
        time.sleep(2)
    raise RuntimeError("/up indisponível após reiniciar serviços")


def main():
    if os.environ.get("CIANITA_SMOKE_LAB") != "1":
        raise RuntimeError("Exige CIANITA_SMOKE_LAB=1 em ambiente descartável")
    port = int(os.environ.get("CIANITA_PORT", "8080"))
    if not 1 <= port <= 65535:
        raise RuntimeError("Porta inválida")
    base = f"http://127.0.0.1:{port}"
    for service in ("db", "redis"):
        container = subprocess.check_output(
            ["docker", "compose", "ps", "-q", service], text=True, timeout=15
        ).strip()
        if not container:
            raise RuntimeError(f"Contêiner {service} ausente")
        raw = subprocess.check_output(
            ["docker", "inspect", "--format", "{{json .NetworkSettings.Ports}}", container],
            text=True, timeout=15,
        )
        bindings = json.loads(raw)
        if any(host_bindings for host_bindings in bindings.values()):
            raise RuntimeError(f"{service} tem porta publicada no host")
    command("docker", "compose", "exec", "-T", "db", "sh", "-c",
            'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"')
    command("docker", "compose", "exec", "-T", "redis", "redis-cli", "ping")
    await_http(base)
    Session(base).api("GET", "/api/vaults", expected=401)

    tag = secrets.token_hex(5)
    email_a, email_b = (f"smoke-a-{tag}@example.invalid",
                        f"smoke-b-{tag}@example.invalid")
    password_a, password_b = secrets.token_urlsafe(24), secrets.token_urlsafe(24)
    provision(email_a, "Usuário artificial A", password_a)
    provision(email_b, "Usuário artificial B", password_b)
    owner, outsider = (Session(base).login(email_a, password_a),
                       Session(base).login(email_b, password_b))
    owner.api("POST", "/api/vaults", {"name": "Sem CSRF"}, expected=419, csrf=False)

    vault = owner.api("POST", "/api/vaults", {"name": "Cofre artificial"}, expected=201)
    vid = vault["id"]
    note = owner.api("POST", f"/api/vaults/{vid}/notes",
                     {"title": "Nota artificial", "body": "Conteúdo artificial"}, expected=201)
    nid = note["id"]
    path = f"/api/vaults/{vid}/notes/{nid}"
    if owner.api("GET", path)["body"] != "Conteúdo artificial":
        raise RuntimeError("Leitura de Markdown divergiu")
    outsider.api("GET", f"/api/vaults/{vid}/notes", expected=403)
    outsider.api("GET", path, expected=403)
    outsider.api("PUT", path, {"title": "Não", "body": "Não", "version": 1}, expected=403)
    owner.api("PUT", path, {"title": "Atualizada", "body": "Segunda versão", "version": 1})
    owner.api("PUT", path, {"title": "Versão antiga", "body": "Indevida", "version": 1},
              expected=409)

    command("docker", "compose", "restart", "db", "redis", "app")
    await_http(base)
    deadline = time.monotonic() + 50
    while True:
        try:
            if owner.api("GET", path)["body"] != "Segunda versão":
                raise RuntimeError("Markdown divergiu após restart")
            break
        except (URLError, OSError, RuntimeError):
            if time.monotonic() >= deadline:
                raise RuntimeError("Persistência PostgreSQL/Markdown não confirmada após restart")
            time.sleep(2)
    owner.api("GET", f"/storage/app/private/vaults/{vid}/notes/{nid}.md", expected=404)
    owner.api("DELETE", path)
    owner.api("GET", path, expected=404)
    print("APROVADO: CLI, login, CSRF, CRUD, 401/403/409, restart, volumes e portas internas")


if __name__ == "__main__":
    main()
