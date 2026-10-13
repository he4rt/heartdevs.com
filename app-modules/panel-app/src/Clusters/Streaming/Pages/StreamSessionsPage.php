<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\StreamDuration;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Session\Queries\StreamSessionTotals;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Database\Eloquent\Builder;

class StreamSessionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = StreamingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Lives';

    protected static ?string $title = 'Lives';

    protected static ?string $slug = 'lives';

    protected static ?int $navigationSort = 4;

    protected string $view = 'panel-app::pages.streaming.lives';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('use-streamer-tools') ?? false;
    }

    /**
     * @return array<int, string>
     */
    public static function getNavigationItemActiveRoutePattern(): array
    {
        return [static::getRouteName(), StreamSessionPage::getRouteName()];
    }

    public function mount(EnsureStreamer $ensureStreamer): void
    {
        $ensureStreamer->handle($this->currentUser());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => resolve(StreamSessionTotals::class)->apply(
                StreamSession::query()->whereBelongsTo($this->currentStreamer()),
            ))
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->placeholder('Sem título')
                    ->description(fn (StreamSession $session): ?string => $session->category)
                    ->limit(48)
                    ->wrap(),
                TextColumn::make('duration')
                    ->label('Duração')
                    ->state(fn (StreamSession $session): string => $session->isOpen() ? 'ao vivo' : StreamDuration::between($session->started_at, $session->ended_at ?? now()))
                    ->badge(fn (StreamSession $session): bool => $session->isOpen())
                    ->color(fn (StreamSession $session): ?string => $session->isOpen() ? 'danger' : null),
                $this->totalColumn('follows_total', 'Follows'),
                $this->totalColumn('subs_total', 'Subs'),
                $this->totalColumn('bits_total', 'Bits'),
                $this->totalColumn('raids_total', 'Raids'),
                $this->totalColumn('messages_total', 'Mensagens'),
                $this->totalColumn('chatters_total', 'Chatters'),
            ])
            ->recordUrl(fn (StreamSession $session): string => StreamSessionPage::getUrl(['session' => $session->id]))
            ->emptyStateIcon(Heroicon::OutlinedSignal)
            ->emptyStateHeading('Nenhuma live ainda')
            ->emptyStateDescription('As lives aparecem aqui quando a Twitch avisa que você entrou ao vivo.');
    }

    /**
     * Aggregates are not sortable: the panel paginates by cursor, and an aggregate sort breaks page 2.
     */
    private function totalColumn(string $attribute, string $label): TextColumn
    {
        return TextColumn::make($attribute)
            ->label($label)
            ->alignEnd()
            ->formatStateUsing(fn (mixed $state, StreamSession $session): string => SessionTotals::of($session)->hasNoData()
                ? '—'
                : number_format(is_numeric($state) ? (int) $state : 0, thousands_separator: '.'));
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function currentStreamer(): Streamer
    {
        return Streamer::query()->whereBelongsTo($this->currentUser())->sole();
    }
}
