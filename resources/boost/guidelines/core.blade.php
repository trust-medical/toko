## Toko (TrustMedical)

This package provides article management models and migrations for Laravel.

### Features

- Post categories with parent-child hierarchy.
- Posts with status, scheduling, and slug history.
- Post revisions and publish history.
- Status change events.

@verbatim
<code-snippet name="Create a post" lang="php">
use TrustMedical\Toko\Enums\PostStatus;
use TrustMedical\Toko\Models\Post;
use TrustMedical\Toko\Models\PostCategory;

$category = PostCategory::create([
    'name' => 'News',
    'slug' => 'news',
]);

$post = Post::create([
    'author_user_id' => 1,
    'category_id' => $category->id,
    'title' => 'Hello Toko',
    'slug' => 'hello-toko',
    'status' => PostStatus::Draft,
]);
</code-snippet>
@endverbatim

### Notes

- User relations resolve via config('auth.providers.users.model') and fall back to App\Models\User.
- Run migrations after installation: php artisan migrate.
- PostStatus labels are translatable via the `toko::post-status.*` namespace and can be overridden by publishing `toko-translations`.
