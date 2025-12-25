<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

class SubmissionApprovedWebhookDTO
{
    public function __construct(
        public int $day,
        public string $text,
        public string $tweetUrl,
        public string $userName,
    ) {}

    public static function make(array $data): self
    {

        return new self(
            day: $data['day'],
            text: $data['text'],
            tweetUrl: $data['tweet_url'],
            userName: $data['user_name'],
        );

    }
}
