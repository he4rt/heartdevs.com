<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Seeders;

use Carbon\CarbonInterface;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Seeds a matrix of timeline posts that exercises every meaningful case the
 * mobile/web feed renders: plain posts, pinned posts, posts with replies
 * (flat under the root, per domain rule), and posts with reactions.
 *
 * Re-runnable: posts are keyed by their exact content, skipped if already seeded.
 */
final class TimelineSeeder extends Seeder
{
    /** @var int Size of the demo author pool shared across posts. */
    private const int AUTHOR_POOL = 15;

    public function run(): void
    {
        $authors = $this->resolveAuthors();

        foreach ($this->matrix() as $spec) {
            $exists = PostEntry::query()->where('content', $spec['content'])->exists();

            if ($exists) {
                continue;
            }

            $author = $authors->random();
            $createdAt = Date::now()->subHours($spec['hoursAgo']);

            $post = $this->createPost($author, $spec['content'], $createdAt, $spec['pinned'] ?? false);

            $this->seedReplies($post, $spec['replies'] ?? [], $authors, $createdAt);
            $this->seedReactions($post, $spec['reactions'] ?? []);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matrix(): array
    {
        return [
            [
                'content' => "Refatoramos o pool de conexões do Postgres no gateway da API do heartdevs.com — P99 caiu de 340ms pra 90ms.\n\n```php\n'pool' => ['min' => 5, 'max' => 50],\n```\n\n#backend #postgres #performance",
                'hoursAgo' => 2,
                'pinned' => true,
                'reactions' => ['🔥' => 18, '🚀' => 9, '👏' => 4],
                'replies' => [
                    'Muito bom! Vocês mediram o impacto em p50 também ou só p99?',
                    'Isso bate com o que vimos aqui na empresa também, pool pequeno demais é cilada.',
                ],
            ],
            [
                'content' => 'Turma de mentoria de Setembro está com vagas abertas! Se você tá buscando o primeiro emprego em tech, dá uma olhada no canal #mentoria do Discord.',
                'hoursAgo' => 5,
                'reactions' => ['❤️' => 12, '🙌' => 3],
                'replies' => [
                    'Já me inscrevi, bora!',
                    'Alguém sabe se tem vaga pra quem já tá no mercado mas quer trocar de área (dados -> backend)?',
                    'Sim! A mentoria não é só pra quem tá começando do zero.',
                ],
            ],
            [
                'content' => "Como fomos de 0 a 9 mil devs na comunidade em menos de 2 anos — thread com o que funcionou (e o que não funcionou).\n\n1/ Conteúdo consistente > conteúdo perfeito",
                'hoursAgo' => 22,
                'reactions' => ['🔥' => 31, '💯' => 15, '🙌' => 7, '👀' => 2],
            ],
            [
                'content' => 'Alguém mais tá tendo problema pra atualizar pro PHP 8.5 por causa de uma dependência que ainda não suporta? Qual pacote tá travando vocês?',
                'hoursAgo' => 30,
                'replies' => [
                    'Aqui foi o phpstan/phpstan-mockery, mas já saiu fix.',
                    'league/flysystem em versão antiga também deu dor de cabeça.',
                ],
            ],
            [
                'content' => "Live hoje às 19h: PHP 8.5 — o que há de novo. Vou mostrar os readonly properties, pipe operator e o novo URI parser na prática.\n\n📺 twitch.tv/he4rtdevs",
                'hoursAgo' => 48,
                'reactions' => ['📺' => 6, '🔥' => 4],
            ],
            [
                'content' => 'Consegui minha primeira contribuição aceita num projeto open source hoje! Obrigado a quem revisou o PR e teve paciência com minhas dúvidas 🙏',
                'hoursAgo' => 50,
                'reactions' => ['🎉' => 27, '❤️' => 14, '👏' => 9],
                'replies' => [
                    'Parabéns!! Qual foi o projeto?',
                    'Isso é só o começo, bora pra próxima 🚀',
                ],
            ],
            [
                'content' => 'Dica rápida de Eloquent: `Model::automaticallyEagerLoadRelationships()` (Laravel 11+) resolve boa parte dos N+1 sem precisar lembrar de `with()` em todo lugar. Mas cuidado com queries que não precisam da relação — você paga o JOIN de qualquer jeito.',
                'hoursAgo' => 72,
                'reactions' => ['💡' => 10, '🔥' => 3],
            ],
            [
                'content' => 'Vagas abertas pra quem quer ajudar a organizar a próxima He4rt Conf! Procurando gente pra logística, conteúdo e design. Chama no DM.',
                'hoursAgo' => 96,
            ],
        ];
    }

    /**
     * @param  list<string>  $replies
     * @param  Collection<int, User>  $authors
     */
    private function seedReplies(Timeline $post, array $replies, Collection $authors, CarbonInterface $rootCreatedAt): void
    {
        foreach ($replies as $index => $content) {
            $author = $authors->random();
            $replyCreatedAt = $rootCreatedAt->clone()->addMinutes(($index + 1) * fake()->numberBetween(5, 45));

            $postEntry = PostEntry::query()->create(['content' => $content]);
            $postEntry->forceFill(['created_at' => $replyCreatedAt, 'updated_at' => $replyCreatedAt])->saveQuietly();

            $reply = Timeline::query()->create([
                'user_id' => $author->id,
                'postable_type' => $postEntry->getMorphClass(),
                'postable_id' => $postEntry->id,
                'root_id' => $post->id,
                'parent_id' => $post->id,
            ]);
            $reply->forceFill(['created_at' => $replyCreatedAt, 'updated_at' => $replyCreatedAt])->saveQuietly();
        }
    }

    /**
     * @param  array<string, int>  $reactions  emoji => count
     */
    private function seedReactions(Timeline $post, array $reactions): void
    {
        foreach ($reactions as $emoji => $count) {
            $post->reactions()->create([
                'emoji_key' => $emoji,
                'emoji_name' => $emoji,
                'count' => $count,
                'count_normal' => $count,
            ]);
        }
    }

    private function createPost(User $author, string $content, CarbonInterface $createdAt, bool $pinned): Timeline
    {
        $postEntry = PostEntry::query()->create(['content' => $content]);
        $postEntry->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        $post = Timeline::query()->create([
            'user_id' => $author->id,
            'postable_type' => $postEntry->getMorphClass(),
            'postable_id' => $postEntry->id,
            'pinned' => $pinned,
        ]);
        $post->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $post;
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveAuthors(): Collection
    {
        $existing = User::query()->limit(self::AUTHOR_POOL)->get();
        $missing = self::AUTHOR_POOL - $existing->count();

        if ($missing > 0) {
            return $existing->concat(User::factory()->count($missing)->create());
        }

        return $existing;
    }
}
