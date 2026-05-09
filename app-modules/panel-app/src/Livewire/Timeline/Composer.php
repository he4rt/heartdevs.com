<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use Illuminate\View\View;
use Livewire\Component;

final class Composer extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MarkdownEditor::make('content')
                    ->hiddenLabel()
                    ->placeholder('O que está acontecendo?')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                    ]),
            ])
            ->statePath('data');
    }

    public function post(): void
    {
        $state = $this->form->getState();

        $tenant = Filament::getTenant();

        resolve(CreatePost::class)->handle(new CreatePostDTO(
            userId: auth()->id(),
            tenantId: $tenant->id,
            content: $state['content'],
        ));

        $this->form->fill();
        $this->dispatch('timeline.post-created');
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.composer');
    }
}
