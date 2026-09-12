#!/usr/bin/env python3
"""End-to-end smoke tests for EpicEvents using only the Python standard library."""

from __future__ import annotations

import http.cookiejar
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import date, timedelta


BASE_URL = os.environ.get("APP_URL", "http://127.0.0.1:8080").rstrip("/")
PASSWORD = "Integration123"
RUN_ID = str(int(time.time()))


class Browser:
    def __init__(self) -> None:
        jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(jar)
        )

    def request(self, path: str, data: dict[str, str] | None = None):
        payload = urllib.parse.urlencode(data).encode() if data is not None else None
        request = urllib.request.Request(BASE_URL + path, data=payload)
        try:
            response = self.opener.open(request, timeout=15)
            return response.status, response.read().decode(), response.headers
        except urllib.error.HTTPError as error:
            return error.code, error.read().decode(), error.headers


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def csrf(body: str) -> str:
    match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', body)
    require(match is not None, "CSRF token was not present")
    return match.group(1)


def mysql_scalar(sql: str) -> str:
    environment = os.environ.copy()
    environment["MYSQL_PWD"] = os.environ.get("DB_PASS", "root")
    result = subprocess.run(
        [
            "mysql",
            "--batch",
            "--skip-column-names",
            "--host",
            os.environ.get("DB_HOST", "127.0.0.1"),
            "--port",
            os.environ.get("DB_PORT", "3306"),
            "--user",
            os.environ.get("DB_USER", "root"),
            os.environ.get("DB_NAME", "epicevents"),
            "--execute",
            sql,
        ],
        check=True,
        capture_output=True,
        text=True,
        env=environment,
    )
    return result.stdout.strip()


def register(browser: Browser, email: str, name: str) -> str:
    status, body, _ = browser.request("/register.php")
    require(status == 200, "Registration page did not load")
    token = csrf(body)
    status, body, _ = browser.request(
        "/register.php",
        {
            "csrf_token": token,
            "name": name,
            "email": email,
            "password": PASSWORD,
            "password_confirm": PASSWORD,
            "privacy": "1",
        },
    )
    require(status == 200 and "My bookings" in body, "Valid registration failed")
    return body


def main() -> None:
    member_email = f"member-{RUN_ID}@example.test"
    admin_email = f"admin-{RUN_ID}@example.test"

    public = Browser()
    status, home, headers = public.request("/")
    require(status == 200 and "Featured experiences" in home, "Home page failed")
    require(headers.get("X-Content-Type-Options") == "nosniff", "Security headers missing")
    status, events, _ = public.request("/events.php?q=Harbour")
    require(status == 200 and "Harbour Light Walk" in events, "Dynamic event search failed")

    status, invalid_form, _ = public.request("/register.php")
    token = csrf(invalid_form)
    status, invalid_form, _ = public.request(
        "/register.php",
        {
            "csrf_token": token,
            "name": "",
            "email": "invalid",
            "password": "short",
            "password_confirm": "different",
        },
    )
    require(status == 200 and "Please correct the following" in invalid_form, "Server validation failed")

    member = Browser()
    dashboard = register(member, member_email, "Integration Member")
    status, denied, _ = member.request("/admin/index.php")
    require(status == 403 and "Access denied" in denied, "Member role was not denied admin access")

    status, booking_form, _ = member.request("/book.php?event_id=1")
    require(status == 200 and "Reserve tickets" in booking_form, "Booking page failed")
    status, dashboard, _ = member.request(
        "/book.php",
        {
            "csrf_token": csrf(booking_form),
            "event_id": "1",
            "quantity": "2",
            "accessibility_notes": "Step-free access requested",
        },
    )
    require(status == 200 and "Booking confirmed" in dashboard, "Booking creation failed")
    require("$48.00" in dashboard, "Booking total was incorrect")

    status, csrf_error, _ = member.request(
        "/dashboard.php",
        {"csrf_token": "0" * 64, "action": "cancel", "booking_id": "1"},
    )
    require(status == 419 and "session expired" in csrf_error.lower(), "Forged CSRF token was accepted")

    status, dashboard, _ = member.request("/dashboard.php")
    booking_match = re.search(r'name="booking_id" value="(\d+)"', dashboard)
    require(booking_match is not None, "Created booking was not shown")
    status, dashboard, _ = member.request(
        "/dashboard.php",
        {
            "csrf_token": csrf(dashboard),
            "action": "cancel",
            "booking_id": booking_match.group(1),
        },
    )
    require(status == 200 and "has been cancelled" in dashboard, "Booking cancellation failed")

    enquiry_subject = f"Integration enquiry {RUN_ID}"
    status, contact, _ = public.request("/contact.php")
    status, contact, _ = public.request(
        "/contact.php",
        {
            "csrf_token": csrf(contact),
            "name": "Integration Visitor",
            "email": "visitor@example.test",
            "subject": enquiry_subject,
            "message": "Please provide accessible arrival information for this event.",
            "website": "",
        },
    )
    require(status == 200 and "enquiry has been received" in contact, "Enquiry creation failed")

    admin = Browser()
    admin_dashboard = register(admin, admin_email, "Integration Administrator")
    mysql_scalar(
        "UPDATE users SET role='admin' "
        f"WHERE email='{admin_email.replace("'", "''")}';"
    )

    status, _, _ = admin.request(
        "/logout.php",
        {"csrf_token": csrf(admin_dashboard)},
    )
    require(status == 200, "Logout failed")
    status, login, _ = admin.request("/login.php")
    status, admin_dashboard, _ = admin.request(
        "/login.php",
        {
            "csrf_token": csrf(login),
            "email": admin_email,
            "password": PASSWORD,
        },
    )
    require(status == 200, "Administrator login failed")
    status, admin_page, _ = admin.request("/admin/index.php")
    require(status == 200 and "Event operations" in admin_page, "Administrator dashboard failed")
    require(enquiry_subject in admin_page, "New enquiry was not visible to the administrator")

    event_title = f"Integration Draft {RUN_ID}"
    future_date = (date.today() + timedelta(days=120)).isoformat()
    status, event_form, _ = admin.request("/admin/event_form.php")
    status, admin_page, _ = admin.request(
        "/admin/event_form.php",
        {
            "csrf_token": csrf(event_form),
            "title": event_title,
            "category": "Testing",
            "event_date": future_date,
            "start_time": "10:30",
            "location": "Sydney Test Venue",
            "capacity": "40",
            "price": "12.50",
            "image_url": "https://images.unsplash.com/photo-1492684223066-81342ee5ff30",
            "description": "A complete integration-test event used to verify administrator CRUD behaviour.",
            "status": "draft",
        },
    )
    require(status == 200 and event_title in admin_page, "Administrator event creation failed")

    event_id = mysql_scalar(
        "SELECT id FROM events "
        f"WHERE title='{event_title.replace("'", "''")}' LIMIT 1;"
    )
    require(event_id.isdigit(), "Created event was not stored")
    status, admin_page, _ = admin.request(
        "/admin/event_delete.php",
        {"csrf_token": csrf(admin_page), "id": event_id},
    )
    require(status == 200 and "permanently deleted" in admin_page, "Eligible draft deletion failed")
    require(mysql_scalar(f"SELECT COUNT(*) FROM events WHERE id={event_id};") == "0", "Draft remained in database")

    stored_hash = mysql_scalar(
        "SELECT password_hash FROM users "
        f"WHERE email='{member_email.replace("'", "''")}';"
    )
    require(stored_hash != PASSWORD and len(stored_hash) >= 55, "Password was not securely hashed")

    print("Integration audit passed: public, member, administrator, CRUD, validation and security flows.")


if __name__ == "__main__":
    try:
        main()
    except Exception as exception:
        print(f"Integration audit failed: {exception}", file=sys.stderr)
        raise
