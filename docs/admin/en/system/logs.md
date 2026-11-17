---
title: Logs
order: 6
---

# Logs

**Logs** shows what the system did: one event for each request, job, command and scheduled task. Open it from
**System → Logs**. The **Activity log** shows who changed which record. Logs shows how the work went.

## What an event holds

Every event has the route or the job, the user, the team, how long it took, how many queries it ran, the feature
flags it read, and its outcome:

| Outcome      | Meaning                                                                        |
| ------------ | ------------------------------------------------------------------------------ |
| Success      | The work finished without an error.                                            |
| Client error | The request was refused with a 4xx status: not found, validation, forbidden.   |
| Error        | The work failed: a 5xx status, an exception, or a command that did not exit 0. |

When the work failed, the event also has the exception and the lines of code where it happened.

An event never holds a password, a token, an email address, an IP address or the body of a request. A route shows
as its template (`reset-password/{token}`), never with the values of the URL.

## Why some requests are missing

The app does not keep every event. It always keeps:

- every event that failed,
- every event that took more than one second,
- every command, scheduled task and MCP tool call,
- every event of a team or user that has a feature flag in a rollout,
- every event of a user with the **Keep every log** flag.

It keeps a sample of the rest (5% by default). The **Kept** badge on an event says why it is there.

An **MCP tool** event is a call that a coding agent made to the development MCP server. The server runs only on a
developer machine, so these events never show in production.

## Debug a problem that a user reports

1. Open **System → Feature flags** and open the gate of **Keep every log**.
2. Click the arrow of the flag, find the user, and turn on their switch.
3. Ask the user to do it again. Every request of that user now shows in **Logs**.
4. Turn the switch off when you are done.

## Find an event

Choose the day, then filter by kind, by outcome, by a minimum duration, or search for a request ID, a route, a job or
a command. Click the arrow at the end of a row to see the event.

The page of an event lists the other events with the same request ID (the jobs that a request dispatched, or the
request that dispatched a job) and the **Activity log** entries of that request.

A filter that matches little stops after part of the file. Click **Keep searching** to read further.

## How long events stay

The app keeps the events of the last 14 days. Older files are deleted every day.

Only super admins can open this screen.
