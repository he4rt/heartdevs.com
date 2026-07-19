<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Identity\User\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Schema $form
 */
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
                    ->placeholder(__('panel-app::feed.composer.placeholder'))
                    ->toolbarButtons([])
                    ->required()
                    ->maxLength(5_000),
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
        /** @var array{content: string, images?: list<string>} $state */
        $state = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        resolve(CreatePost::class)->handle(new CreatePostDTO(
            userId: $user->id,
            content: $state['content'],
            images: $state['images'] ?? [],
        ));

        $this->form->fill();
        $this->dispatch('timeline.post-created');
    }

    #[Computed]
    public function avatarUrl(): ?string
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->getFirstMediaUrl('avatar') ?: null;
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.composer');
    }
}
