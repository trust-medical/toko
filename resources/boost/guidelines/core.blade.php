## Toko (TrustMedical)

This package provides article management models and migrations for Laravel.

### Features

- Post categories with parent-child hierarchy.
- Posts with status, scheduling, and slug history.
- Post revisions and publish history.
- Status change events.
- Services (resolve via contracts): `PostEditorContract`, `PostPublisherContract`, `PostSchedulerContract`, `PostRevisionRestorerContract`.
- `php artisan toko:publish-scheduled` publishes due scheduled posts.

@verbatim
<code-snippet name="Create and publish a post" lang="php">
use TrustMedical\Toko\Contracts\PostEditorContract;
use TrustMedical\Toko\Enums\PostStatus;

$editor = app(PostEditorContract::class);

$post = $editor->create(
    ['category_id' => $categoryId, 'slug' => 'hello-toko'],
    ['title' => 'Hello Toko', 'content_json' => $json, 'content_html' => $html],
    author: $user,
);

// Publishes the latest revision
$post = $editor->update($post, ['status' => PostStatus::Published], [], $user);
</code-snippet>

<code-snippet name="Query visible posts" lang="php">
use TrustMedical\Toko\Models\Post;

Post::visible()->inCategory($categoryId)->latest('published_at')->paginate();
</code-snippet>
@endverbatim

### Notes

- User relations resolve via config('auth.providers.users.model') and fall back to App\Models\User.
- Run migrations after installation: php artisan migrate.
- Change post status through the services, not by writing `status` directly, so publish history, schedules and status events stay consistent.
- A published post's title/excerpt/slug change only by publishing a new revision.
- Published posts cannot be scheduled, and the same revision cannot be published twice (use `PostRevisionRestorerContract::restore()` to create a new revision).
- Services throw `InvalidArgumentException` on invalid input.
- PostStatus labels are translatable via the `toko::post-status.*` namespace and can be overridden by publishing `toko-translations`.
