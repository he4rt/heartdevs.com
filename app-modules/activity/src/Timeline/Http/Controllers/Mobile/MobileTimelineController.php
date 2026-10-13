<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\Actions\CreateReply;
use He4rt\Activity\Timeline\Actions\DeleteReply;
use He4rt\Activity\Timeline\Actions\LikePost;
use He4rt\Activity\Timeline\Actions\UnlikePost;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use He4rt\Activity\Timeline\Http\Resources\TimelinePostResource;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class MobileTimelineController extends Controller
{
    /**
     * Listar timeline
     *
     * Retorna os posts raiz da timeline, mais recentes primeiro.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $posts = Timeline::query()
            ->feed()
            ->with(['user.media', 'user.providers', 'postable.media'])
            ->with(['likes' => fn (Relation $query): Relation => $query->where('user_id', $user->id)])
            ->withCount(['children', 'reactions', 'likes'])
            ->simplePaginate(20);

        return TimelinePostResource::collection($posts);
    }

    /**
     * Criar post
     *
     * Publica um novo post na timeline, com até 4 imagens opcionais.
     */
    public function store(Request $request, CreatePost $createPost): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'images' => ['array', 'max:4'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $post = $createPost->handle(new CreatePostDTO(
            userId: $request->user()->id,
            content: $request->string('content')->toString(),
            images: $this->storeImages($request),
        ));

        return $this->postResponse($request, $post, Response::HTTP_CREATED);
    }

    /**
     * Responder post
     *
     * Cria uma resposta pro post (ou resposta) indicado — sempre fica
     * pendurada no post raiz da thread, mesmo respondendo outra resposta.
     */
    public function storeReply(Request $request, Timeline $post, CreateReply $createReply): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'images' => ['array', 'max:4'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $reply = $createReply->handle(new CreateReplyDTO(
            userId: $request->user()->id,
            parentTimelineId: $post->id,
            content: $request->string('content')->toString(),
            images: $this->storeImages($request),
        ));

        return $this->postResponse($request, $reply, Response::HTTP_CREATED);
    }

    /**
     * Excluir resposta
     *
     * Remove uma resposta própria. Só o autor pode excluir, e só vale pra
     * respostas — não para posts raiz.
     */
    public function destroyReply(Request $request, Timeline $reply, DeleteReply $deleteReply): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $deleteReply->handle($user, $reply);
        } catch (AuthorizationException $authorizationException) {
            return response()->json(['message' => $authorizationException->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Curtir post
     *
     * Idempotente — curtir de novo um post já curtido não tem efeito.
     */
    public function like(Request $request, Timeline $post, LikePost $likePost): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $likePost->handle($user, $post);

        return $this->likeResponse($post, $user);
    }

    /**
     * Descurtir post
     *
     * Idempotente — descurtir um post não curtido não tem efeito.
     */
    public function unlike(Request $request, Timeline $post, UnlikePost $unlikePost): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $unlikePost->handle($user, $post);

        return $this->likeResponse($post, $user);
    }

    /**
     * @return array<int, string>
     */
    private function storeImages(Request $request): array
    {
        /** @var list<UploadedFile> $images */
        $images = $request->file('images', []);

        return array_map(
            static function (UploadedFile $image): string {
                $path = $image->store('timeline-uploads', 'public');
                throw_if($path === false, RuntimeException::class, 'Failed to store the uploaded image.');

                return $path;
            },
            $images,
        );
    }

    private function postResponse(Request $request, Timeline $post, int $status): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // create() não preenche defaults de banco (pinned, is_ignored, views) no model em memória.
        $post->refresh()
            ->load(['user.media', 'user.providers', 'postable.media'])
            ->load(['likes' => fn (Relation $query): Relation => $query->where('user_id', $user->id)])
            ->loadCount(['children', 'reactions', 'likes']);

        return new TimelinePostResource($post)
            ->response()
            ->setStatusCode($status);
    }

    private function likeResponse(Timeline $post, User $user): JsonResponse
    {
        $post->loadCount('likes');
        $likedByMe = $post->likes()->where('user_id', $user->id)->exists();

        return response()->json([
            'likes_count' => $post->likes_count,
            'liked_by_me' => $likedByMe,
        ]);
    }
}
