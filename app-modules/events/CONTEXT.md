# Events — Bounded Context

## Purpose

Manages event participation lifecycle: enrollment, check-in, attendance tracking, and XP reward dispatching. Covers in-person meetups, workshops, and multi-day conferences.

## Glossary

| Term                       | Definition                                                                                                                                                                                                                  | Not to be confused with                                                                                 |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Event**                  | A scheduled gathering (meetup, workshop, or conference) owned by a tenant. Defined by type, date range, location, and an enrollment policy.                                                                                 | "Event" as in Laravel Event (domain event) — use "domain event" for those.                              |
| **Enrollment**             | A single record representing one user's relationship with one event. Progresses through a strict state machine from entry to terminal state. One enrollment per (user, event).                                              | "Registration" — we don't use this term.                                                                |
| **Enrollment Policy**      | A 1:1 configuration record attached to an event. Defines enrollment method, capacity, check-in method, waitlist behavior, cancellation deadline, XP rewards, and application form schema.                                   | "Event settings" — policy specifically governs participation rules.                                     |
| **Enrollment Method**      | How a user enters an event. One of: `rsvp` (1-click), `rsvp_checkin` (1-click + mandatory presence verification), `application` (form submission + organizer approval).                                                     |                                                                                                         |
| **Check-in**               | Verification of physical/virtual presence at an event. One record per (enrollment, date). Methods: `manual`, `numeric_code`, `qr_code`.                                                                                     | "Enrollment" — check-in proves presence, enrollment proves intent.                                      |
| **Numeric Code**           | A short-lived code announced by the organizer (projected on screen or spoken in stream). Bound to a specific event date. Has expiration window and optional max uses. Also used by Discord/Twitch bots as check-in trigger. |                                                                                                         |
| **QR Token**               | A unique token generated per enrollment. Encoded in a QR code on the participant's badge/screen. Reusable across event days — each scan creates a new check-in record for that day.                                         |                                                                                                         |
| **Waitlist**               | Ordered queue of enrollments when event capacity is full. FIFO promotion when a confirmed participant cancels.                                                                                                              |                                                                                                         |
| **Attendance Requirement** | Policy rule defining how many days of check-in are needed to achieve `attended` status. Values: `all_days`, `any_day`, `minimum_days(N)`.                                                                                   |                                                                                                         |
| **Transition**             | An auditable state change on an enrollment. Every transition is recorded with actor, timestamp, and reason. Written by application code (Actions), never by database triggers.                                              |                                                                                                         |
| **No-show**                | Terminal state for a participant who confirmed but never checked in. Assigned automatically by a scheduled job after the event ends. No systemic consequences in MVP (future: may affect eligibility).                      |                                                                                                         |
| **Application**            | An enrollment method where the participant submits a dynamic form (JSONB schema defined in policy) and waits for organizer approval. Results in `pending → confirmed` or `pending → rejected`.                              | "Submission" (CFP) — application is for participation, submission is for presenting (out of MVP scope). |

## State Machine — Enrollment

```
[entry]
  ├─ application → pending
  │                 ├─ approve()  → confirmed
  │                 ├─ reject()   → rejected (TERMINAL)
  │                 └─ cancel()   → cancelled (TERMINAL)
  │
  ├─ rsvp/rsvp_checkin + capacity available → confirmed
  └─ rsvp/rsvp_checkin + full → waitlisted
                                  ├─ slot_opens() → confirmed
                                  └─ cancel()     → cancelled (TERMINAL)

confirmed
  ├─ check_in()           → checked_in
  ├─ cancel(pre-deadline) → cancelled (TERMINAL)
  └─ [job] event_ended   → no_show (TERMINAL)

checked_in
  └─ [job] event_ended + attendance_requirement met → attended (TERMINAL · SUCCESS)
```

**States:** `pending`, `confirmed`, `waitlisted`, `checked_in`, `attended`, `cancelled`, `rejected`, `no_show`

**Terminal states:** `attended` (success), `cancelled`, `rejected`, `no_show`

## Actors

| Actor           | Panel            | Capabilities                                                                                         |
| --------------- | ---------------- | ---------------------------------------------------------------------------------------------------- |
| **Organizer**   | Admin (`/admin`) | Creates events, configures policies, approves/rejects applications, manual check-in, status override |
| **Participant** | App (`/app`)     | Enrolls (RSVP/application), checks in (code/QR), cancels, views own events                           |

## Module Boundaries

- **Events → Gamification**: Events dispatches domain events (`EnrollmentConfirmed`, `ParticipantCheckedIn`, `ParticipantAttended`). Gamification listens and awards XP. Events does not know how XP works.
- **Bot Discord → Events**: Bot dispatches domain events (e.g., `CheckInRequested`). Events module listens and processes. Bot is transport, Events owns the rules.
- **Events → Identity**: Events reads User and Tenant models. No writes to Identity.

## Out of Scope (MVP)

- Networking between participants
- Referral / invite links
- Magic link and geolocation check-in
- Sponsors association
- Timeline / activity feed (listeners ready, no consumer yet)
- Call for Papers / Submissions (CFP)
- Agenda / schedule display
- Paid events / payment integration
- No-show penalties
