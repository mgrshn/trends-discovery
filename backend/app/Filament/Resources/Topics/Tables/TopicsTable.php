<?php

namespace App\Filament\Resources\Topics\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('geo')
                    ->sortable()
                    ->badge(),

                TextColumn::make('source')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trending'       => 'success',
                        'related_rising' => 'info',
                        'breakdown'      => 'gray',
                        default          => 'gray',
                    }),

                TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'exploding' => 'warning',
                        'regular'   => 'success',
                        'peaked'    => 'gray',
                        'noise'     => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('score')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('growth_pct')
                    ->label('Growth %')
                    ->numeric(0)
                    ->sortable(),

                TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                IconColumn::make('approved')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('discovered_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('score', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'exploding' => 'Exploding',
                        'regular'   => 'Regular',
                        'peaked'    => 'Peaked',
                        'noise'     => 'Noise',
                    ]),

                SelectFilter::make('source')
                    ->options([
                        'trending'       => 'Trending',
                        'related_rising' => 'Related Rising',
                        'breakdown'      => 'Breakdown',
                    ]),

                TernaryFilter::make('approved')
                    ->nullable()
                    ->placeholder('Pending review')
                    ->trueLabel('Approved')
                    ->falseLabel('Rejected'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['approved' => true]))
                    ->visible(fn ($record) => $record->approved !== true),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['approved' => false]))
                    ->visible(fn ($record) => $record->approved !== false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['approved' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_reject')
                        ->label('Reject selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['approved' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
