<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Pages\Dashboard as BaseDashboard;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;

class TimelinePage extends BaseDashboard
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Timeline';

    protected string $view = 'panel-app::dashboard';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPost')
                ->label('Novo Post')
                ->icon('heroicon-o-plus')
                ->modalHeading('Criar post')
                ->modalWidth('lg')
                ->schema([
                    MarkdownEditor::make('content')
                        ->label('Conteúdo')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'link',
                            'bulletList',
                            'orderedList',
                        ])
                        ->columnSpanFull(),
                    SpatieMediaLibraryFileUpload::make('images')
                        ->label('Imagens')
                        ->collection('images')
                        ->multiple()
                        ->image()
                        ->maxFiles(4)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    resolve(CreatePost::class)->handle(new CreatePostDTO(
                        userId: auth()->id(),
                        tenantId: $tenant->id,
                        content: $data['content'],
                        images: $data['images'] ?? [],
                    ));

                    $this->dispatch('timeline.post-created');
                })
                ->successNotificationTitle('Post criado!'),
        ];
    }
}
