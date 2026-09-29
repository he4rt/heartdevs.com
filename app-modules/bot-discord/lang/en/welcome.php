<?php

declare(strict_types=1);

return [
    'dm' => [
        'description' => "Good to have you here, **:username**! 💜\n\nHe4rt is one of the largest developer communities in Brazil — a place to learn together, share ideas, join events and grow with people who love code. 💻",
    ],
    'fallback' => [
        'description' => "I tried to welcome you in your DM, but it looks closed 👀\n\nNo problem — you can start right here!",
        'body' => ':mention 👋',
    ],
    'embed' => [
        'title' => 'Welcome to He4rt! 💜',
        'cta_title' => '🙋 Start with an introduction',
        'cta' => 'Tap **Introduce me** below (it takes you to the right channel) and send `/apresentar`. It takes less than a minute — name, nickname and a few words about you. That is how the community meets you and how you unlock the rest of the server. 🚀',
        'footer' => ':year © He4rt Developers',
    ],
    'buttons' => [
        'present' => 'Introduce me',
        'portal' => 'Portal',
        'socials' => 'Our socials',
    ],
    'announcement' => [
        'title' => 'A new member arrived',
        'message' => 'Welcome, :username!',
        'body' => ':mention just arrived. To start, use the `/apresentar` command and tell the community a bit about you.',
    ],
    'profile_failure' => [
        'title' => 'New member',
        'message' => 'Welcome!',
        'body' => ':mention joined the server, but there was a problem starting their profile. If something does not work, talk to the moderation team.',
    ],
];
