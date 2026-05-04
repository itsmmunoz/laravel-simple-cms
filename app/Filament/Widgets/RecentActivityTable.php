<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivityTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Articles')
            ->description('Latest article updates')
            ->query($this->getRecentArticlesQuery())
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->url(fn ($record) => ArticleResource::getUrl('edit', ['record' => $record->id]))
                    ->weight('medium'),
                TextColumn::make('category.title')
                    ->label('Category')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Published' => 'success',
                        'Draft' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->color('gray'),
            ])
            ->paginated(false);
    }

    protected function getRecentArticlesQuery(): Builder
    {
        return Article::query()
            ->visibleTo(auth()->user())
            ->with('category')
            ->select([
                'id',
                'title',
                'slug',
                'category_id',
                'updated_at',
            ])
            ->selectRaw("CASE WHEN is_published = 1 THEN 'Published' ELSE 'Draft' END as status")
            ->latest('updated_at')
            ->limit(5);
    }
}
