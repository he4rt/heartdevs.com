<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;

class CodeCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'code';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Code Command command.';

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the command.
     */
    public function handle(Interaction $interaction): void
    {
        $this
            ->message()
            ->body("
Format your code (change golang for your language):
\`\`\`golang
          PASTE YOUR CODE HERE
\`\`\`
```golang\nfmt.Println('Pride was here')\n```
            ")
            ->send($interaction->channel_id);
    }
}
