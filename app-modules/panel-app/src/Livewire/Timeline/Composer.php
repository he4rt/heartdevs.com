<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Identity\User\Models\User;
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
                    ->toolbarButtons([])
                    ->required()
                    ->maxLength(5000),
                FileUpload::make('images')
                    ->hiddenLabel()
                    ->image()
                    ->multiple()
                    ->maxFiles(4)
                    ->disk('public')
                    ->directory('timeline-uploads')
                    ->panelLayout('compact')
                    ->extraFieldWrapperAttributes([
                        'x-show' => 'showUpload',
                        'x-cloak' => true,
                        'x-transition' => true,
                    ]),
            ])
            ->statePath('data');
    }

    public function post(): void
    {
        $state = $this->form->getState();

        $tenant = Filament::getTenant();

        /** @var User $user */
        $user = auth()->user();

        resolve(CreatePost::class)->handle(new CreatePostDTO(
            userId: $user->id,
            tenantId: $tenant->id,
            content: $state['content'],
            images: $state['images'] ?? [],
        ));

        $this->form->fill();
        $this->dispatch('timeline.post-created');
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.composer');
    }
}
