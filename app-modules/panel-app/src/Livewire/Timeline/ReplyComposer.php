<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Livewire\Timeline;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use He4rt\Activity\Timeline\Actions\CreateReply;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ReplyComposer extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    #[Locked]
    public string $timelineId;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(string $timelineId): void
    {
        $this->timelineId = $timelineId;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MarkdownEditor::make('content')
                    ->hiddenLabel()
                    ->placeholder('Escreva uma resposta...')
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

    public function reply(): void
    {
        $state = $this->form->getState();

        resolve(CreateReply::class)->handle(new CreateReplyDTO(
            userId: auth()->id(),
            parentTimelineId: $this->timelineId,
            content: $state['content'],
            images: $state['images'] ?? [],
        ));

        $this->form->fill();
        $this->dispatch('timeline.reply-created');
    }

    public function render(): View
    {
        return view('panel-app::livewire.timeline.reply-composer');
    }
}
