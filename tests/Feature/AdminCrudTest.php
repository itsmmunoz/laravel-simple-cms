<?php

use App\Enums\UserRole;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Pages\Pages\CreatePage as CreatePagePage;
use App\Filament\Resources\Pages\Pages\EditPage as EditPagePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;

use Livewire\Livewire;

beforeEach(function () {
    $this->admin = createUser(UserRole::Admin);
    $this->actingAs($this->admin);
});

// ── Categories ───────────────────────────────────────────────────

describe('Categories', function () {
    it('can be created', function () {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'title' => 'Technology',
                'slug' => 'technology',
                'description' => 'Tech articles',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['title' => 'Technology', 'slug' => 'technology']);
    });

    it('can be edited', function () {
        $category = Category::create(['title' => 'Old Title', 'slug' => 'old-title', 'is_active' => true]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['title' => 'Updated Title', 'slug' => 'updated-title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'title' => 'Updated Title']);
    });

    it('can be deleted', function () {
        $category = Category::create(['title' => 'To Delete', 'slug' => 'to-delete', 'is_active' => true]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('appears in the list', function () {
        $category = Category::create(['title' => 'Listed', 'slug' => 'listed', 'is_active' => true]);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category]);
    });

    it('requires a title', function () {
        Livewire::test(CreateCategory::class)
            ->fillForm(['title' => '', 'slug' => 'no-title'])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    });

    it('requires a unique slug', function () {
        Category::create(['title' => 'Existing', 'slug' => 'existing-slug', 'is_active' => true]);

        Livewire::test(CreateCategory::class)
            ->fillForm(['title' => 'Another', 'slug' => 'existing-slug'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });
});

// ── Articles ─────────────────────────────────────────────────────

describe('Articles', function () {
    it('can be created with a category', function () {
        $category = Category::create(['title' => 'Tech', 'slug' => 'tech', 'is_active' => true]);

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'My First Article',
                'slug' => 'my-first-article',
                'content' => '<p>Body</p>',
                'category_id' => $category->id,
                'is_published' => true,
                'published_at' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', ['title' => 'My First Article', 'category_id' => $category->id]);
    });

    it('can be created without a category', function () {
        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'No Category',
                'slug' => 'no-category',
                'content' => '<p>Content</p>',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', ['title' => 'No Category', 'category_id' => null]);
    });

    it('can be edited', function () {
        $article = Article::create(['title' => 'Original', 'slug' => 'original', 'content' => '<p>Old</p>', 'is_published' => false]);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm(['title' => 'Updated', 'slug' => 'updated', 'is_published' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Updated', 'is_published' => true]);
    });

    it('can be deleted', function () {
        $article = Article::create(['title' => 'Delete Me', 'slug' => 'delete-me', 'content' => 'C', 'is_published' => false]);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    });

    it('appears in the list', function () {
        $article = Article::create(['title' => 'Listed', 'slug' => 'listed', 'content' => 'C', 'is_published' => true, 'published_at' => now()]);

        Livewire::test(ListArticles::class)
            ->assertCanSeeTableRecords([$article]);
    });

    it('requires title and slug', function () {
        Livewire::test(CreateArticle::class)
            ->fillForm(['title' => '', 'slug' => ''])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'slug' => 'required']);
    });

    it('requires a unique slug', function () {
        Article::create(['title' => 'Existing', 'slug' => 'existing-slug', 'content' => 'C', 'is_published' => false]);

        Livewire::test(CreateArticle::class)
            ->fillForm(['title' => 'Another', 'slug' => 'existing-slug'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });
});

// ── Pages ────────────────────────────────────────────────────────

describe('Pages', function () {
    it('can be created', function () {
        Livewire::test(CreatePagePage::class)
            ->fillForm([
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<p>About us content</p>',
                'is_published' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['title' => 'About Us', 'slug' => 'about-us']);
    });

    it('can be created as a child page', function () {
        $parent = Page::create(['title' => 'Parent', 'slug' => 'parent', 'is_published' => true]);

        Livewire::test(CreatePagePage::class)
            ->fillForm([
                'title' => 'Child Page',
                'slug' => 'child-page',
                'parent_id' => $parent->id,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['title' => 'Child Page', 'parent_id' => $parent->id]);
    });

    it('can be edited', function () {
        $page = Page::create(['title' => 'Old', 'slug' => 'old', 'content' => '<p>Old</p>', 'is_published' => false]);

        Livewire::test(EditPagePage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Updated', 'slug' => 'updated', 'is_published' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Updated', 'is_published' => true]);
    });

    it('can be deleted', function () {
        $page = Page::create(['title' => 'Delete Me', 'slug' => 'delete-me', 'is_published' => false]);

        Livewire::test(EditPagePage::class, ['record' => $page->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    });

    it('appears in the list', function () {
        $page = Page::create(['title' => 'Listed', 'slug' => 'listed', 'is_published' => true]);

        Livewire::test(ListPages::class)
            ->assertCanSeeTableRecords([$page]);
    });

    it('requires title and slug', function () {
        Livewire::test(CreatePagePage::class)
            ->fillForm(['title' => '', 'slug' => ''])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'slug' => 'required']);
    });
});

// ── Users ────────────────────────────────────────────────────────

describe('Users', function () {
    it('can be created as editor', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Editor',
                'email' => 'editor@test.com',
                'password' => 'securepassword',
                'password_confirmation' => 'securepassword',
                'role' => 'editor',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'editor@test.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->role)->toBe(UserRole::Editor);
    });

    it('can be created as admin', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Admin',
                'email' => 'newadmin@test.com',
                'password' => 'securepassword',
                'password_confirmation' => 'securepassword',
                'role' => 'admin',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'newadmin@test.com')->first();
        expect($user->role)->toBe(UserRole::Admin);
    });

    it('can be edited', function () {
        $editor = createUser(UserRole::Editor, ['name' => 'Old Name', 'email' => 'old@test.com']);

        Livewire::test(EditUser::class, ['record' => $editor->getRouteKey()])
            ->fillForm(['name' => 'New Name', 'email' => 'new@test.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', ['id' => $editor->id, 'name' => 'New Name', 'email' => 'new@test.com']);
    });

    it('can be deleted', function () {
        $user = createUser(UserRole::Editor, ['email' => 'deleteme@test.com']);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('appears in the list', function () {
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$this->admin]);
    });

    it('requires name, email, and password', function () {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => '', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);
    });

    it('requires unique email', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplicate',
                'email' => $this->admin->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'editor',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    });

    it('requires password confirmation', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'test@test.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
                'role' => 'editor',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'confirmed']);
    });

    it('requires minimum password length', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'short@test.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'role' => 'editor',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'min']);
    });

    it('cannot delete self', function () {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->assertActionHidden('delete');
    });
});

// ── Editor CRUD ──────────────────────────────────────────────────

describe('Editor role', function () {
    beforeEach(function () {
        $this->actingAs(createUser(UserRole::Editor));
    });

    it('can create articles', function () {
        Livewire::test(CreateArticle::class)
            ->fillForm(['title' => 'Editor Article', 'slug' => 'editor-article', 'content' => '<p>Content</p>', 'is_published' => true, 'published_at' => now()->toDateTimeString()])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', ['title' => 'Editor Article']);
    });

    it('can create categories', function () {
        Livewire::test(CreateCategory::class)
            ->fillForm(['title' => 'Editor Category', 'slug' => 'editor-category', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['title' => 'Editor Category']);
    });

    it('can create pages', function () {
        Livewire::test(CreatePagePage::class)
            ->fillForm(['title' => 'Editor Page', 'slug' => 'editor-page', 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['title' => 'Editor Page']);
    });
});

// ── Ownership Authorization ─────────────────────────────────────

describe('Ownership authorization', function () {
    it('prevents editor from updating or deleting admin article', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Admin Article', 'slug' => 'admin-article',
            'content' => '<p>Content</p>', 'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('update', $article))->toBeFalse();
        expect($editor->can('delete', $article))->toBeFalse();
    });

    it('allows editor to update and delete own article', function () {
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Editor Article', 'slug' => 'editor-own-article',
            'content' => '<p>Content</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('update', $article))->toBeTrue();
        expect($editor->can('delete', $article))->toBeTrue();
    });

    it('allows admin to update and delete any article', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Editor Article', 'slug' => 'editor-article-2',
            'content' => '<p>Content</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $this->actingAs($admin);
        expect($admin->can('update', $article))->toBeTrue();
        expect($admin->can('delete', $article))->toBeTrue();
    });

    it('prevents editor from updating or deleting admin page', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $page = Page::forceCreate([
            'title' => 'Admin Page', 'slug' => 'admin-page',
            'content' => '<p>Content</p>', 'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('update', $page))->toBeFalse();
        expect($editor->can('delete', $page))->toBeFalse();
    });

    it('allows editor to update and delete own page', function () {
        $editor = createUser(UserRole::Editor);
        $page = Page::forceCreate([
            'title' => 'Editor Page', 'slug' => 'editor-own-page',
            'content' => '<p>Content</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('update', $page))->toBeTrue();
        expect($editor->can('delete', $page))->toBeTrue();
    });

    it('prevents editor from updating or deleting admin media', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $media = \App\Models\Media::forceCreate([
            'model_type' => 'App\Models\MediaItem', 'model_id' => 1,
            'collection_name' => 'default', 'name' => 'test', 'file_name' => 'test.jpg',
            'mime_type' => 'image/jpeg', 'disk' => 'public', 'size' => 1024,
            'manipulations' => '[]', 'custom_properties' => '[]',
            'generated_conversions' => '[]', 'responsive_images' => '[]',
            'uuid' => \Illuminate\Support\Str::uuid(), 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('update', $media))->toBeFalse();
        expect($editor->can('delete', $media))->toBeFalse();
    });

    it('editor cannot see admin articles in list', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminArticle = Article::forceCreate([
            'title' => 'Admin Only', 'slug' => 'admin-only',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListArticles::class)
            ->assertCanNotSeeTableRecords([$adminArticle]);
    });

    it('editor cannot see admin pages in list', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminPage = Page::forceCreate([
            'title' => 'Admin Page', 'slug' => 'admin-page-hidden',
            'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListPages::class)
            ->assertCanNotSeeTableRecords([$adminPage]);
    });

    it('editor cannot see admin media in list', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminMedia = \App\Models\Media::forceCreate([
            'model_type' => 'App\Models\MediaItem', 'model_id' => 1,
            'collection_name' => 'images', 'name' => 'admin-photo', 'file_name' => 'admin.jpg',
            'mime_type' => 'image/jpeg', 'disk' => 'public', 'size' => 1024,
            'manipulations' => '[]', 'custom_properties' => '[]',
            'generated_conversions' => '[]', 'responsive_images' => '[]',
            'uuid' => \Illuminate\Support\Str::uuid(), 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListMedia::class)
            ->assertCanNotSeeTableRecords([$adminMedia]);
    });

    it('editor can see own articles in list', function () {
        $editor = createUser(UserRole::Editor);
        $editorArticle = Article::forceCreate([
            'title' => 'My Article', 'slug' => 'my-article',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListArticles::class)
            ->assertCanSeeTableRecords([$editorArticle]);
    });

    it('admin can see all articles regardless of owner', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $editorArticle = Article::forceCreate([
            'title' => 'Editor Article', 'slug' => 'editor-visible',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $this->actingAs($admin);
        Livewire::test(ListArticles::class)
            ->assertCanSeeTableRecords([$editorArticle]);
    });

    it('auto-assigns user_id when creating article', function () {
        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);

        $article = Article::create([
            'title' => 'Auto Assign', 'slug' => 'auto-assign',
            'content' => '<p>C</p>', 'is_published' => false,
        ]);

        expect($article->fresh()->user_id)->toBe($editor->id);
    });

    it('auto-assigns user_id when creating page', function () {
        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);

        $page = Page::create([
            'title' => 'Auto Assign Page', 'slug' => 'auto-assign-page',
            'is_published' => false,
        ]);

        expect($page->fresh()->user_id)->toBe($editor->id);
    });

    it('preserves content when user is deleted via nullOnDelete', function () {
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Orphan Article', 'slug' => 'orphan-article',
            'content' => '<p>Keep me</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        $editor->delete();

        $article->refresh();
        expect($article->exists)->toBeTrue();
        expect($article->user_id)->toBeNull();
    });

    it('editor can delete own article via Filament', function () {
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Delete Own', 'slug' => 'delete-own',
            'content' => '<p>C</p>', 'is_published' => false, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    });

    it('editor can delete own page via Filament', function () {
        $editor = createUser(UserRole::Editor);
        $page = Page::forceCreate([
            'title' => 'Delete Own Page', 'slug' => 'delete-own-page',
            'is_published' => false, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(EditPagePage::class, ['record' => $page->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    });

    it('editor cannot access admin article edit page via direct URL', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Admin Direct URL', 'slug' => 'admin-direct-url',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        $this->get('/admin/articles/' . $article->id . '/edit')
            ->assertNotFound(); // Query scoping hides the record entirely (more secure than 403)
    });

    it('editor cannot access admin page edit page via direct URL', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $page = Page::forceCreate([
            'title' => 'Admin Direct URL Page', 'slug' => 'admin-direct-url-page',
            'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        $this->get('/admin/pages/' . $page->id . '/edit')
            ->assertNotFound();
    });

    it('editor cannot forceDelete own article', function () {
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Force Delete Test', 'slug' => 'force-delete-test',
            'content' => '<p>C</p>', 'is_published' => false, 'user_id' => $editor->id,
        ]);

        $this->actingAs($editor);
        expect($editor->can('forceDelete', $article))->toBeFalse();
    });

    it('admin can forceDelete any article', function () {
        $admin = createUser(UserRole::Admin);
        $article = Article::forceCreate([
            'title' => 'Admin Force Delete', 'slug' => 'admin-force-delete',
            'content' => '<p>C</p>', 'is_published' => false, 'user_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        expect($admin->can('forceDelete', $article))->toBeTrue();
    });

    it('bulk delete skips records editor does not own', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminArticle = Article::forceCreate([
            'title' => 'Bulk Protected', 'slug' => 'bulk-protected',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListArticles::class)
            ->callTableBulkAction('delete', [$adminArticle->id]);

        $this->assertDatabaseHas('articles', ['id' => $adminArticle->id]);
    });

    it('bulk delete skips pages editor does not own', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminPage = Page::forceCreate([
            'title' => 'Bulk Protected Page', 'slug' => 'bulk-protected-page',
            'is_published' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListPages::class)
            ->callTableBulkAction('delete', [$adminPage->id]);

        $this->assertDatabaseHas('pages', ['id' => $adminPage->id]);
    });

    it('allows editor to update and delete orphaned article (owner deleted)', function () {
        $originalOwner = createUser(UserRole::Editor);
        $orphan = Article::forceCreate([
            'title' => 'Orphan', 'slug' => 'orphan-update',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $originalOwner->id,
        ]);
        $originalOwner->delete();
        $orphan->refresh();
        expect($orphan->user_id)->toBeNull();

        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        expect($editor->can('update', $orphan))->toBeTrue();
        expect($editor->can('delete', $orphan))->toBeTrue();
    });

    it('allows editor to update and delete orphaned page (owner deleted)', function () {
        $originalOwner = createUser(UserRole::Editor);
        $orphan = Page::forceCreate([
            'title' => 'Orphan Page', 'slug' => 'orphan-page',
            'is_published' => true, 'user_id' => $originalOwner->id,
        ]);
        $originalOwner->delete();
        $orphan->refresh();
        expect($orphan->user_id)->toBeNull();

        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        expect($editor->can('update', $orphan))->toBeTrue();
        expect($editor->can('delete', $orphan))->toBeTrue();
    });

    it('still prevents editor from forceDeleting orphaned article', function () {
        $originalOwner = createUser(UserRole::Editor);
        $orphan = Article::forceCreate([
            'title' => 'Orphan FD', 'slug' => 'orphan-fd',
            'content' => '<p>C</p>', 'is_published' => false, 'user_id' => $originalOwner->id,
        ]);
        $originalOwner->delete();
        $orphan->refresh();

        $editor = createUser(UserRole::Editor);
        $this->actingAs($editor);
        expect($editor->can('forceDelete', $orphan))->toBeFalse();
    });

    it('visibleTo scope hides admin pages from editor (drives PageForm parent_id)', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);

        $adminPage = Page::forceCreate(['title' => 'Admin Parent', 'slug' => 'admin-parent', 'is_published' => true, 'user_id' => $admin->id]);
        $editorPage = Page::forceCreate(['title' => 'Editor Parent', 'slug' => 'editor-parent', 'is_published' => true, 'user_id' => $editor->id]);

        $visibleIds = Page::query()->visibleTo($editor)->pluck('id')->all();

        expect($visibleIds)->toContain($editorPage->id)
            ->and($visibleIds)->not->toContain($adminPage->id);
    });

    it('visibleTo scope returns everything to admin', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $a = Page::forceCreate(['title' => 'A', 'slug' => 'vt-a', 'is_published' => true, 'user_id' => $admin->id]);
        $b = Page::forceCreate(['title' => 'B', 'slug' => 'vt-b', 'is_published' => true, 'user_id' => $editor->id]);

        $ids = Page::query()->visibleTo($admin)->pluck('id')->all();
        expect($ids)->toContain($a->id, $b->id);
    });

    it('visibleTo scope includes orphans for editor', function () {
        $editor = createUser(UserRole::Editor);
        $owner = createUser(UserRole::Editor);
        $orphan = Page::forceCreate(['title' => 'Orphaning', 'slug' => 'orphaning', 'is_published' => true, 'user_id' => $owner->id]);
        $owner->delete();

        $ids = Page::query()->visibleTo($editor)->pluck('id')->all();
        expect($ids)->toContain($orphan->id);
    });

    it('saves to article invalidates editor dashboard_stats cache', function () {
        $editor = createUser(UserRole::Editor);
        cache()->put('dashboard_stats:'.$editor->id, ['totalArticles' => 99], 60);

        Article::forceCreate([
            'title' => 'Cache Buster', 'slug' => 'cache-buster',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        expect(cache()->has('dashboard_stats:'.$editor->id))->toBeFalse();
    });

    it('saves to page invalidates editor dashboard_stats and nav_pages cache', function () {
        $editor = createUser(UserRole::Editor);
        cache()->put('dashboard_stats:'.$editor->id, ['totalPages' => 99], 60);
        cache()->put('nav_pages', [['slug' => 'stale', 'title' => 'Stale']], 300);

        Page::forceCreate([
            'title' => 'New Page', 'slug' => 'new-page-cache',
            'is_published' => true, 'user_id' => $editor->id,
        ]);

        expect(cache()->has('dashboard_stats:'.$editor->id))->toBeFalse()
            ->and(cache()->has('nav_pages'))->toBeFalse();
    });

    it('promoting editor to admin busts their dashboard_stats cache', function () {
        $editor = createUser(UserRole::Editor);
        cache()->put('dashboard_stats:'.$editor->id, ['totalArticles' => 5], 60);

        $editor->role = UserRole::Admin;
        $editor->save();

        expect(cache()->has('dashboard_stats:'.$editor->id))->toBeFalse();
    });

    it('saving user without role change does not bust dashboard_stats cache', function () {
        $editor = createUser(UserRole::Editor);
        cache()->put('dashboard_stats:'.$editor->id, ['totalArticles' => 5], 60);

        $editor->name = 'Updated Name';
        $editor->save();

        expect(cache()->has('dashboard_stats:'.$editor->id))->toBeTrue();
    });

    it('deleting user busts their dashboard_stats cache', function () {
        $editor = createUser(UserRole::Editor);
        cache()->put('dashboard_stats:'.$editor->id, ['totalArticles' => 5], 60);

        $editor->delete();

        expect(cache()->has('dashboard_stats:'.$editor->id))->toBeFalse();
    });

    it('policy treats stringified user_id as equal to int (MySQL safety)', function () {
        $editor = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'String Id', 'slug' => 'string-id-test',
            'content' => '<p>C</p>', 'is_published' => true, 'user_id' => $editor->id,
        ]);

        // Simulate MySQL's PDO returning user_id as string by setting it explicitly
        $article->setRawAttributes(array_merge($article->getAttributes(), ['user_id' => (string) $editor->id]));

        $this->actingAs($editor);
        expect($editor->can('update', $article))->toBeTrue();
    });

    it('editor can edit orphaned article via Filament after original owner deleted', function () {
        $originalOwner = createUser(UserRole::Editor);
        $article = Article::forceCreate([
            'title' => 'Will Be Orphaned', 'slug' => 'will-be-orphaned',
            'content' => '<p>C</p>', 'is_published' => false, 'user_id' => $originalOwner->id,
        ]);
        $originalOwner->delete();
        $article->refresh();
        expect($article->user_id)->toBeNull();

        $newEditor = createUser(UserRole::Editor);
        $this->actingAs($newEditor);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm(['title' => 'Reclaimed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Reclaimed']);
    });

    it('bulk delete skips media editor does not own', function () {
        $admin = createUser(UserRole::Admin);
        $editor = createUser(UserRole::Editor);
        $adminMedia = \App\Models\Media::forceCreate([
            'model_type' => 'App\Models\MediaItem', 'model_id' => 1,
            'collection_name' => 'images', 'name' => 'bulk-test', 'file_name' => 'bulk.jpg',
            'mime_type' => 'image/jpeg', 'disk' => 'public', 'size' => 1024,
            'manipulations' => '[]', 'custom_properties' => '[]',
            'generated_conversions' => '[]', 'responsive_images' => '[]',
            'uuid' => \Illuminate\Support\Str::uuid(), 'user_id' => $admin->id,
        ]);

        $this->actingAs($editor);
        Livewire::test(ListMedia::class)
            ->callTableBulkAction('delete', [$adminMedia->id]);

        $this->assertDatabaseHas('media', ['id' => $adminMedia->id]);
    });
});
