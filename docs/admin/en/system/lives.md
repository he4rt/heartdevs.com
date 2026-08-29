---
title: Lives
icon: heroicon-o-video-camera
order: 1
---

# Lives

The **Lives** resource manages the platform's own live streams: creating a live, wiring it up in OBS, following the audience while it runs, moderating the chat, and closing it out when the broadcast ends.

## Creating a live

From the lives list, use the **Criar live** action and fill in a title and an optional description. Only one live can be current (any status other than "Encerrada") at a time — if you try to create a second one while another is still open, the panel shows a notification and no record is created. Close the current live first.

## Wiring up OBS

Open the live you just created. The **Ingest** section shows the RTMP URL and the stream key:

- Paste the full RTMP URL (`rtmp://localhost:1935/live?user=he4rt&pass=<stream key>`) into OBS's "Server" field, or split it into the server address and the stream key field, matching your OBS setup.
- The stream key is masked by default. Use the eye icon next to it to reveal it, and the copy icon to copy either value to the clipboard.

## Ending a live

Use the **Encerrar live** header action on the live's page. This action requires confirmation and is only available while the live has not already ended — once a live is "Encerrada" its stream key stops being accepted by the ingest server.

## Rotating the stream key

If a stream key leaks or you simply want a fresh one, use **Rotacionar stream key**. This also requires confirmation and is only available before the live ends. The previous key stops working immediately.

## Reading the audience chart

The audience chart on the live's page plots the number of concurrent viewers sampled over time, from oldest to newest. A flat or empty chart usually means the live hasn't started collecting samples yet, or has not received any viewers.

## Moderating the chat

The chat table on the live's page lists every message sent during the live, most recent first, together with its author and timestamp. Use the **Remover** row action to delete an inappropriate message — this requires confirmation and records the moderation event before removing the message.
