<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('keyword')
                    ->required(),
                TextInput::make('geo')
                    ->required()
                    ->default(''),
                TextInput::make('source')
                    ->required()
                    ->default('trending'),
                TextInput::make('seed_keyword'),
                TextInput::make('category_id')
                    ->numeric(),
                TextInput::make('status'),
                TextInput::make('score')
                    ->numeric(),
                TextInput::make('volume')
                    ->numeric(),
                TextInput::make('growth_3m')
                    ->numeric(),
                TextInput::make('growth_6m')
                    ->numeric(),
                TextInput::make('growth_12m')
                    ->numeric(),
                TextInput::make('sparkline'),
                DateTimePicker::make('discovered_at')
                    ->required(),
                DateTimePicker::make('last_scored_at'),
                Toggle::make('approved'),
                TextInput::make('growth_pct')
                    ->numeric(),
            ]);
    }
}
