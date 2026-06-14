<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Markdown;

use He4rt\Docs\CommonMark\Markdown\GithubFlavoredMarkdownExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;

/**
 * GitHub-flavored Markdown converter for the discovery portal, with YAML
 * front-matter support. Kept separate from the legacy converter so enabling
 * front-matter never alters the existing docs/Scramble rendering.
 */
final class DiscoveryMarkdownConverter extends MarkdownConverter
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new FrontMatterExtension());

        parent::__construct($environment);
    }
}
