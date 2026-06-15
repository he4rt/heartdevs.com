<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Activity\Message\Models\Message;
use He4rt\Moderation\Cases\Actions\SubmitReport;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('provider_message_id')
                    ->label('Provider Message Id'),

                TextInput::make('channel_id')
                    ->label('Channel Id'),

                MarkdownEditor::make('content')
                    ->label('Content')
                    ->required(),

                TextInput::make('obtained_experience')
                    ->label('Obtained Experience')
                    ->required()
                    ->integer(),

                DatePicker::make('sent_at')
                    ->label('Sent Date'),

                Select::make('tenant_id')
                    ->label('Tenant Id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('reactions_count')
                    ->label('Reactions Count')
                    ->required()
                    ->integer(),

                TextInput::make('reactions_total')
                    ->label('Reactions Total')
                    ->required()
                    ->integer(),

                TextInput::make('kind')
                    ->label('Kind'),

                TextInput::make('raw_message_type')
                    ->label('Raw Message Type')
                    ->integer(),

                TextInput::make('source_kind')
                    ->label('Source Kind'),

                Checkbox::make('is_pinned')
                    ->label('Is Pinned'),

                Checkbox::make('mentions_everyone')
                    ->label('Mentions Everyone'),

                TextInput::make('mention_role_count')
                    ->label('Mention Role Count')
                    ->required()
                    ->integer(),

                DatePicker::make('edited_at')
                    ->label('Edited Date'),

                TextInput::make('reply_to_provider_message_id')
                    ->label('Reply To Provider Message Id'),

                TextInput::make('reply_to_message_id')
                    ->label('Reply To Message Id'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->dateTime(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('provider_message_id')
                    ->label('Provider Message Id'),

                TextEntry::make('channel_id')
                    ->label('Channel Id'),

                TextEntry::make('content')
                    ->label('Content'),

                TextEntry::make('obtained_experience')
                    ->label('Obtained Experience'),

                TextEntry::make('sent_at')
                    ->label('Sent Date')
                    ->dateTime(),

                TextEntry::make('tenant.name')
                    ->label('Tenant Id'),

                TextEntry::make('metadata')
                    ->label('Metadata'),

                TextEntry::make('reactions_count')
                    ->label('Reactions Count'),

                TextEntry::make('reactions_total')
                    ->label('Reactions Total'),

                TextEntry::make('kind')
                    ->label('Kind'),

                TextEntry::make('raw_message_type')
                    ->label('Raw Message Type'),

                TextEntry::make('source_kind')
                    ->label('Source Kind'),

                TextEntry::make('is_pinned')
                    ->label('Is Pinned'),

                TextEntry::make('mentions_everyone')
                    ->label('Mentions Everyone'),

                TextEntry::make('mention_role_count')
                    ->label('Mention Role Count'),

                TextEntry::make('edited_at')
                    ->label('Edited Date')
                    ->dateTime(),

                TextEntry::make('reply_to_provider_message_id')
                    ->label('Reply To Provider Message Id'),

                TextEntry::make('reply_to_message_id')
                    ->label('Reply To Message Id'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('provider_message_id')
                    ->label('Provider Message Id'),

                TextColumn::make('channel_id')
                    ->label('Channel Id'),

                TextColumn::make('obtained_experience')
                    ->label('Obtained Experience'),

                TextColumn::make('sent_at')
                    ->label('Sent Date')
                    ->date(),

                TextColumn::make('tenant.name')
                    ->label('Tenant Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('metadata')
                    ->label('Metadata'),

                TextColumn::make('reactions_count')
                    ->label('Reactions Count'),

                TextColumn::make('reactions_total')
                    ->label('Reactions Total'),

                TextColumn::make('kind')
                    ->label('Kind'),

                TextColumn::make('raw_message_type')
                    ->label('Raw Message Type'),

                TextColumn::make('source_kind')
                    ->label('Source Kind'),

                TextColumn::make('is_pinned')
                    ->label('Is Pinned'),

                TextColumn::make('mentions_everyone')
                    ->label('Mentions Everyone'),

                TextColumn::make('mention_role_count')
                    ->label('Mention Role Count'),

                TextColumn::make('edited_at')
                    ->label('Edited Date')
                    ->date(),

                TextColumn::make('reply_to_provider_message_id')
                    ->label('Reply To Provider Message Id'),

                TextColumn::make('reply_to_message_id')
                    ->label('Reply To Message Id'),
            ])
            ->filters([

            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('report')
                    ->label('Report')
                    ->icon(Heroicon::OutlinedFlag)
                    ->color('danger')
                    ->schema([
                        Select::make('reason')
                            ->label('Violation Type')
                            ->options(ViolationType::class)
                            ->required(),
                        Textarea::make('details')
                            ->label('Details')
                            ->rows(3),
                    ])
                    ->action(function (Message $record, array $data): void {
                        $identity = $this->getOwnerRecord();

                        $contentDTO = ModerationContentDTO::fromPlatform(Platform::Discord, [
                            'content_id' => $record->provider_message_id ?? $record->id,
                            'content_type' => 'message',
                            'author_external_id' => $identity->external_account_id ?? '',
                            'text' => $record->content,
                            'media_urls' => [],
                            'tenant_id' => (string) $record->tenant_id,
                            'metadata' => [
                                'channel_id' => $record->channel_id,
                            ],
                        ]);

                        /** @var ViolationType $reason */
                        $reason = $data['reason'];

                        /** @var string|null $details */
                        $details = $data['details'] ?? null;

                        resolve(SubmitReport::class)->handle(
                            reporter: auth()->user(),
                            contentDTO: $contentDTO,
                            reason: $reason,
                            details: $details,
                            platform: Platform::Web,
                        );
                    })
                    ->successNotificationTitle('Report submitted successfully.'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
